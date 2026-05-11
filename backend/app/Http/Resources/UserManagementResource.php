<?php
// app/Http/Resources/UserManagementResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'email'                => $this->email,
            'role'                 => $this->roles->first()?->name,
            'role_label'           => $this->roleLabel(),
            'is_active'            => (bool) $this->is_active,
            'must_change_password' => (bool) $this->must_change_password,
            'last_login_at'        => $this->last_login_at?->toIso8601String(),
            'last_login_at_human'  => $this->last_login_at?->diffForHumans(),
            'last_login_ip'        => $this->last_login_ip,
            'created_at'           => $this->created_at?->toIso8601String(),
            'created_at_human'     => $this->created_at?->diffForHumans(),
        ];
    }

    private function roleLabel(): string
    {
        return match ($this->roles->first()?->name) {
            'super-admin'  => 'Super Admin',
            'manager'      => 'Manager',
            'loan-officer' => 'Loan Officer',
            'staff'        => 'Staff',
            'board'        => 'Board Member',
            default        => ucfirst($this->roles->first()?->name ?? 'No role'),
        };
    }
}
