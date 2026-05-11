<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE amortization_schedules MODIFY COLUMN status ENUM('PENDING','PAID','PARTIAL','OVERDUE','VOIDED') NOT NULL DEFAULT 'PENDING'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE amortization_schedules MODIFY COLUMN status ENUM('PENDING','PAID','PARTIAL','OVERDUE') NOT NULL DEFAULT 'PENDING'");
    }
};
