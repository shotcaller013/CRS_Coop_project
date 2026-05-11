<?php
// app/Policies/RestructuringPolicy.php
namespace App\Policies;

use App\Models\Loan;
use App\Models\LoanRestructuring;
use App\Models\User;

class RestructuringPolicy
{
    /** View restructuring history — anyone who can view loans */
    public function viewAny(User $user, Loan $loan): bool
    {
        return $user->hasAnyPermission(['view-loan']);
    }

    /** Preview a restructuring (dry run) — manager and above */
    public function preview(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'manager']);
    }

    /** Execute a restructuring — manager and above */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'manager']);
    }
}
