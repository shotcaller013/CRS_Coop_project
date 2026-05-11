<?php
// app/Policies/AuditLogPolicy.php
namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Only super-admin can view audit logs.
     * Audit logs are immutable — no create/update/delete through the API.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->hasRole('super-admin');
    }
}
