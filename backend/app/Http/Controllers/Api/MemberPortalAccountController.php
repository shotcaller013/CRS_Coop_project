<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberPortalAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberPortalAccountController extends Controller
{
    private function formatAccount(MemberPortalAccount $account): array
    {
        $account->loadMissing('member');
        return [
            'id'                    => $account->id,
            'member_id'             => $account->member_id,
            'member_no'             => $account->member->member_no ?? null,
            'member_name'           => $account->member_name,
            'company'               => $account->member->company ?? null,
            'department'            => $account->member->department ?? null,
            'position'              => $account->member->position ?? null,
            'username'              => $account->username,
            'email'                 => $account->email,
            'force_password_change' => $account->force_password_change,
            'modules'               => $account->modules,
            'active'                => $account->is_active,
            'last_login_at'         => $account->last_login_at?->toISOString(),
            'created_at'            => $account->created_at?->toISOString(),
            'updated_at'            => $account->updated_at?->toISOString(),
        ];
    }

    private function defaultModules(): array
    {
        return ['dashboard', 'loans', 'payments', 'shareCapital', 'beneficiaries', 'profile'];
    }

    public function index(Request $request): JsonResponse
    {
        $query = MemberPortalAccount::with('member');

        if ($request->filled('search')) {
            $like = '%' . $request->search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('username', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhereHas('member', fn($m) => $m->where('member_no', 'like', $like)
                      ->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", [$like]));
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $accounts = $query->orderByHas('member', fn($q) => $q->orderBy('last_name')->orderBy('first_name'))
                          ->limit(300)
                          ->get();

        return response()->json([
            'success' => true,
            'data'    => $accounts->map(fn($a) => $this->formatAccount($a)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_id'             => 'required|integer|exists:members,id',
            'username'              => 'required|string|max:80|unique:member_portal_accounts,username',
            'email'                 => 'nullable|email|max:150',
            'password'              => 'required|string|min:6',
            'force_password_change' => 'boolean',
            'modules'               => 'nullable|array',
            'is_active'             => 'boolean',
        ]);

        $account = MemberPortalAccount::create([
            'member_id'             => $validated['member_id'],
            'username'              => $validated['username'],
            'email'                 => $validated['email'] ?? null,
            'password_hash'         => Hash::make($validated['password']),
            'force_password_change' => $validated['force_password_change'] ?? true,
            'modules_json'          => $validated['modules'] ?? $this->defaultModules(),
            'is_active'             => $validated['is_active'] ?? true,
            'created_by'            => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->formatAccount($account),
        ], 201);
    }

    public function show(MemberPortalAccount $memberPortalAccount): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->formatAccount($memberPortalAccount),
        ]);
    }

    public function update(Request $request, MemberPortalAccount $memberPortalAccount): JsonResponse
    {
        $validated = $request->validate([
            'member_id'             => 'required|integer|exists:members,id',
            'username'              => 'required|string|max:80|unique:member_portal_accounts,username,' . $memberPortalAccount->id,
            'email'                 => 'nullable|email|max:150',
            'password'              => 'nullable|string|min:6',
            'force_password_change' => 'boolean',
            'modules'               => 'nullable|array',
            'is_active'             => 'boolean',
        ]);

        $data = [
            'member_id'             => $validated['member_id'],
            'username'              => $validated['username'],
            'email'                 => $validated['email'] ?? null,
            'force_password_change' => $validated['force_password_change'] ?? $memberPortalAccount->force_password_change,
            'modules_json'          => $validated['modules'] ?? $memberPortalAccount->modules_json,
            'is_active'             => $validated['is_active'] ?? $memberPortalAccount->is_active,
        ];

        if (! empty($validated['password'])) {
            $data['password_hash'] = Hash::make($validated['password']);
        }

        $memberPortalAccount->update($data);

        return response()->json([
            'success' => true,
            'data'    => $this->formatAccount($memberPortalAccount->fresh('member')),
        ]);
    }

    public function toggleActive(MemberPortalAccount $memberPortalAccount): JsonResponse
    {
        $memberPortalAccount->update(['is_active' => ! $memberPortalAccount->is_active]);

        return response()->json([
            'success' => true,
            'data'    => $this->formatAccount($memberPortalAccount->fresh('member')),
        ]);
    }

    public function resetPassword(MemberPortalAccount $memberPortalAccount): JsonResponse
    {
        $temp = 'MEM-' . random_int(100000, 999999);

        $memberPortalAccount->update([
            'password_hash'         => Hash::make($temp),
            'force_password_change' => true,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'temp_password' => $temp,
                'account'       => $this->formatAccount($memberPortalAccount->fresh('member')),
            ],
        ]);
    }
}
