<?php
// app/Models/AuditLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    // Immutable — no updated_at
    public $timestamps = false;
    const CREATED_AT   = 'created_at';

    protected $fillable = [
        'user_id', 'user_name', 'user_role',
        'auditable_type', 'auditable_id', 'auditable_label',
        'event', 'old_values', 'new_values', 'dirty_keys',
        'ip_address', 'user_agent', 'url', 'tags',
        'created_at',
    ];

    protected $casts = [
        'old_values'  => 'array',
        'new_values'  => 'array',
        'dirty_keys'  => 'array',
        'created_at'  => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Helpers ──────────────────────────────────────────────

    /** Short class name e.g. "Member", "Loan" */
    public function getAuditableShortTypeAttribute(): string
    {
        return class_basename($this->auditable_type);
    }

    /** True when at least one key changed */
    public function hasDiff(): bool
    {
        return !empty($this->dirty_keys);
    }

    /** Returns only the changed before/after pairs */
    public function getDiff(): array
    {
        if (empty($this->dirty_keys)) return [];
        $diff = [];
        foreach ($this->dirty_keys as $key) {
            $diff[$key] = [
                'old' => $this->old_values[$key] ?? null,
                'new' => $this->new_values[$key] ?? null,
            ];
        }
        return $diff;
    }
}
