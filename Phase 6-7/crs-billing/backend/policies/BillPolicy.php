<?php
// app/Policies/BillPolicy.php
namespace App\Policies;

use App\Models\Bill;
use App\Models\User;

class BillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view-payment', 'create-payment']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'manager']);
    }

    public function update(User $user, Bill $bill): bool
    {
        // Can only edit DRAFT bills
        return $user->hasAnyRole(['super-admin', 'manager'])
            && $bill->status === 'DRAFT';
    }

    public function issue(User $user, Bill $bill): bool
    {
        return $user->hasAnyRole(['super-admin', 'manager'])
            && $bill->status === 'DRAFT';
    }

    public function uploadRemittance(User $user, Bill $bill): bool
    {
        return $user->hasAnyRole(['super-admin', 'manager'])
            && in_array($bill->status, ['ISSUED', 'PARTIAL']);
    }

    public function settle(User $user, Bill $bill): bool
    {
        return $user->hasAnyRole(['super-admin', 'manager'])
            && in_array($bill->status, ['ISSUED', 'PARTIAL']);
    }

    public function cancel(User $user, Bill $bill): bool
    {
        return $user->hasAnyRole(['super-admin'])
            && in_array($bill->status, ['DRAFT', 'ISSUED']);
    }
}
