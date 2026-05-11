<?php
// app/Services/NotificationService.php
namespace App\Services;

use App\Jobs\SendLoanNotification;
use App\Models\AmortizationSchedule;
use App\Models\Loan;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\SystemPreference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    // ── Preference helpers ────────────────────────────────────

    /**
     * Check whether a notification channel is enabled for an event.
     * Falls back to 'true' if the key is not in system_preferences.
     */
    public function isEnabled(string $event, string $channel): bool
    {
        $key = "notify_{$event}_{$channel}"; // e.g. notify_loan_approved_sms
        $val = SystemPreference::get($key, 'true');
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * How many days before due date should reminders fire?
     */
    public function reminderDaysBefore(): int
    {
        return (int) SystemPreference::get('notify_reminder_days_before', 3);
    }

    // ── Dispatch helpers (fire and forget via queue) ──────────

    public function loanApproved(Loan $loan): void
    {
        $this->dispatch('loan_approved', $loan->member, $loan, [
            'loan_no'   => $loan->loan_no,
            'amount'    => number_format($loan->amount, 2),
            'term'      => $loan->term_months,
            'frequency' => $loan->frequency,
            'first_due' => $loan->first_due_date,
        ]);
    }

    public function paymentReceived(Payment $payment): void
    {
        $loan   = $payment->loan;
        $member = $loan->member;
        $this->dispatch('payment_received', $member, $payment, [
            'loan_no'     => $loan->loan_no,
            'amount_paid' => number_format($payment->amount_paid, 2),
            'or_number'   => $payment->or_number,
            'balance'     => number_format($payment->balance_after, 2),
            'date'        => $payment->payment_date,
        ], channels: ['sms']); // SMS only for payment confirmation
    }

    public function loanClosed(Loan $loan): void
    {
        $this->dispatch('loan_closed', $loan->member, $loan, [
            'loan_no' => $loan->loan_no,
            'amount'  => number_format($loan->amount, 2),
        ], channels: ['sms']); // SMS only
    }

    public function overdueAlert(AmortizationSchedule $schedule): void
    {
        $loan   = $schedule->loan;
        $member = $loan->member;
        $this->dispatch('overdue', $member, $schedule, [
            'loan_no'      => $loan->loan_no,
            'period_no'    => $schedule->period_no,
            'due_date'     => $schedule->due_date?->format('M d, Y'),
            'amount_due'   => number_format($schedule->amount_due, 2),
            'days_overdue' => $schedule->days_overdue,
            'penalty'      => number_format($schedule->penalty_amount, 2),
        ]);
    }

    public function loanRestructured(Loan $loan, \App\Models\LoanRestructuring $record): void
    {
        $this->dispatch('restructured', $loan->member, $record, [
            'loan_no'         => $loan->loan_no,
            'restructuring_no'=> $record->restructuring_no,
            'new_amount'      => number_format($record->new_amount, 2),
            'new_term'        => $record->new_term_months,
            'new_rate'        => $record->new_annual_rate,
            'new_first_due'   => $record->new_first_due_date?->format('M d, Y'),
        ]);
    }

    /**
     * Email the manager (not the member) when a loan needs approval.
     */
    public function approvalNeeded(Loan $loan): void
    {
        if (!$this->isEnabled('approval_needed', 'email')) return;

        $managers = \App\Models\User::whereHas('roles', fn($q) =>
            $q->whereIn('name', ['manager', 'super-admin'])
        )->whereNotNull('email')->get();

        foreach ($managers as $manager) {
            $message = $this->renderTemplate('approval_needed', 'email', [
                'manager_name' => $manager->name,
                'loan_no'      => $loan->loan_no,
                'member_name'  => $loan->member->full_name ?? "{$loan->member->first_name} {$loan->member->last_name}",
                'amount'       => number_format($loan->amount, 2),
                'loan_type'    => $loan->loanType?->label,
            ]);

            SendLoanNotification::dispatch(
                channel:       'email',
                recipient:     $manager->email,
                recipientName: $manager->name,
                message:       $message,
                event:         'approval_needed',
                memberId:      null,
                referenceType: Loan::class,
                referenceId:   $loan->id,
            );
        }
    }

    // ── Due date reminders (called by nightly scheduler) ─────

    /**
     * Find all amortization periods due in exactly N days and send reminders.
     * Called by the SendDueReminders artisan command.
     */
    public function sendDueReminders(): array
    {
        $daysBefore = $this->reminderDaysBefore();
        $targetDate = Carbon::today()->addDays($daysBefore)->toDateString();

        $schedules = AmortizationSchedule::with(['loan.member', 'loan'])
            ->where('status', 'PENDING')
            ->whereDate('due_date', $targetDate)
            ->whereHas('loan', fn($q) => $q->where('status', 'ACTIVE'))
            ->get();

        $sent = 0;
        foreach ($schedules as $schedule) {
            $loan   = $schedule->loan;
            $member = $loan->member;

            $this->dispatch('payment_due', $member, $schedule, [
                'loan_no'    => $loan->loan_no,
                'period_no'  => $schedule->period_no,
                'due_date'   => $schedule->due_date?->format('M d, Y'),
                'amount_due' => number_format($schedule->amount_due, 2),
                'days_away'  => $daysBefore,
            ]);
            $sent++;
        }

        return ['schedules_found' => $schedules->count(), 'notifications_dispatched' => $sent];
    }

    // ── Core dispatch ─────────────────────────────────────────

    private function dispatch(
        string  $event,
        Member  $member,
        object  $reference,
        array   $vars,
        array   $channels = ['sms', 'email']
    ): void {
        foreach ($channels as $channel) {
            if (!$this->isEnabled($event, $channel)) continue;

            $recipient = match ($channel) {
                'sms'   => $member->contact_number ?? null,
                'email' => $member->email ?? null,
                default => null,
            };

            if (!$recipient) continue;

            $message = $this->renderTemplate($event, $channel, array_merge($vars, [
                'member_name'  => "{$member->first_name} {$member->last_name}",
                'member_no'    => $member->member_no,
                'coop_name'    => config('semaphore.coop_name', 'CRS Employees Credit Cooperative'),
            ]));

            SendLoanNotification::dispatch(
                channel:       $channel,
                recipient:     $recipient,
                recipientName: "{$member->first_name} {$member->last_name}",
                message:       $message,
                event:         $event,
                memberId:      $member->id,
                referenceType: get_class($reference),
                referenceId:   $reference->id,
            );
        }
    }

    // ── Template engine ───────────────────────────────────────

    /**
     * Render an SMS or email subject+body from system_preferences templates.
     * Template keys: notify_template_{event}_{channel}
     * Variables: {member_name}, {loan_no}, {amount}, etc. — wrapped in curly braces.
     */
    public function renderTemplate(string $event, string $channel, array $vars): string
    {
        $defaultTemplates = $this->defaultTemplates();
        $key      = "notify_template_{$event}_{$channel}";
        $template = SystemPreference::get($key, $defaultTemplates[$event][$channel] ?? '');

        foreach ($vars as $k => $v) {
            $template = str_replace('{' . $k . '}', (string) $v, $template);
        }

        return $template;
    }

    // ── Default templates ─────────────────────────────────────

    public function defaultTemplates(): array
    {
        $coop = config('semaphore.coop_name', 'CRS ECCO');
        return [
            'loan_approved' => [
                'sms'   => "Hi {member_name}, your loan application ({loan_no}) for ₱{amount} has been APPROVED. First payment of ₱{first_payment_amt} is due on {first_due}. - {coop_name}",
                'email' => "loan_approved",  // Blade view name
            ],
            'payment_due' => [
                'sms'   => "Reminder: Your loan payment of ₱{amount_due} for {loan_no} is due on {due_date} ({days_away} days away). Please ensure timely payment. - {coop_name}",
                'email' => "payment_due",
            ],
            'payment_received' => [
                'sms'   => "Payment received: ₱{amount_paid} for {loan_no} (O.R. #{or_number}). Remaining balance: ₱{balance}. Thank you! - {coop_name}",
            ],
            'overdue' => [
                'sms'   => "NOTICE: Your payment for {loan_no} (Period {period_no}, due {due_date}) is {days_overdue} day(s) overdue. Outstanding: ₱{amount_due} + penalty ₱{penalty}. Please pay immediately. - {coop_name}",
                'email' => "overdue",
            ],
            'loan_closed' => [
                'sms'   => "Congratulations {member_name}! Your loan {loan_no} has been fully paid. Thank you for your timely payments. - {coop_name}",
            ],
            'restructured' => [
                'sms'   => "Your loan {loan_no} has been restructured ({restructuring_no}). New amount: ₱{new_amount}, {new_term} months at {new_rate}% p.a. First payment due: {new_first_due}. - {coop_name}",
                'email' => "restructured",
            ],
            'approval_needed' => [
                'email' => "approval_needed",
            ],
        ];
    }
}
