<?php
// database/migrations/2026_07_01_000012_add_billed_status_to_amortization_schedules.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add BILLED to the status enum
        // BILLED = period has been included in an issued bill, awaiting company remittance
        DB::statement("
            ALTER TABLE amortization_schedules
            MODIFY COLUMN status
            ENUM('PENDING','PAID','PARTIAL','OVERDUE','VOIDED','BILLED')
            NOT NULL DEFAULT 'PENDING'
        ");

        // Optional convenience FK back to which bill_item covered this period
        // Nullable — not all periods go through billing
        Schema::table('amortization_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('bill_item_id')
                ->nullable()
                ->after('penalty_amount');

            $table->foreign('bill_item_id')
                ->references('id')
                ->on('bill_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('amortization_schedules', function (Blueprint $table) {
            $table->dropForeign(['bill_item_id']);
            $table->dropColumn('bill_item_id');
        });

        DB::statement("
            ALTER TABLE amortization_schedules
            MODIFY COLUMN status
            ENUM('PENDING','PAID','PARTIAL','OVERDUE','VOIDED')
            NOT NULL DEFAULT 'PENDING'
        ");
    }
};
