<?php
// app/Console/Commands/SendDueReminders.php
namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature   = 'coop:send-due-reminders';
    protected $description = 'Send SMS/email reminders for payments due in the configured number of days';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Sending due-date reminders...');

        $result = $notificationService->sendDueReminders();

        $this->info("Periods found:               {$result['schedules_found']}");
        $this->info("Notifications dispatched:    {$result['notifications_dispatched']}");

        return Command::SUCCESS;
    }
}
