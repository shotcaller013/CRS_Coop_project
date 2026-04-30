<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_no', 30)->unique();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('loan_type_id');
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('term_months');
            $table->enum('frequency', ['monthly', 'bimonthly', 'weekly'])->default('bimonthly');
            $table->decimal('annual_rate', 6, 4);
            $table->text('purpose')->nullable();
            $table->unsignedBigInteger('co_maker_1_id')->nullable();
            $table->unsignedBigInteger('co_maker_2_id')->nullable();
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'ACTIVE', 'CLOSED', 'REJECTED'])->default('DRAFT');
            $table->decimal('total_payment', 14, 2)->nullable();
            $table->decimal('total_interest', 14, 2)->nullable();
            $table->unsignedInteger('n_periods')->nullable();
            $table->decimal('first_payment_amt', 12, 2)->nullable();
            $table->decimal('last_payment_amt', 12, 2)->nullable();
            $table->date('application_date')->nullable();
            $table->date('approval_date')->nullable();
            $table->date('first_due_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('approved_by_hr', 150)->nullable();
            $table->string('approved_by_coop', 150)->nullable();
            $table->string('signed_form_url', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('member_id')->references('id')->on('members');
            $table->foreign('loan_type_id')->references('id')->on('loan_types');
            $table->foreign('co_maker_1_id')->references('id')->on('members')->nullOnDelete();
            $table->foreign('co_maker_2_id')->references('id')->on('members')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('member_id');
            $table->index('status');
            $table->index('loan_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
