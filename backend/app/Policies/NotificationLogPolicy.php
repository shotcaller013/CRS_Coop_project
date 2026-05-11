<?php

namespace App\Policies;

use App\Models\User;

class NotificationLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'manager']);
    }
}
