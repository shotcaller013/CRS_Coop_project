<?php

use Illuminate\Support\Facades\Schedule;

// Detect overdue amortization periods and apply penalties every night at 2:00 AM
Schedule::command('coop:detect-overdue')->dailyAt('02:00');
