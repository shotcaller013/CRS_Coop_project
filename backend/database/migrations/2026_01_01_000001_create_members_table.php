<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_no', 20)->unique();
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('contact', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('company', 200)->nullable();
            $table->string('branch', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->enum('status', ['REGULAR', 'PROBI', 'SUSPENDED', 'INACTIVE'])->default('PROBI');
            $table->string('position', 150)->nullable();
            $table->string('supervisor', 150)->nullable();
            $table->date('date_hired')->nullable();
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->decimal('share_capital', 12, 2)->default(0);
            $table->enum('member_status', ['ACTIVE', 'INACTIVE', 'RESIGNED'])->default('ACTIVE');
            $table->string('photo_url', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('member_no');
            $table->index('member_status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
