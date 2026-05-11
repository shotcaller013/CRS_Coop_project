<?php
// app/Services/ShareCapitalService.php
namespace App\Services;

use App\Models\Member;
use App\Models\ShareCapitalTransaction;
use App\Models\CoopProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShareCapitalService
{
    // ── Post a transaction ────────────────────────────────────

    public function post(Member $member, array $data, string $direction): ShareCapitalTransaction
    {
        return DB::transaction(function () use ($member, $data, $direction) {

            // Guard: withdrawal cannot exceed current balance
            if ($direction === 'debit') {
                $current = (float) $member->share_capital;
                if ((float) $data['amount'] > $current) {
                    throw new \InvalidArgumentException(
                        "Withdrawal of ₱{$data['amount']} exceeds current balance of ₱{$current}."
                    );
                }
            }

            // Compute balance_after
            $current      = (float) $member->share_capital;
            $balanceAfter = $direction === 'credit'
                ? round($current + (float) $data['amount'], 2)
                : round($current - (float) $data['amount'], 2);

            $tx = ShareCapitalTransaction::create([
                'member_id'        => $member->id,
                'type'             => $data['type'],
                'direction'        => $direction,
                'amount'           => $data['amount'],
                'balance_after'    => $balanceAfter,
                'or_number'        => $data['or_number'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'remarks'          => $data['remarks'] ?? null,
                'reference_no'     => $data['reference_no'] ?? null,
                'posted_by'        => Auth::id(),
            ]);

            // Sync member.share_capital
            $member->syncShareCapitalBalance();

            return $tx;
        });
    }

    // ── Void a transaction ────────────────────────────────────

    public function void(ShareCapitalTransaction $tx): void
    {
        DB::transaction(function () use ($tx) {
            $tx->delete(); // soft-delete
            $tx->member->syncShareCapitalBalance();
        });
    }

    // ── Ledger for one member ─────────────────────────────────

    public function getLedger(Member $member, array $filters = []): Collection
    {
        $query = ShareCapitalTransaction::withTrashed()
            ->where('member_id', $member->id)
            ->orderBy('transaction_date')
            ->orderBy('id');

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->get();
    }

    // ── Aggregate summary for one member ─────────────────────

    public function getSummary(Member $member): array
    {
        $txns = ShareCapitalTransaction::withoutTrashed()
            ->where('member_id', $member->id);

        $totalCredits  = (float) (clone $txns)->where('direction', 'credit')->sum('amount');
        $totalDebits   = (float) (clone $txns)->where('direction', 'debit')->sum('amount');
        $txCount       = (clone $txns)->count();
        $lastTx        = (clone $txns)->orderByDesc('transaction_date')->orderByDesc('id')->first();

        return [
            'current_balance'  => (float) $member->share_capital,
            'total_credits'    => round($totalCredits, 2),
            'total_debits'     => round($totalDebits, 2),
            'transaction_count'=> $txCount,
            'last_transaction' => $lastTx?->transaction_date?->toDateString(),
            'last_type'        => $lastTx?->type_label,
        ];
    }

    // ── Aggregate report: all members ────────────────────────

    public function getAggregate(array $filters = []): array
    {
        $memberQuery = Member::query()
            ->whereIn('member_status', ['ACTIVE'])
            ->with('latestShareCapitalTransaction');

        if (!empty($filters['company'])) {
            $memberQuery->where('company', $filters['company']);
        }
        if (!empty($filters['department'])) {
            $memberQuery->where('department', $filters['department']);
        }

        $members = $memberQuery->get(['id', 'member_no', 'first_name', 'last_name',
            'company', 'department', 'share_capital']);

        $rows = $members->map(fn($m) => [
            'member_id'    => $m->id,
            'member_no'    => $m->member_no,
            'member_name'  => "{$m->first_name} {$m->last_name}",
            'company'      => $m->company,
            'department'   => $m->department,
            'balance'      => (float) $m->share_capital,
            'last_tx_date' => $m->latestShareCapitalTransaction?->transaction_date?->toDateString(),
        ])->sortByDesc('balance')->values();

        // Period totals
        $txQuery = ShareCapitalTransaction::withoutTrashed();
        if (!empty($filters['date_from'])) {
            $txQuery->whereDate('transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $txQuery->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        $periodCredits = (float) (clone $txQuery)->where('direction', 'credit')->sum('amount');
        $periodDebits  = (float) (clone $txQuery)->where('direction', 'debit')->sum('amount');

        return [
            'rows'              => $rows,
            'filters'           => $filters,
            'summary' => [
                'total_members'     => $members->count(),
                'total_balance'     => round($members->sum('share_capital'), 2),
                'members_with_zero' => $members->where('share_capital', '<=', 0)->count(),
                'period_credits'    => round($periodCredits, 2),
                'period_debits'     => round($periodDebits, 2),
                'period_net'        => round($periodCredits - $periodDebits, 2),
            ],
        ];
    }

    // ── PDF ledger statement ──────────────────────────────────

    public function generateLedgerPdf(Member $member, array $filters = []): string
    {
        $ledger  = $this->getLedger($member, $filters);
        $summary = $this->getSummary($member);
        $profile = CoopProfile::current();

        $pdf = Pdf::loadView('share_capital.ledger', [
            'member'      => $member,
            'profile'     => $profile,
            'ledger'      => $ledger,
            'summary'     => $summary,
            'filters'     => $filters,
            'printed_by'  => Auth::user()?->name,
            'printed_at'  => now()->format('F d, Y  H:i'),
        ])->setPaper('a4', 'portrait');

        $path = storage_path('app/exports/sc_ledger_' . $member->member_no . '_' . now()->format('Ymd_His') . '.pdf');
        @mkdir(dirname($path), 0755, true);
        $pdf->save($path);
        return $path;
    }
}
