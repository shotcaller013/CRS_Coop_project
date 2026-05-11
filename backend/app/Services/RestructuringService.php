<?php
// app/Services/RestructuringService.php
namespace App\Services;

use App\Models\AmortizationSchedule;
use App\Models\Loan;
use App\Models\LoanRestructuring;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestructuringService
{
    // ── Guard checks ─────────────────────────────────────────

    /**
     * Validate that this loan can be restructured.
     * Throws InvalidArgumentException with a user-facing message if not.
     */
    public function assertCanRestructure(Loan $loan): void
    {
        if ($loan->status !== 'ACTIVE') {
            throw new \InvalidArgumentException(
                "Only ACTIVE loans can be restructured. This loan is {$loan->status}."
            );
        }

        $remaining = $loan->remaining_balance;
        if ($remaining <= 0) {
            throw new \InvalidArgumentException(
                'This loan has no outstanding balance. Nothing to restructure.'
            );
        }
    }

    // ── Preview (dry-run, no DB writes) ──────────────────────

    /**
     * Compute what the new schedule would look like without saving anything.
     * Returns the new schedule array + summary comparison.
     */
    public function preview(Loan $loan, array $params): array
    {
        $this->assertCanRestructure($loan);

        $newSchedule = $this->computeSchedule(
            amount:      (float) $params['new_amount'],
            termMonths:  (int)   $params['new_term_months'],
            annualRate:  (float) $params['new_annual_rate'],
            frequency:   $params['new_frequency'],
            firstDueDate:$params['new_first_due_date'],
        );

        $oldRemaining = $loan->remaining_balance;
        $oldPending   = $loan->amortizationSchedules()
            ->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
            ->count();

        return [
            'can_restructure'    => true,
            'old' => [
                'remaining_balance' => round($oldRemaining, 2),
                'periods_remaining' => $oldPending,
                'annual_rate'       => $loan->annual_rate,
                'frequency'         => $loan->frequency,
                'term_months'       => $loan->term_months,
            ],
            'new' => [
                'amount'            => (float) $params['new_amount'],
                'term_months'       => (int)   $params['new_term_months'],
                'annual_rate'       => (float) $params['new_annual_rate'],
                'frequency'         => $params['new_frequency'],
                'first_due_date'    => $params['new_first_due_date'],
                'n_periods'         => count($newSchedule),
                'total_payment'     => round(array_sum(array_column($newSchedule, 'amount_due')), 2),
                'total_interest'    => round(array_sum(array_column($newSchedule, 'interest')), 2),
                'first_payment'     => round($newSchedule[0]['amount_due'] ?? 0, 2),
                'last_payment'      => round(end($newSchedule)['amount_due'] ?? 0, 2),
            ],
            'schedule_preview'   => array_slice($newSchedule, 0, 6), // first 6 periods for UI
            'schedule_full'      => $newSchedule,
        ];
    }

    // ── Execute ───────────────────────────────────────────────

    /**
     * Execute the restructuring:
     *  1. Void all remaining PENDING / PARTIAL / OVERDUE periods
     *  2. Generate new schedule
     *  3. Update loan totals and key fields
     *  4. Log the restructuring record
     *
     * All done in a single DB transaction — if anything fails, nothing is saved.
     */
    public function execute(Loan $loan, array $params): LoanRestructuring
    {
        $this->assertCanRestructure($loan);

        return DB::transaction(function () use ($loan, $params) {
            $user = Auth::user();

            // Snapshot old terms before any changes
            $oldBalance  = round($loan->remaining_balance, 2);
            $oldPending  = $loan->amortizationSchedules()
                ->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
                ->count();
            $oldRate     = $loan->annual_rate;
            $oldTerm     = $loan->term_months;
            $oldFreq     = $loan->frequency;

            // Step 1 — void remaining periods (mark as VOIDED, do not delete)
            $voided = $loan->amortizationSchedules()
                ->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
                ->update([
                    'status'     => 'VOIDED',
                    'updated_at' => now(),
                ]);

            // Step 2 — generate new schedule
            $newSchedule = $this->computeSchedule(
                amount:      (float) $params['new_amount'],
                termMonths:  (int)   $params['new_term_months'],
                annualRate:  (float) $params['new_annual_rate'],
                frequency:   $params['new_frequency'],
                firstDueDate:$params['new_first_due_date'],
            );

            // Step 3 — insert new schedule rows
            $now = now();
            $rows = [];
            foreach ($newSchedule as $period) {
                $rows[] = array_merge($period, [
                    'loan_id'    => $loan->id,
                    'status'     => 'PENDING',
                    'paid_amount'=> 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            AmortizationSchedule::insert($rows);

            // Step 4 — update loan record
            $nPeriods       = count($newSchedule);
            $totalPayment   = round(array_sum(array_column($newSchedule, 'amount_due')), 2);
            $totalInterest  = round(array_sum(array_column($newSchedule, 'interest')), 2);

            $loan->update([
                'amount'            => $params['new_amount'],
                'term_months'       => $params['new_term_months'],
                'annual_rate'       => $params['new_annual_rate'],
                'frequency'         => $params['new_frequency'],
                'first_due_date'    => $newSchedule[0]['due_date'],
                'end_date'          => end($newSchedule)['due_date'],
                'n_periods'         => $nPeriods,
                'total_payment'     => $totalPayment,
                'total_interest'    => $totalInterest,
                'first_payment_amt' => $newSchedule[0]['amount_due'],
                'last_payment_amt'  => end($newSchedule)['amount_due'],
            ]);

            // Step 5 — log restructuring record
            $restructuringNo = $this->generateRestructuringNo();
            return LoanRestructuring::create([
                'loan_id'               => $loan->id,
                'restructuring_no'      => $restructuringNo,
                'effective_date'        => $params['effective_date'],

                'old_remaining_balance' => $oldBalance,
                'old_term_months'       => $oldTerm,
                'old_annual_rate'       => $oldRate,
                'old_frequency'         => $oldFreq,
                'old_periods_remaining' => $oldPending,

                'new_amount'            => $params['new_amount'],
                'new_term_months'       => $params['new_term_months'],
                'new_annual_rate'       => $params['new_annual_rate'],
                'new_frequency'         => $params['new_frequency'],
                'new_first_due_date'    => $params['new_first_due_date'],
                'new_n_periods'         => $nPeriods,
                'new_total_payment'     => $totalPayment,
                'new_total_interest'    => $totalInterest,

                'periods_voided'        => $voided,
                'reason'                => $params['reason'],
                'approved_by'           => $user?->id,
                'approved_by_name'      => $user?->name,
                'created_at'            => $now,
            ]);
        });
    }

    // ── Schedule computation ─────────────────────────────────

    /**
     * Diminishing-balance schedule — same algorithm as LoanService::computeSchedule.
     * Frequency factor: MONTHLY=1.0, BI-MONTHLY=0.5, WEEKLY=0.25
     */
    public function computeSchedule(
        float  $amount,
        int    $termMonths,
        float  $annualRate,
        string $frequency,
        string $firstDueDate
    ): array {
        $factor = match (strtoupper($frequency)) {
            'BI-MONTHLY', 'BIMONTHLY' => 0.5,
            'WEEKLY'                  => 0.25,
            default                   => 1.0,
        };

        $nPeriods = match (strtoupper($frequency)) {
            'BI-MONTHLY', 'BIMONTHLY' => $termMonths * 2,
            'WEEKLY'                  => $termMonths * 4,
            default                   => $termMonths,
        };

        $principalPerPeriod = round($amount / $nPeriods, 4);
        $monthlyRate        = ($annualRate / 100) / 12;
        $balance            = $amount;
        $schedule           = [];
        $dueDate            = Carbon::parse($firstDueDate);

        for ($i = 1; $i <= $nPeriods; $i++) {
            $interest    = round($balance * $monthlyRate * $factor, 2);
            $principal   = ($i === $nPeriods)
                ? round($balance, 2) // last period gets the remaining balance
                : round($principalPerPeriod, 2);
            $amountDue   = round($principal + $interest, 2);
            $balance     = round($balance - $principal, 4);

            $schedule[] = [
                'period_no'  => $i,
                'due_date'   => $dueDate->toDateString(),
                'principal'  => $principal,
                'interest'   => $interest,
                'amount_due' => $amountDue,
                'balance'    => max(0, round($balance, 2)),
            ];

            // Advance due date by frequency
            $dueDate = match (strtoupper($frequency)) {
                'BI-MONTHLY', 'BIMONTHLY' => $dueDate->copy()->addDays(15),
                'WEEKLY'                  => $dueDate->copy()->addWeek(),
                default                   => $dueDate->copy()->addMonth(),
            };
        }

        return $schedule;
    }

    // ── Number generator ─────────────────────────────────────

    private function generateRestructuringNo(): string
    {
        $year = now()->year;
        $last = LoanRestructuring::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('restructuring_no');

        if ($last) {
            $seq = (int) substr($last, -5) + 1;
        } else {
            $seq = 1;
        }

        return sprintf('RS-%04d-%05d', $year, $seq);
    }
}
