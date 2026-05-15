<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_portal_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('username', 80)->unique();
            $table->string('email', 150)->nullable();
            $table->string('password_hash');
            $table->boolean('force_password_change')->default(true);
            $table->json('modules_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('member_id', 'idx_mpa_member');
            $table->index('email', 'idx_mpa_email');
            $table->index('is_active', 'idx_mpa_active');
        });

        Schema::create('member_portal_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('account_id')->references('id')->on('member_portal_accounts')->onDelete('cascade');
            $table->index('account_id', 'idx_mps_account');
            $table->index('expires_at', 'idx_mps_expiry');
        });

        Schema::create('member_portal_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('action', 80);
            $table->string('detail', 500)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('account_id')->references('id')->on('member_portal_accounts')->onDelete('set null');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
            $table->index('account_id', 'idx_mpal_account');
            $table->index('member_id', 'idx_mpal_member');
            $table->index('created_at', 'idx_mpal_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_portal_audit_logs');
        Schema::dropIfExists('member_portal_sessions');
        Schema::dropIfExists('member_portal_accounts');
    }
};
