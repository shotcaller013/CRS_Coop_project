<?php
// app/Services/BillService.php
namespace App\Services;

use App\Models\AmortizationSchedule;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillRemittance;
use App\Models\CoopProfile;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BillService
{
    // ── Create (DRAFT) ────────────────────────────────────────

    /**
     * Build a new DRAFT bill for a company.
     * Finds all PENDING/OVERDUE amortization periods for members of
     * that company whose due_date falls within the billing period.
     */
    public function create(array $data): Bill
    {
        return DB::transaction(function () use ($data) {

            // Find eligible schedule periods
            $schedules = AmortizationSchedule::with(['loan.member', 'loan'])
                ->whereIn('status', ['PENDING', 'OVERDUE'])
                ->whereBetween('due_date', [
                    $data['billing_period_start'],
                    $data['billing_period_end'],
                ])
                ->whereHas('loan', fn($q) => $q->where('status', 'ACTIVE'))
                ->whereHas('loan.member', fn($q) =>
                    $q->where('company', function ($sub) use ($data) {
                        // Match members whose company matches the selected company name
                        $sub->select('name')
                            ->from('companies')
                            ->where('id', $data['company_id'])
                            ->limit(1);
                    })
                )
                ->get();

            if ($schedules->isEmpty()) {
                throw new \InvalidArgumentException(
                    'No pending amortization periods found for this company in the selected billing period.'
                );
            }

            // Create the bill
            $bill = Bill::create([
                'bill_no'              => $this->generateBillNo(),
                'company_id'           => $data['company_id'],
                'status'               => 'DRAFT',
                'billing_period_start' => $data['billing_period_start'],
                'billing_period_end'   => $data['billing_period_end'],
                'total_amount'         => $schedules->sum('amount_due'),
                'amount_remitted'      => 0,
                'prepared_by'          => Auth::id(),
                'notes'                => $data['notes'] ?? null,
            ]);

            // Create line items
            foreach ($schedules as $schedule) {
                BillItem::create([
                    'bill_id'     => $bill->id,
                    'schedule_id' => $schedule->id,
                    'member_id'   => $schedule->loan->member_id,
                    'loan_id'     => $schedule->loan_id,
                    'amount_due'  => $schedule->amount_due,
                    'amount_paid' => 0,
                    'status'      => 'PENDING',
                ]);
            }

            return $bill->load(['company', 'items.member', 'items.loan', 'items.schedule']);
        });
    }

    // ── Issue ─────────────────────────────────────────────────

    /**
     * Issue a DRAFT bill — marks all amortization periods as BILLED
     * so they won't be picked up by future billing cycles.
     */
    public function issue(Bill $bill): Bill
    {
        if ($bill->status !== 'DRAFT') {
            throw new \InvalidArgumentException('Only DRAFT bills can be issued.');
        }

        return DB::transaction(function () use ($bill) {
            $scheduleIds = $bill->items()->pluck('schedule_id');

            // Mark all periods as BILLED
            AmortizationSchedule::whereIn('id', $scheduleIds)
                ->update(['status' => 'BILLED', 'bill_item_id' => DB::raw('(
                    SELECT id FROM bill_items
                    WHERE bill_items.schedule_id = amortization_schedules.id
                    AND bill_items.bill_id = ' . $bill->id . '
                    LIMIT 1
                )')]);

            $bill->update([
                'status'    => 'ISSUED',
                'issued_at' => now(),
            ]);

            return $bill->fresh(['company', 'items.member', 'items.loan', 'items.schedule']);
        });
    }

    // ── Upload remittance ─────────────────────────────────────

    /**
     * Record a remittance payment from the company.
     * If total remitted >= total amount, auto-settle.
     */
    public function uploadRemittance(Bill $bill, array $data, ?UploadedFile $file): Bill
    {
        return DB::transaction(function () use ($bill, $data, $file) {
            $filePath = null;
            if ($file) {
                $filePath = $file->store(
                    "remittances/{$bill->bill_no}",
                    'local'
                );
            }

            BillRemittance::create([
                'bill_id'         => $bill->id,
                'or_number'       => $data['or_number'] ?? null,
                'amount'          => $data['amount'],
                'remittance_date' => $data['remittance_date'],
                'notes'           => $data['notes'] ?? null,
                'file_path'       => $filePath,
                'posted_by'       => Auth::id(),
            ]);

            $newRemitted = round((float)$bill->amount_remitted + (float)$data['amount'], 2);
            $isFullyPaid = $newRemitted >= (float)$bill->total_amount;

            $bill->update([
                'amount_remitted' => $newRemitted,
                'status'          => $isFullyPaid ? 'SETTLED' : 'PARTIAL',
                'settled_at'      => $isFullyPaid ? now() : null,
            ]);

            // If fully settled, auto-create payment records and mark schedules PAID
            if ($isFullyPaid) {
                $this->settleItems($bill);
            }

            return $bill->fresh(['company', 'items', 'remittances']);
        });
    }

    // ── Manual settle ─────────────────────────────────────────

    /**
     * Manually mark a bill as settled (e.g. cash payment without upload).
     */
    public function settle(Bill $bill): Bill
    {
        return DB::transaction(function () use ($bill) {
            $bill->update([
                'status'     => 'SETTLED',
                'settled_at' => now(),
                'amount_remitted' => $bill->total_amount, // mark as fully paid
            ]);

            $this->settleItems($bill);

            return $bill->fresh(['company', 'items']);
        });
    }

    // ── Cancel ────────────────────────────────────────────────

    public function cancel(Bill $bill): Bill
    {
        return DB::transaction(function () use ($bill) {
            // Revert BILLED periods back to PENDING
            if ($bill->status === 'ISSUED') {
                $scheduleIds = $bill->items()->pluck('schedule_id');
                AmortizationSchedule::whereIn('id', $scheduleIds)
                    ->where('status', 'BILLED')
                    ->update(['status' => 'PENDING', 'bill_item_id' => null]);
            }

            $bill->update(['status' => 'CANCELLED']);
            return $bill->fresh();
        });
    }

    // ── PDF generation ────────────────────────────────────────

    public function generatePdf(Bill $bill): string
    {
        $bill->load([
            'company',
            'preparedBy',
            'items.member',
            'items.loan',
            'items.schedule',
            'remittances.postedBy',
        ]);

        $profile = CoopProfile::current();

        $pdf = Pdf::loadView('billing.bill', [
            'bill'       => $bill,
            'profile'    => $profile,
            'items'      => $bill->items,
            'remittances'=> $bill->remittances,
            'printed_by' => Auth::user()?->name,
            'printed_at' => now()->format('F d, Y  H:i'),
        ])->setPaper('a4', 'portrait');

        $path = storage_path("app/exports/Bill_{$bill->bill_no}_" . now()->format('Ymd_His') . '.pdf');
        @mkdir(dirname($path), 0755, true);
        $pdf->save($path);
        return $path;
    }

    // ── List ──────────────────────────────────────────────────

    public function list(array $filters = [])
    {
        $query = Bill::with('company')->orderByDesc('created_at');

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('billing_period_start', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('billing_period_end', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    // ── Private helpers ───────────────────────────────────────

    /**
     * When a bill is settled, create payment records for all line items
     * and mark the amortization schedules as PAID.
     */
    private function settleItems(Bill $bill): void
    {
        $items = $bill->items()->with('schedule')->where('status', 'PENDING')->get();

        foreach ($items as $item) {
            // Create a payment record
            Payment::create([
                'loan_id'      => $item->loan_id,
                'schedule_id'  => $item->schedule_id,
                'amount_paid'  => $item->amount_due,
                'payment_type' => 'full',
                'or_number'    => "BILL-{$bill->bill_no}",
                'payment_date' => now()->toDateString(),
                'penalty_paid' => 0,
                'balance_after'=> 0, // will be recomputed if needed
                'received_by'  => Auth::id(),
            ]);

            // Mark schedule as PAID
            AmortizationSchedule::where('id', $item->schedule_id)
                ->update(['status' => 'PAID', 'paid_amount' => $item->amount_due, 'paid_date' => now()]);

            // Mark bill item as PAID
            $item->update(['status' => 'PAID', 'amount_paid' => $item->amount_due]);
        }

        // Check if loan should auto-close
        foreach ($items->pluck('loan_id')->unique() as $loanId) {
            $allPaid = AmortizationSchedule::where('loan_id', $loanId)
                ->whereNotIn('status', ['PAID', 'VOIDED'])
                ->doesntExist();

            if ($allPaid) {
                \App\Models\Loan::where('id', $loanId)->update(['status' => 'CLOSED']);
            }
        }
    }

    private function generateBillNo(): string
    {
        $year = now()->year;
        $last = Bill::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('bill_no');

        $seq = $last ? (int)substr($last, -5) + 1 : 1;
        return sprintf('BL-%04d-%05d', $year, $seq);
    }
}
