<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coop_profile', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('CRS Cooperative');
            $table->string('cda_reg_no')->nullable();
            $table->string('address')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('hr_signatory')->nullable();
            $table->string('coop_signatory')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('fiscal_year_start')->nullable();
            $table->timestamps();
        });

        Schema::create('system_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_preferences');
        Schema::dropIfExists('coop_profile');
    }
};
