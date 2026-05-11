<?php
// database/migrations/2026_07_01_000001_add_performance_indexes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── amortization_schedules ─────────────────────────────────────
        // Heaviest table — drives aging report, overdue detection, collections
        Schema::table('amortization_schedules', function (Blueprint $table) {
            // Overdue detection job: WHERE status='OVERDUE' AND loan_id IN (ACTIVE loans)
            if (!$this->indexExists('amortization_schedules', 'idx_as_status_due')) {
                $table->index(['status', 'due_date'], 'idx_as_status_due');
            }
            // Collection report: GROUP BY month(due_date)
            if (!$this->indexExists('amortization_schedules', 'idx_as_due_date')) {
                $table->index('due_date', 'idx_as_due_date');
            }
            // Payment recording: WHERE loan_id + status
            if (!$this->indexExists('amortization_schedules', 'idx_as_loan_status')) {
                $table->index(['loan_id', 'status'], 'idx_as_loan_status');
            }
        });

        // ── loans ──────────────────────────────────────────────────────
        Schema::table('loans', function (Blueprint $table) {
            // Dashboard disbursements: WHERE application_date BETWEEN
            if (!$this->indexExists('loans', 'idx_loans_app_date')) {
                $table->index('application_date', 'idx_loans_app_date');
            }
            // Status filter on every list view
            if (!$this->indexExists('loans', 'idx_loans_status_member')) {
                $table->index(['status', 'member_id'], 'idx_loans_status_member');
            }
        });

        // ── payments ──────────────────────────────────────────────────
        Schema::table('payments', function (Blueprint $table) {
            // Payment history per loan
            if (!$this->indexExists('payments', 'idx_payments_loan_date')) {
                $table->index(['loan_id', 'payment_date'], 'idx_payments_loan_date');
            }
        });

        // ── notification_logs ─────────────────────────────────────────
        Schema::table('notification_logs', function (Blueprint $table) {
            // Log viewer filters by status + channel
            if (!$this->indexExists('notification_logs', 'idx_notif_status_ch')) {
                $table->index(['status', 'channel'], 'idx_notif_status_ch');
            }
        });

        // ── audit_logs ────────────────────────────────────────────────
        Schema::table('audit_logs', function (Blueprint $table) {
            // Dashboard activity feed: ORDER BY created_at DESC LIMIT 8
            if (!$this->indexExists('audit_logs', 'idx_audit_created')) {
                $table->index('created_at', 'idx_audit_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('amortization_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_as_status_due');
            $table->dropIndex('idx_as_due_date');
            $table->dropIndex('idx_as_loan_status');
        });
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('idx_loans_app_date');
            $table->dropIndex('idx_loans_status_member');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_loan_date');
        });
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropIndex('idx_notif_status_ch');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_created');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(\DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($index);
    }
};
