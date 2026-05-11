<?php
// database/migrations/2026_04_01_000002_create_member_beneficiaries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_beneficiaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            // Primary = entitled to death/insurance benefit
            // Secondary = contingent — receives only if no primary beneficiary survives
            $table->enum('type', ['primary', 'secondary'])->default('primary');

            // Identity
            $table->string('first_name', 100);
            $table->string('last_name',  100);
            $table->string('middle_name', 100)->nullable();
            $table->string('relationship', 60); // Spouse | Child | Parent | Sibling | Other
            $table->date('birthdate')->nullable();

            // CDA-required: percentage share must total 100% across all primary beneficiaries
            // Set to null for secondary beneficiaries (they share equally if triggered)
            $table->decimal('share_percentage', 5, 2)->nullable();

            // Contact
            $table->string('contact_number', 20)->nullable();
            $table->string('address', 300)->nullable();

            // Minor beneficiary fields (required when age < 18 at declaration time)
            $table->string('guardian_name',    200)->nullable();
            $table->string('guardian_contact',  20)->nullable();
            $table->string('guardian_relationship', 60)->nullable();

            // Display order within type group
            $table->tinyInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('member_id', 'idx_ben_member');
            $table->index('type',      'idx_ben_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_beneficiaries');
    }
};
