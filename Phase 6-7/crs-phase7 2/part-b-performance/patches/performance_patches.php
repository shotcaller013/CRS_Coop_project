<?php
// ══════════════════════════════════════════════════════════════════════════
// PATCH A — DashboardService.php cache layer
// ══════════════════════════════════════════════════════════════════════════
// In app/Services/DashboardService.php replace the getData() method with:

use Illuminate\Support\Facades\Cache;

public function getData(): array
{
    // Cache for 5 minutes — dashboard data doesn't need to be real-time.
    // Key includes the date so it auto-expires at midnight.
    $cacheKey = 'dashboard:' . now()->format('Ymd-Hi');  // changes every minute for the "Hi" suffix

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

// Also add a cache-busting method to call after payments, overdue runs, etc.:
public function bust(): void
{
    Cache::forget('dashboard_data');
}

// ── Call bust() in these places: ──────────────────────────────────────────
// PaymentService::record()           → app(DashboardService::class)->bust()
// OverdueDetectionService::run()     → app(DashboardService::class)->bust()
// LoanService::approve()             → app(DashboardService::class)->bust()


// ══════════════════════════════════════════════════════════════════════════
// PATCH B — MemberController N+1 fix
// ══════════════════════════════════════════════════════════════════════════
// In app/Http/Controllers/Api/MemberController.php, index() method.
// Replace the query with withCount() to avoid N+1 on the loans count:

// BEFORE:
$members = Member::query()
    ->when($search, ...)
    ->paginate($perPage);

// AFTER:
$members = Member::query()
    ->withCount([
        'loans as active_loan_count' => fn($q) => $q->where('status', 'ACTIVE'),
        'loans as total_loan_count',
    ])
    ->when($search, ...)
    ->paginate($perPage);

// Then in MemberResource.php add:
// 'active_loan_count' => $this->active_loan_count ?? 0,
// 'total_loan_count'  => $this->total_loan_count  ?? 0,

// ══════════════════════════════════════════════════════════════════════════
// PATCH C — Report queries: eager load fix
// ══════════════════════════════════════════════════════════════════════════
// In ReportService.php, all three report methods load loans with members.
// Add explicit eager loads to prevent N+1 when iterating results:

// BEFORE:
$loans = Loan::where('status', 'ACTIVE')->get();

// AFTER:
$loans = Loan::where('status', 'ACTIVE')
    ->with(['member:id,first_name,last_name,member_no,company,department', 'loanType:id,label'])
    ->get();

// ══════════════════════════════════════════════════════════════════════════
// PATCH D — API response compression
// ══════════════════════════════════════════════════════════════════════════
// In app/Http/Kernel.php, add to $middleware (global):
\Illuminate\Http\Middleware\GzipMiddleware::class,

// Or use the built-in compress middleware for Laravel 10+:
// In bootstrap/app.php (Laravel 11):
$app->withMiddleware(function (Middleware $middleware) {
    $middleware->compress();  // Gzip all responses
});

// ══════════════════════════════════════════════════════════════════════════
// PATCH E — Frontend: debounce search inputs
// ══════════════════════════════════════════════════════════════════════════
// Add to src/composables/useDebounce.js:

// import { ref, watch } from 'vue'
// export function useDebounce(value, delay = 400) {
//   const debounced = ref(value.value)
//   let timer
//   watch(value, (v) => {
//     clearTimeout(timer)
//     timer = setTimeout(() => { debounced.value = v }, delay)
//   })
//   return debounced
// }

// Usage in any page with a search input:
// const debouncedSearch = useDebounce(computed(() => filters.search), 400)
// watch(debouncedSearch, () => store.fetch())
// Remove the @keyup.enter="search" pattern and let the watcher handle it.
