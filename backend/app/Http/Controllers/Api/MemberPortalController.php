<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Loan;
use App\Models\MemberPortalAuditLog;
use App\Models\MemberPortalSession;
use App\Models\Payment;
use App\Models\ShareCapitalTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberPortalController extends Controller
{
    private function resolveAccount(Request $request): array
    {
        $raw = '';
        $header = $request->header('Authorization', '');
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            $raw = trim($matches[1]);
        }
        if ($raw === '') {
            $raw = trim($request->query('token', ''));
        }

        if ($raw === '') {
            abort(response()->json(['success' => false, 'message' => 'Missing member portal token.'], 401));
        }

        $session = MemberPortalSession::with(['account.member'])
            ->where('token_hash', hash('sha256', $raw))
            ->where('expires_at', '>', now())
            ->whereHas('account', fn($q) => $q->where('is_active', true))
            ->first();

        if (! $session) {
            abort(response()->json(['success' => false, 'message' => 'Member portal session expired or invalid.'], 401));
        }

        return [$session->account, $session->account->member];
    }

    public function dashboard(Request $request): JsonResponse
    {
        [$account, $member] = $this->resolveAccount($request);
        $memberId = $member->id;

        $memberData = [
            'id'             => $memberId,
            'member_no'      => $member->member_no,
            'first_name'     => $member->first_name,
            'middle_name'    => $member->middle_name,
            'last_name'      => $member->last_name,
            'email'          => $member->email,
            'contact'        => $member->contact,
            'address'        => $member->address,
            'company'        => $member->company,
            'branch'         => $member->branch,
            'department'     => $member->department,
            'position'       => $member->position,
            'status'         => $member->status,
            'member_status'  => $member->member_status,
            'monthly_salary' => (float) ($member->monthly_salary ?? 0),
            'share_capital'  => (float) ($member->share_capital ?? 0),
        ];

        $loans = Loan::with(['loanType', 'amortizationSchedules'])
            ->where('member_id', $memberId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($loan) {
                $schedules   = $loan->amortizationSchedules ?? collect();
                $outstanding = $schedules->sum(fn($s) => max(0, ($s->amount_due ?? 0) - ($s->paid_amount ?? 0)));
                $nextPending = $schedules
                    ->whereIn('status', ['PENDING', 'PARTIAL', 'BILLED', 'OVERDUE'])
                    ->sortBy('due_date')
                    ->first();

                return [
                    'id'              => $loan->id,
                    'loan_no'         => $loan->loan_no,
                    'type'            => $loan->loanType?->label ?? 'Loan',
                    'amount'          => (float) $loan->amount,
                    'status'          => $loan->status,
                    'first_due_date'  => optional($loan->first_due_date)->toDateString(),
                    'total_payable'   => (float) ($schedules->sum('amount_due') ?: $loan->amount),
                    'outstanding'     => (float) $outstanding,
                    'next_due_date'   => optional($nextPending?->due_date)->toDateString(),
                    'next_due_amount' => (float) ($nextPending ? max(0, ($nextPending->amount_due ?? 0) - ($nextPending->paid_amount ?? 0)) : 0),
                ];
            });

        $payments = Payment::join('loans', 'loans.id', '=', 'payments.loan_id')
            ->where('loans.member_id', $memberId)
            ->select('payments.payment_date as date', 'payments.or_number as reference', 'loans.loan_no', 'payments.amount_paid as amount')
            ->orderByDesc('payments.payment_date')
            ->orderByDesc('payments.id')
            ->limit(50)
            ->get()
            ->map(fn($row) => [...$row->toArray(), 'amount' => (float) $row->amount, 'status' => 'POSTED']);

        $shareCapital = ShareCapitalTransaction::where('member_id', $memberId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn($row) => [
                'date'      => optional($row->transaction_date)->toDateString(),
                'reference' => $row->or_number ?? $row->reference_no,
                'type'      => ucfirst($row->type),
                'amount'    => (float) $row->amount,
                'balance'   => (float) $row->balance_after,
                'remarks'   => $row->remarks,
            ]);

        $beneficiaries = Beneficiary::where('member_id', $memberId)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('last_name')
            ->get()
            ->map(fn($b) => [
                'name'         => $b->full_name,
                'relationship' => $b->relationship,
                'allocation'   => (float) ($b->share_percentage ?? 0),
                'type'         => ucfirst($b->type),
                'contact'      => $b->contact_number,
            ]);

        try {
            MemberPortalAuditLog::create([
                'account_id' => $account->id,
                'member_id'  => $memberId,
                'action'     => 'VIEW_DASHBOARD',
                'detail'     => 'Member portal dashboard accessed.',
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            ]);
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'data'    => [
                'member'       => $memberData,
                'access'       => [
                    'username'              => $account->username,
                    'modules'               => $account->modules,
                    'force_password_change' => $account->force_password_change,
                ],
                'loans'        => $loans,
                'payments'     => $payments,
                'shareCapital' => $shareCapital,
                'beneficiaries'=> $beneficiaries,
            ],
        ]);
    }
}
