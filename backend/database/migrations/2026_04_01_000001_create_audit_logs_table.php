<?php
// database/migrations/2026_04_01_000001_create_audit_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who
            $table->unsignedBigInteger('user_id')->nullable(); // null = system (scheduler, artisan)
            $table->string('user_name', 200)->nullable();      // snapshot — user may be deleted later
            $table->string('user_role', 100)->nullable();      // snapshot of role at time of action

            // What was touched
            $table->string('auditable_type', 100);             // App\Models\Member, Loan, Payment…
            $table->unsignedBigInteger('auditable_id');
            $table->string('auditable_label', 200)->nullable(); // human label e.g. "LN-2026-00012"

            // What happened
            $table->string('event', 30);                       // created | updated | deleted | restored
            $table->json('old_values')->nullable();            // before snapshot (null on created)
            $table->json('new_values')->nullable();            // after snapshot  (null on deleted)
            $table->json('dirty_keys')->nullable();            // array of changed keys only

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('url', 1000)->nullable();           // request path at time of change
            $table->string('tags', 200)->nullable();           // optional free-text tag e.g. "bulk-import"

            $table->timestamp('created_at')->useCurrent();

            // Indexes — no updated_at, audit rows are immutable
            $table->index(['auditable_type', 'auditable_id'], 'idx_auditable');
            $table->index('user_id',   'idx_audit_user');
            $table->index('event',     'idx_audit_event');
            $table->index('created_at','idx_audit_date');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
