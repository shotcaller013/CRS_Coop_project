<?php
// app/Services/AuditLogService.php
namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Keys that are NEVER written to audit logs — passwords, tokens, etc.
     */
    private const SANITIZE_KEYS = [
        'password', 'password_confirmation', 'remember_token',
        'api_token', 'secret', 'token', 'pin',
    ];

    /**
     * Keys that are masked rather than stripped — show that a value
     * changed but not what it changed to.
     */
    private const MASK_KEYS = [
        'email', // show it changed but mask actual value for privacy
    ];

    // ── Public API ───────────────────────────────────────────

    /**
     * Record a created event.
     * old_values = null, new_values = full sanitized attributes.
     */
    public function recordCreated(Model $model): void
    {
        $this->write($model, 'created', null, $this->sanitize($model->getAttributes()));
    }

    /**
     * Record an updated event.
     * Stores the full before/after plus the list of changed keys.
     */
    public function recordUpdated(Model $model): void
    {
        $dirty = array_keys($model->getDirty());
        // Filter out non-auditable internal keys
        $dirty = array_filter($dirty, fn($k) => !in_array($k, ['updated_at', 'created_at']));
        $dirty = array_values($dirty);

        if (empty($dirty)) return; // nothing meaningful changed

        $old = $this->sanitize(array_intersect_key($model->getOriginal(), array_flip($dirty)));
        $new = $this->sanitize(array_intersect_key($model->getAttributes(), array_flip($dirty)));

        $this->write($model, 'updated', $old, $new, $dirty);
    }

    /**
     * Record a soft-delete event.
     * Captures the full record at the moment of deletion.
     */
    public function recordDeleted(Model $model): void
    {
        $this->write($model, 'deleted', $this->sanitize($model->getAttributes()), null);
    }

    /**
     * Record a restore-from-soft-delete event.
     */
    public function recordRestored(Model $model): void
    {
        $this->write($model, 'restored', null, $this->sanitize($model->getAttributes()));
    }

    /**
     * Write a freeform audit entry (for system actions like overdue detection).
     */
    public function recordSystem(
        string $auditableType,
        int    $auditableId,
        string $event,
        array  $context = [],
        string $tags = ''
    ): void {
        AuditLog::create([
            'user_id'        => null,
            'user_name'      => 'System',
            'user_role'      => 'system',
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'auditable_label'=> $context['label'] ?? null,
            'event'          => $event,
            'old_values'     => $context['old'] ?? null,
            'new_values'     => $context['new'] ?? null,
            'dirty_keys'     => $context['keys'] ?? null,
            'ip_address'     => null,
            'user_agent'     => null,
            'url'            => null,
            'tags'           => $tags ?: null,
        ]);
    }

    // ── Private helpers ──────────────────────────────────────

    private function write(
        Model  $model,
        string $event,
        ?array $old,
        ?array $new,
        array  $dirtyKeys = []
    ): void {
        $user = Auth::user();

        AuditLog::create([
            'user_id'        => $user?->id,
            'user_name'      => $user?->name ?? 'System',
            'user_role'      => $user?->roles->first()?->name ?? null,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'auditable_label'=> $this->resolveLabel($model),
            'event'          => $event,
            'old_values'     => $old,
            'new_values'     => $new,
            'dirty_keys'     => empty($dirtyKeys) ? null : $dirtyKeys,
            'ip_address'     => Request::ip(),
            'user_agent'     => substr((string) Request::userAgent(), 0, 500),
            'url'            => Request::path(),
            'tags'           => null,
        ]);
    }

    /**
     * Strip sensitive keys. Mask semi-sensitive ones.
     */
    private function sanitize(array $attributes): array
    {
        $result = [];
        foreach ($attributes as $key => $value) {
            if (in_array($key, self::SANITIZE_KEYS)) {
                continue; // drop entirely
            }
            if (in_array($key, self::MASK_KEYS)) {
                $result[$key] = '[masked]';
                continue;
            }
            // Cast to string-safe scalar — JSON won't choke on nulls
            $result[$key] = is_array($value) || is_object($value)
                ? json_encode($value)
                : $value;
        }
        return $result;
    }

    /**
     * Resolve a human-readable label for the audited record.
     * Extend this as new models are added.
     */
    private function resolveLabel(Model $model): ?string
    {
        return match (true) {
            isset($model->loan_no)      => $model->loan_no,
            isset($model->member_no)    => "{$model->first_name} {$model->last_name} ({$model->member_no})",
            isset($model->or_number)    => "O.R. {$model->or_number}",
            isset($model->label)        => $model->label,  // LoanType
            isset($model->name)         => $model->name,   // CoopProfile, Company, User
            default                     => null,
        };
    }
}
