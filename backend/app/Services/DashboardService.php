<?php
// app/Services/DashboardService.php
namespace App\Services;

use App\Models\AmortizationSchedule;
use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\Member;
use App\Models\ShareCapitalTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getData(): array
    {
        return Cache::remember('dashboard_data', 300, function () {
            return [
                'stats'               => $this->stats(),
                'monthly_collections' => $this->monthlyCollections(6),
                'loan_status'         => $this->loanStatusBreakdown(),
                'disbursements'       => $this->disbursementsTrend(6),
                'loan_types'          => $this->loanTypeBreakdown(),
                'aging'               => $this->agingBuckets(),
                'share_capital'       => $this->shareCapitalSummary(),
                'recent_activity'     => $this->recentActivity(8),
                'top_overdue'         => $this->topOverdue(5),
                'generated_at'        => now()->toIso8601String(),
            ];
        });
    }

    public function bust(): void
    {
        Cache::forget('dashboard_data');
    }

    // ── 1. Stat cards ─────────────────────────────────────────

    private function stats(): array
    {
        $activeCount = Loan::where('status', 'ACTIVE')->count();

        $outstanding = AmortizationSchedule::whereHas('loan', fn($q) =>
            $q->where('status', 'ACTIVE')
        )->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
         ->selectRaw('SUM(amount_due - paid_amount) as total')
         ->value('total') ?? 0;

        // Collection rate for current month
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd   = Carbon::now()->endOfMonth()->toDateString();

        $monthSched = AmortizationSchedule::whereBetween('due_date', [$monthStart, $monthEnd]);
        $expected   = (float) (clone $monthSched)->sum('amount_due');
        $collected  = (float) (clone $monthSched)->sum('paid_amount');
        $rate       = $expected > 0 ? round($collected / $expected * 100, 1) : 0;

        // Compare to last month
        $lastStart  = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $lastEnd    = Carbon::now()->subMonth()->endOfMonth()->toDateString();
        $lastSched  = AmortizationSchedule::whereBetween('due_date', [$lastStart, $lastEnd]);
        $lastExp    = (float) (clone $lastSched)->sum('amount_due');
        $lastColl   = (float) (clone $lastSched)->sum('paid_amount');
        $lastRate   = $lastExp > 0 ? round($lastColl / $lastExp * 100, 1) : 0;

        // Overdue accounts
        $overdueCount = Loan::where('status', 'ACTIVE')
            ->whereHas('amortizationSchedules', fn($q) =>
                $q->where('status', 'OVERDUE')
            )->count();

        $overdueBalance = AmortizationSchedule::where('status', 'OVERDUE')
            ->whereHas('loan', fn($q) => $q->where('status', 'ACTIVE'))
            ->selectRaw('SUM(amount_due - paid_amount) as total')
            ->value('total') ?? 0;

        // New loans this month
        $newThisMonth = Loan::whereMonth('application_date', now()->month)
            ->whereYear('application_date', now()->year)
            ->count();

        return [
            'active_loans'       => $activeCount,
            'total_outstanding'  => round((float) $outstanding, 2),
            'collection_rate'    => $rate,
            'collection_rate_delta' => round($rate - $lastRate, 1),
            'overdue_count'      => $overdueCount,
            'overdue_balance'    => round((float) $overdueBalance, 2),
            'new_loans_this_month' => $newThisMonth,
            'total_members'      => Member::where('member_status', 'ACTIVE')->count(),
        ];
    }

    // ── 2. Monthly collections (last N months) ────────────────

    private function monthlyCollections(int $months): array
    {
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $d     = Carbon::now()->subMonths($i);
            $start = $d->copy()->startOfMonth()->toDateString();
            $end   = $d->copy()->endOfMonth()->toDateString();

            $sched = AmortizationSchedule::whereBetween('due_date', [$start, $end]);
            $expected  = (float) (clone $sched)->sum('amount_due');
            $collected = (float) (clone $sched)->sum('paid_amount');

            $result[] = [
                'month'     => $d->format('M'),
                'year'      => $d->format('Y'),
                'label'     => $d->format('M Y'),
                'expected'  => round($expected, 2),
                'collected' => round($collected, 2),
                'rate'      => $expected > 0 ? round($collected / $expected * 100, 1) : 0,
            ];
        }
        return $result;
    }

    // ── 3. Loan status breakdown ──────────────────────────────

    private function loanStatusBreakdown(): array
    {
        $rows = Loan::groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = ['ACTIVE','APPROVED','PENDING','CLOSED','REJECTED','DRAFT'];
        $result   = [];
        foreach ($statuses as $s) {
            if (($rows[$s] ?? 0) > 0) {
                $result[] = ['status' => $s, 'count' => (int)($rows[$s] ?? 0)];
            }
        }
        return $result;
    }

    // ── 4. Disbursements trend ────────────────────────────────

    private function disbursementsTrend(int $months): array
    {
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $d     = Carbon::now()->subMonths($i);
            $start = $d->copy()->startOfMonth()->toDateString();
            $end   = $d->copy()->endOfMonth()->toDateString();

            $loans = Loan::whereBetween('application_date', [$start, $end]);
            $result[] = [
                'month'  => $d->format('M'),
                'label'  => $d->format('M Y'),
                'count'  => (clone $loans)->count(),
                'amount' => round((float)(clone $loans)->sum('amount'), 2),
            ];
        }
        return $result;
    }

    // ── 5. Loan type outstanding balance ──────────────────────

    private function loanTypeBreakdown(): array
    {
        return Loan::join('loan_types', 'loan_types.id', '=', 'loans.loan_type_id')
            ->where('loans.status', 'ACTIVE')
            ->selectRaw('loan_types.label, SUM(loans.amount) as total_amount, COUNT(*) as count')
            ->groupBy('loan_types.id', 'loan_types.label')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn($r) => [
                'label'  => $r->label,
                'amount' => round((float)$r->total_amount, 2),
                'count'  => (int)$r->count,
            ])
            ->values()
            ->toArray();
    }

    // ── 6. Overdue aging buckets ──────────────────────────────

    private function agingBuckets(): array
    {
        $overdue = AmortizationSchedule::where('status', 'OVERDUE')
            ->whereHas('loan', fn($q) => $q->where('status', 'ACTIVE'))
            ->select(['days_overdue', 'amount_due', 'paid_amount', 'penalty_amount'])
            ->get();

        $buckets = [
            '0_30'    => ['label' => '0–30 days',   'count' => 0, 'balance' => 0, 'penalty' => 0],
            '31_60'   => ['label' => '31–60 days',  'count' => 0, 'balance' => 0, 'penalty' => 0],
            '61_90'   => ['label' => '61–90 days',  'count' => 0, 'balance' => 0, 'penalty' => 0],
            '90_plus' => ['label' => '90+ days',    'count' => 0, 'balance' => 0, 'penalty' => 0],
        ];

        foreach ($overdue as $r) {
            $days    = (int)$r->days_overdue;
            $balance = (float)$r->amount_due - (float)$r->paid_amount;
            $key     = match(true) {
                $days <= 30  => '0_30',
                $days <= 60  => '31_60',
                $days <= 90  => '61_90',
                default      => '90_plus',
            };
            $buckets[$key]['count']++;
            $buckets[$key]['balance']  = round($buckets[$key]['balance'] + $balance, 2);
            $buckets[$key]['penalty']  = round($buckets[$key]['penalty'] + (float)$r->penalty_amount, 2);
        }

        return array_values($buckets);
    }

    // ── 7. Share capital summary ──────────────────────────────

    private function shareCapitalSummary(): array
    {
        $total        = (float) Member::sum('share_capital');
        $memberCount  = Member::where('member_status', 'ACTIVE')->count();

        $start  = Carbon::now()->startOfMonth()->toDateString();
        $end    = Carbon::now()->endOfMonth()->toDateString();
        $txs    = ShareCapitalTransaction::withoutTrashed()
            ->whereBetween('transaction_date', [$start, $end]);

        $credits = (float)(clone $txs)->where('direction', 'credit')->sum('amount');
        $debits  = (float)(clone $txs)->where('direction', 'debit')->sum('amount');

        return [
            'total_balance'    => round($total, 2),
            'member_count'     => $memberCount,
            'month_credits'    => round($credits, 2),
            'month_debits'     => round($debits, 2),
            'month_net'        => round($credits - $debits, 2),
        ];
    }

    // ── 8. Recent activity from audit log ────────────────────

    private function recentActivity(int $limit): array
    {
        return AuditLog::with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'id'             => $log->id,
                'event'          => $log->event,
                'auditable_type' => class_basename($log->auditable_type),
                'auditable_label'=> $log->auditable_label,
                'user_name'      => $log->user_name,
                'created_at'     => $log->created_at?->toIso8601String(),
                'created_at_human' => $log->created_at?->diffForHumans(),
            ])
            ->values()
            ->toArray();
    }

    // ── 9. Top overdue members ────────────────────────────────

    private function topOverdue(int $limit): array
    {
        return AmortizationSchedule::with(['loan.member'])
            ->where('status', 'OVERDUE')
            ->whereHas('loan', fn($q) => $q->where('status', 'ACTIVE'))
            ->orderByDesc('days_overdue')
            ->limit($limit)
            ->get()
            ->map(fn($s) => [
                'member_name'  => $s->loan->member
                    ? "{$s->loan->member->first_name} {$s->loan->member->last_name}"
                    : '—',
                'member_no'    => $s->loan->member?->member_no,
                'loan_no'      => $s->loan->loan_no,
                'days_overdue' => (int)$s->days_overdue,
                'balance'      => round((float)$s->amount_due - (float)$s->paid_amount, 2),
                'penalty'      => round((float)$s->penalty_amount, 2),
                'loan_id'      => $s->loan_id,
            ])
            ->values()
            ->toArray();
    }
}
