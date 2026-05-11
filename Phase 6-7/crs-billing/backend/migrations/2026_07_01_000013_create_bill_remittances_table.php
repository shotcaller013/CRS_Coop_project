<?php
// database/migrations/2026_07_01_000013_create_bill_remittances_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_remittances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bill_id')
                ->constrained('bills')
                ->cascadeOnDelete();

            $table->string('or_number', 100)->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('remittance_date');
            $table->string('file_path', 500)->nullable(); // uploaded remittance file
            $table->string('notes', 500)->nullable();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index('bill_id', 'idx_remit_bill');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_remittances');
    }
};
