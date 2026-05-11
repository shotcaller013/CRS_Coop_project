<?php
// app/Policies/BeneficiaryPolicy.php
namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\User;

class BeneficiaryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view-member']);
    }

    public function view(User $user, Beneficiary $beneficiary): bool
    {
        return $user->hasAnyPermission(['view-member']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['create-member', 'edit-member']);
    }

    public function update(User $user, Beneficiary $beneficiary): bool
    {
        return $user->hasAnyPermission(['edit-member']);
    }

    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        return $user->hasAnyPermission(['delete-member', 'edit-member']);
    }
}
