<?php
// database/migrations/2026_07_01_000011_create_bill_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bill_id')
                ->constrained('bills')
                ->cascadeOnDelete();

            // The amortization period being billed
            $table->foreignId('schedule_id')
                ->constrained('amortization_schedules')
                ->restrictOnDelete();

            // Denormalized for fast display without joins
            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();
            $table->foreignId('loan_id')
                ->constrained('loans')
                ->restrictOnDelete();

            // Snapshot of amount at billing time
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);

            $table->enum('status', ['PENDING', 'PAID'])->default('PENDING');

            $table->timestamps();

            // A schedule period can only appear on one bill
            $table->unique('schedule_id', 'uq_bill_schedule');
            $table->index('bill_id',   'idx_bi_bill');
            $table->index('member_id', 'idx_bi_member');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
