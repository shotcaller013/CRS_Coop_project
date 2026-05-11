<?php
// app/Policies/UserManagementPolicy.php
namespace App\Policies;

use App\Models\User;

class UserManagementPolicy
{
    /** Only super-admin can manage users */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasRole('super-admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasRole('super-admin');
    }

    /** Cannot deactivate yourself */
    public function deactivate(User $user, User $target): bool
    {
        return $user->hasRole('super-admin') && $user->id !== $target->id;
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->hasRole('super-admin');
    }
}
