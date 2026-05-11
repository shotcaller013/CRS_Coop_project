<?php

use Illuminate\Support\Facades\Schedule;

// Detect overdue amortization periods and apply penalties every night at 2:00 AM
Schedule::command('coop:detect-overdue')->dailyAt('02:00');

// Send due-date reminders to members at 8:00 AM
Schedule::command('coop:send-due-reminders')->dailyAt('08:00');
