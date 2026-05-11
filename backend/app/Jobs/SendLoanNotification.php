<?php
// app/Jobs/SendLoanNotification.php
namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\SemaphoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendLoanNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly string  $channel,
        public readonly string  $recipient,
        public readonly string  $recipientName,
        public readonly string  $message,
        public readonly string  $event,
        public readonly ?int    $memberId,
        public readonly ?string $referenceType,
        public readonly ?int    $referenceId,
    ) {}

    public function handle(SemaphoreService $semaphore): void
    {
        // Create the log entry first (status = queued)
        $log = NotificationLog::create([
            'member_id'        => $this->memberId,
            'event'            => $this->event,
            'channel'          => $this->channel,
            'recipient'        => $this->recipient,
            'recipient_name'   => $this->recipientName,
            'message'          => $this->message,
            'status'           => 'queued',
            'reference_type'   => $this->referenceType,
            'reference_id'     => $this->referenceId,
        ]);

        try {
            if ($this->channel === 'sms') {
                $result = $semaphore->send($this->recipient, $this->message);

                NotificationLog::whereKey($log->id)->update([
                    'status'            => $result['success'] ? 'sent' : 'failed',
                    'provider_response' => json_encode($result['raw']),
                    'sent_at'           => $result['success'] ? now() : null,
                    'failed_at'         => $result['success'] ? null : now(),
                    'error_message'     => $result['success'] ? null : ($result['raw']['error'] ?? 'Unknown error'),
                ]);

            } elseif ($this->channel === 'email') {
                // $this->message holds the Blade view name (e.g. "loan_approved")
                // or a plain text string — we detect by whether it contains spaces
                $isView = !str_contains($this->message, ' ');

                if ($isView) {
                    Mail::send(
                        "emails.{$this->message}",
                        ['recipientName' => $this->recipientName],
                        fn($m) => $m->to($this->recipient, $this->recipientName)
                                    ->subject($this->resolveSubject())
                    );
                } else {
                    Mail::raw($this->message, fn($m) =>
                        $m->to($this->recipient, $this->recipientName)
                          ->subject($this->resolveSubject())
                    );
                }

                NotificationLog::whereKey($log->id)->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error("SendLoanNotification failed: {$e->getMessage()}", [
                'channel'  => $this->channel,
                'event'    => $this->event,
                'recipient'=> $this->recipient,
            ]);

            NotificationLog::whereKey($log->id)->update([
                'status'        => 'failed',
                'failed_at'     => now(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e; // re-throw so the queue marks the job as failed for retry
        }
    }

    private function resolveSubject(): string
    {
        return match ($this->event) {
            'loan_approved'   => 'Your loan application has been approved',
            'payment_due'     => 'Loan payment reminder',
            'overdue'         => 'Overdue payment notice',
            'restructured'    => 'Your loan has been restructured',
            'approval_needed' => 'Loan application pending your approval',
            default           => 'CRS Employees Credit Cooperative — Notification',
        };
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendLoanNotification permanently failed", [
            'event'     => $this->event,
            'channel'   => $this->channel,
            'recipient' => $this->recipient,
            'error'     => $e->getMessage(),
        ]);
    }
}
