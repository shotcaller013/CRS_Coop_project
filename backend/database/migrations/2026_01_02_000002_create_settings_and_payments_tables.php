<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Cooperative profile (single-row config) ──────────
        if (!Schema::hasTable('coop_profile')) {
            Schema::create('coop_profile', function (Blueprint $table) {
                $table->id();
                $table->string('name', 200);
                $table->string('cda_reg_no', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('contact', 30)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('website', 150)->nullable();
                $table->string('hr_signatory', 150)->nullable();
                $table->string('coop_signatory', 150)->nullable();
                $table->string('logo_url', 255)->nullable();
                $table->string('fiscal_year_start', 20)->default('January');
                $table->timestamps();
            });
        }

        // ── Companies ────────────────────────────────────────
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->string('code', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Approval thresholds (per loan type) ──────────────
        Schema::create('approval_thresholds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_type_id');
            $table->string('level', 30);            // manager, board
            $table->string('approver_role', 50);    // Spatie role name
            $table->decimal('amount_from', 12, 2)->default(0);
            $table->decimal('amount_to', 12, 2)->nullable(); // null = no ceiling
            $table->unsignedInteger('sequence');    // 1 = first approver, 2 = second
            $table->timestamps();

            $table->foreign('loan_type_id')->references('id')->on('loan_types')->cascadeOnDelete();
            $table->index(['loan_type_id', 'sequence']);
        });

        // ── Loan approvals log (replaces approved_by_hr/coop) ─
        Schema::create('loan_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->string('level', 30);
            $table->unsignedInteger('sequence');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('approver_name', 150)->nullable();
            $table->enum('decision', ['approved', 'rejected', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->cascadeOnDelete();
            $table->foreign('approver_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['loan_id', 'sequence']);
        });

        // ── System preferences (key-value store) ─────────────
        if (!Schema::hasTable('system_preferences')) {
            Schema::create('system_preferences', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('group', 50)->default('general');
                $table->string('description', 255)->nullable();
                $table->timestamps();
                $table->index('group');
            });
        }

        // ── Payments (amortization period payments) ───────────
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('schedule_id');  // FK → amortization_schedules
            $table->decimal('amount_paid', 12, 2);
            $table->enum('payment_type', ['full', 'partial', 'advance', 'penalty']);
            $table->string('or_number', 50)->nullable();
            $table->date('payment_date');
            $table->decimal('penalty_paid', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('received_by');
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans');
            $table->foreign('schedule_id')->references('id')->on('amortization_schedules');
            $table->foreign('received_by')->references('id')->on('users');
            $table->index(['loan_id', 'payment_date']);
            $table->index('or_number');
        });

        // ── Add penalty columns to amortization_schedules ─────
        if (!Schema::hasColumn('amortization_schedules', 'penalty_amount')) {
            Schema::table('amortization_schedules', function (Blueprint $table) {
                $table->decimal('penalty_amount', 12, 2)->default(0)->after('paid_amount');
                $table->integer('days_overdue')->default(0)->after('penalty_amount');
            });
        }
    }

    public function down(): void
    {
        Schema::table('amortization_schedules', function (Blueprint $table) {
            $table->dropColumn(['penalty_amount', 'days_overdue']);
        });
        Schema::dropIfExists('payments');
        Schema::dropIfExists('system_preferences');
        Schema::dropIfExists('loan_approvals');
        Schema::dropIfExists('approval_thresholds');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('coop_profile');
    }
};
