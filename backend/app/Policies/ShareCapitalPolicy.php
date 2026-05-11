<?php
// app/Policies/ShareCapitalPolicy.php
namespace App\Policies;

use App\Models\ShareCapitalTransaction;
use App\Models\User;

class ShareCapitalPolicy
{
    /** View ledger — anyone who can view members */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view-member']);
    }

    /** Post a new transaction — loan officer and above */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['create-payment', 'edit-member']);
    }

    /** Void (soft-delete) a transaction — manager only */
    public function delete(User $user, ShareCapitalTransaction $tx): bool
    {
        return $user->hasAnyRole(['super-admin', 'manager']);
    }

    /** View aggregate share capital report — manager and above */
    public function viewReport(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'manager', 'board']);
    }
}
