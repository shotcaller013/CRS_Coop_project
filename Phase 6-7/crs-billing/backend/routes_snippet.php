<?php
// ── routes/api.php — add inside v1 auth:sanctum group ──────────
use App\Http\Controllers\Api\BillController;

Route::prefix('bills')->group(function () {
    Route::get('/',                              [BillController::class, 'index']);
    Route::post('/',                             [BillController::class, 'store']);
    Route::get('/{bill}',                        [BillController::class, 'show']);
    Route::post('/{bill}/issue',                 [BillController::class, 'issue']);
    Route::post('/{bill}/remittance',            [BillController::class, 'uploadRemittance']);
    Route::post('/{bill}/settle',                [BillController::class, 'settle']);
    Route::post('/{bill}/cancel',                [BillController::class, 'cancel']);
    Route::get('/{bill}/pdf',                    [BillController::class, 'pdf'])->name('api.bills.pdf');
});

Route::get('bills/remittance/{remittance}/file', [BillController::class, 'remittanceFile'])
    ->name('api.bills.remittance.file');

// ── app/Providers/AuthServiceProvider.php ─────────────────────
// Add to $policies:
\App\Models\Bill::class => \App\Policies\BillPolicy::class,

// ── App.vue sidebar — add under Payments ──────────────────────
// <NavItem to="/billing" icon="◻" label="Billing" :collapsed="isCollapsed" />
// Visible to all roles that can view-payment.

// ── No seeder changes needed ───────────────────────────────────
// BillPolicy reuses existing role checks.
// The BILLED status is added to amortization_schedules via migration.
// The overdue detection job already filters by status IN ('PENDING','OVERDUE')
// so BILLED periods are automatically excluded from the nightly job.
