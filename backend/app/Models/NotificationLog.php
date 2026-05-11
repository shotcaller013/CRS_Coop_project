<?php
// app/Models/NotificationLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    public $timestamps = false;
    const CREATED_AT   = 'created_at';

    protected $fillable = [
        'member_id', 'event', 'channel', 'recipient', 'recipient_name',
        'message', 'status', 'provider_response',
        'sent_at', 'failed_at', 'error_message',
        'reference_type', 'reference_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'sent_at'    => 'datetime',
        'failed_at'  => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    // ── Computed ─────────────────────────────────────────────

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'loan_approved'      => 'Loan approved',
            'payment_due'        => 'Payment due reminder',
            'payment_received'   => 'Payment received',
            'overdue'            => 'Overdue alert',
            'loan_closed'        => 'Loan fully paid',
            'restructured'       => 'Loan restructured',
            'approval_needed'    => 'Approval needed',
            default              => ucwords(str_replace('_', ' ', $this->event)),
        };
    }

    // Convenience scopes
    public function scopeSent($q)   { return $q->where('status', 'sent'); }
    public function scopeFailed($q) { return $q->where('status', 'failed'); }
    public function scopeSms($q)    { return $q->where('channel', 'sms'); }
    public function scopeEmail($q)  { return $q->where('channel', 'email'); }
}
