<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amortization_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedInteger('period_no');
            $table->date('due_date')->nullable();
            $table->decimal('principal', 12, 2);
            $table->decimal('interest', 12, 2);
            $table->decimal('amount_due', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->enum('status', ['PENDING', 'PAID', 'PARTIAL', 'OVERDUE'])->default('PENDING');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->string('or_number', 50)->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->cascadeOnDelete();
            $table->index(['loan_id', 'period_no']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amortization_schedules');
    }
};
