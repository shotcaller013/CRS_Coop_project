<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberPortalAccount;
use App\Models\MemberPortalAuditLog;
use App\Models\MemberPortalSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberPortalAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);

        $identifier = strtolower(trim($request->identifier));

        $account = MemberPortalAccount::with('member')
            ->where('is_active', true)
            ->where(function ($q) use ($identifier) {
                $q->whereRaw('LOWER(username) = ?', [$identifier])
                  ->orWhereRaw('LOWER(email) = ?', [$identifier])
                  ->orWhereHas('member', fn($m) => $m->whereRaw('LOWER(member_no) = ?', [$identifier])
                      ->orWhereRaw('LOWER(email) = ?', [$identifier]));
            })
            ->first();

        if (! $account || ! Hash::check($request->password, $account->password_hash)) {
            return response()->json(['success' => false, 'message' => 'Invalid member login.'], 401);
        }

        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = now()->addHours(8);

        MemberPortalSession::create([
            'account_id' => $account->id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $account->update(['last_login_at' => now()]);

        try {
            MemberPortalAuditLog::create([
                'account_id' => $account->id,
                'member_id'  => $account->member_id,
                'action'     => 'LOGIN',
                'detail'     => 'Member portal login successful.',
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            ]);
        } catch (\Throwable) {
            // audit failure must not block login
        }

        $m = $account->member;

        return response()->json([
            'success' => true,
            'data'    => [
                'token'      => $rawToken,
                'expires_at' => $expiresAt->toISOString(),
                'member'     => [
                    'id'             => $m->id,
                    'member_no'      => $m->member_no,
                    'first_name'     => $m->first_name,
                    'middle_name'    => $m->middle_name,
                    'last_name'      => $m->last_name,
                    'email'          => $m->email,
                    'contact'        => $m->contact,
                    'address'        => $m->address,
                    'company'        => $m->company,
                    'branch'         => $m->branch,
                    'department'     => $m->department,
                    'position'       => $m->position,
                    'status'         => $m->status,
                    'member_status'  => $m->member_status,
                    'monthly_salary' => (float) ($m->monthly_salary ?? 0),
                    'share_capital'  => (float) ($m->share_capital ?? 0),
                ],
                'access' => [
                    'id'                    => $account->id,
                    'username'              => $account->username,
                    'modules'               => $account->modules,
                    'force_password_change' => $account->force_password_change,
                ],
            ],
        ]);
    }
}
