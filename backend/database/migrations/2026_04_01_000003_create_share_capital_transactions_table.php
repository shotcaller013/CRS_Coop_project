<?php
// database/migrations/2026_04_01_000003_create_share_capital_transactions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_capital_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            // Transaction classification
            $table->enum('type', [
                'opening',      // Initial balance when a member joins
                'deposit',      // Regular monthly contribution
                'withdrawal',   // Member withdraws portion (resignation/retirement)
                'dividend',     // Annual dividend credited to share capital
                'adjustment',   // Manual correction — requires remarks
            ]);

            // credit = adds to balance, debit = subtracts from balance
            $table->enum('direction', ['credit', 'debit']);

            // Always positive — direction handles the sign
            $table->decimal('amount', 12, 2);

            // Running balance snapshot computed at write time — never manually edited
            // This makes the ledger self-verifiable: balance_after on row N
            // must equal balance_after on row N-1 ± amount
            $table->decimal('balance_after', 12, 2);

            $table->string('or_number', 50)->nullable();         // O.R. for deposits/withdrawals
            $table->date('transaction_date');                     // Allows back-dating for corrections
            $table->string('remarks', 500)->nullable();           // Required for adjustments
            $table->string('reference_no', 100)->nullable();      // Optional external reference

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('member_id',         'idx_sc_member');
            $table->index('transaction_date',  'idx_sc_date');
            $table->index('type',              'idx_sc_type');
            $table->index(['member_id', 'created_at'], 'idx_sc_member_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_capital_transactions');
    }
};
