<?php
// database/migrations/2026_05_01_000002_create_notification_logs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            // The event that triggered this notification
            $table->string('event', 60);
            // loan_approved | payment_due | payment_received |
            // overdue | loan_closed | restructured | approval_needed

            // Channel
            $table->enum('channel', ['sms', 'email']);

            // Who received it
            $table->string('recipient', 200)->nullable(); // phone or email
            $table->string('recipient_name', 200)->nullable();

            // What was sent
            $table->text('message')->nullable();

            // Delivery status
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->text('provider_response')->nullable(); // raw JSON from Semaphore/SMTP
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();

            // What it's about (polymorphic)
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Immutable log
            $table->timestamp('created_at')->useCurrent();

            $table->index(['member_id', 'event'],             'idx_notif_member_event');
            $table->index('status',                            'idx_notif_status');
            $table->index('created_at',                        'idx_notif_date');
            $table->index(['reference_type', 'reference_id'], 'idx_notif_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
