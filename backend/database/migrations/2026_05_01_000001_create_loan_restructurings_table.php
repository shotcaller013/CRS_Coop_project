<?php
// database/migrations/2026_05_01_000001_create_loan_restructurings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_restructurings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')
                ->constrained('loans')
                ->cascadeOnDelete();

            // Human-readable reference — RS-YYYY-NNNNN
            $table->string('restructuring_no', 30)->unique();

            $table->date('effective_date');

            // ── Old terms snapshot ───────────────────────
            $table->decimal('old_remaining_balance', 12, 2)->nullable();
            $table->integer('old_term_months')->nullable();
            $table->decimal('old_annual_rate', 6, 4)->nullable();
            $table->string('old_frequency', 20)->nullable();
            $table->integer('old_periods_remaining')->nullable();

            // ── New terms ────────────────────────────────
            $table->decimal('new_amount', 12, 2);
            $table->integer('new_term_months');
            $table->decimal('new_annual_rate', 6, 4);
            $table->string('new_frequency', 20);
            $table->date('new_first_due_date');

            // ── Computed totals (new schedule) ───────────
            $table->decimal('new_n_periods', 8, 0)->nullable();
            $table->decimal('new_total_payment', 12, 2)->nullable();
            $table->decimal('new_total_interest', 12, 2)->nullable();

            // ── What happened ────────────────────────────
            $table->integer('periods_voided')->default(0);
            $table->string('reason', 500);

            // ── Who approved ─────────────────────────────
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('approved_by_name', 200)->nullable(); // snapshot

            // Immutable log — no updated_at, no soft-delete
            $table->timestamp('created_at')->useCurrent();

            $table->index('loan_id',           'idx_restr_loan');
            $table->index('restructuring_no',  'idx_restr_no');
            $table->index('effective_date',    'idx_restr_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_restructurings');
    }
};
