<?php
// database/migrations/2026_07_01_000010_create_bills_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no', 30)->unique();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->enum('status', ['DRAFT','ISSUED','PARTIAL','SETTLED','CANCELLED'])
                ->default('DRAFT');

            $table->date('billing_period_start');
            $table->date('billing_period_end');

            // Financials
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_remitted', 12, 2)->default(0);

            // Timestamps
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('settled_at')->nullable();

            // Who prepared this bill
            $table->foreignId('prepared_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id',           'idx_bills_company');
            $table->index('status',               'idx_bills_status');
            $table->index('billing_period_start', 'idx_bills_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
