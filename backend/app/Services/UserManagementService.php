<?php
// app/Services/UserManagementService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementService
{
    private const ROLES = ['super-admin', 'manager', 'loan-officer', 'staff', 'board'];

    // ── Create ────────────────────────────────────────────────

    public function create(array $data): User
    {
        $user = User::create([
            'name'                 => $data['name'],
            'email'                => $data['email'],
            'password'             => Hash::make($data['password']),
            'is_active'            => true,
            'must_change_password' => true, // always force change on first login
        ]);

        $user->syncRoles([$data['role']]);

        return $user->load('roles');
    }

    // ── Update ────────────────────────────────────────────────

    public function update(User $user, array $data): User
    {
        $fillable = array_filter([
            'name'  => $data['name']  ?? null,
            'email' => $data['email'] ?? null,
        ]);

        if (!empty($fillable)) {
            $user->update($fillable);
        }

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user->fresh('roles');
    }

    // ── Toggle active ─────────────────────────────────────────

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);

        // Revoke all tokens when deactivating
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return $user->fresh('roles');
    }

    // ── Reset password ────────────────────────────────────────

    /**
     * Generate a random temporary password, hash it, and flag the account
     * so the frontend forces a password change on next login.
     * Returns the plain-text temp password so the admin can communicate it.
     */
    public function resetPassword(User $user): string
    {
        // Generate a readable temp password: 2 words + 4 digits
        $words = ['Coop', 'Loan', 'Member', 'Login', 'Access', 'Secure', 'Credit', 'Ecco'];
        $temp  = $words[array_rand($words)] . $words[array_rand($words)] . rand(1000, 9999);

        $user->update([
            'password'             => Hash::make($temp),
            'must_change_password' => true,
        ]);

        // Revoke existing tokens so the user must re-login with the new password
        $user->tokens()->delete();

        return $temp;
    }

    // ── List ──────────────────────────────────────────────────

    public function list(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::with('roles')->orderBy('name');

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', fn($q) => $q->where('name', $filters['role']));
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(fn($q) =>
                $q->where('name',  'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
            );
        }

        return $query->get();
    }
}
