<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\LoanTypeSettingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\EligibilityController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\BeneficiaryController;
use App\Http\Controllers\Api\ShareCapitalController;
use App\Http\Controllers\Api\RestructuringController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\LoanPacketController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\MemberPortalAccountController;
use App\Http\Controllers\Api\MemberPortalAuthController;
use App\Http\Controllers\Api\MemberPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login',  [AuthController::class, 'login']);

    // ── Member Portal (token-based, no Sanctum) ───────────────────
    Route::post('member-portal/auth/login', [MemberPortalAuthController::class, 'login']);
    Route::get('member-portal/dashboard',   [MemberPortalController::class, 'dashboard']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me',      [AuthController::class, 'me']);

    Route::get('members/dropdown', [MemberController::class, 'dropdown']);
    Route::apiResource('members', MemberController::class);

    Route::get('loans/pipeline', [LoanController::class, 'pipeline']);
    Route::post('loans/calculate', [LoanController::class, 'calculate']);
    Route::get('loan-types', [LoanController::class, 'loanTypes']);
    Route::post('loans/{loan}/approve', [LoanController::class, 'approve']);
    Route::apiResource('loans', LoanController::class);

    // ── Settings (super-admin only, enforced in controller) ──
    Route::prefix('settings')->group(function () {
        Route::get('profile',              [SettingController::class, 'getProfile']);
        Route::put('profile',              [SettingController::class, 'updateProfile']);
        Route::get('preferences',          [SettingController::class, 'getPreferences']);
        Route::put('preferences',          [SettingController::class, 'updatePreferences']);
        Route::apiResource('loan-types',   LoanTypeSettingController::class);
    });

    // ── Eligibility check (pre-submission) ───────────────────
    Route::post('loans/eligibility-check', [EligibilityController::class, 'check']);

    // ── Payments ─────────────────────────────────────────────
    Route::get('loans/{loan}/payments',    [PaymentController::class, 'byLoan']);
    Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);

    // ── Reports ──────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('collection',  [ReportController::class, 'collection']);
        Route::get('aging',       [ReportController::class, 'aging']);
        Route::get('outstanding', [ReportController::class, 'outstanding']);
    });

    // ── Audit Logs ───────────────────────────────────────────
    Route::prefix('audit-logs')->group(function () {
        Route::get('/',                [AuditLogController::class, 'index']);
        Route::get('/{auditLog}',      [AuditLogController::class, 'show']);
        Route::get('/for/{type}/{id}', [AuditLogController::class, 'forRecord']);
    });

    // ── Beneficiaries ────────────────────────────────────────
    Route::prefix('members/{member}/beneficiaries')->group(function () {
        Route::get('/',                [BeneficiaryController::class, 'index']);
        Route::post('/',               [BeneficiaryController::class, 'store']);
        Route::post('/reorder',        [BeneficiaryController::class, 'reorder']);
        Route::get('/status',          [BeneficiaryController::class, 'status']);
        Route::get('/declaration.pdf', [BeneficiaryController::class, 'declaration']);
    });
    Route::apiResource('beneficiaries', BeneficiaryController::class)
        ->only(['show', 'update', 'destroy']);

    // ── Share Capital ─────────────────────────────────────────
    Route::get('share-capital/report', [ShareCapitalController::class, 'report']);
    Route::prefix('members/{member}/share-capital')->group(function () {
        Route::get('/',           [ShareCapitalController::class, 'index']);
        Route::post('/',          [ShareCapitalController::class, 'store']);
        Route::get('/summary',    [ShareCapitalController::class, 'summary']);
        Route::get('/ledger.pdf', [ShareCapitalController::class, 'pdf']);
    });
    Route::delete('share-capital/{shareCapital}', [ShareCapitalController::class, 'destroy']);

    // ── Loan Restructuring ────────────────────────────────────
    Route::prefix('loans/{loan}/restructurings')->group(function () {
        Route::get('/',               [RestructuringController::class, 'index']);
        Route::post('/',              [RestructuringController::class, 'store']);
        Route::post('/preview',       [RestructuringController::class, 'preview']);
        Route::get('/{restructuring}',[RestructuringController::class, 'show']);
    });

    // ── Notifications ─────────────────────────────────────────
    Route::prefix('notification-logs')->group(function () {
        Route::get('/',          [NotificationController::class, 'index']);
        Route::get('/settings',  [NotificationController::class, 'settings']);
        Route::put('/settings',  [NotificationController::class, 'updateSettings']);
        Route::post('/test-sms', [NotificationController::class, 'testSms']);
    });

    // ── PDF Loan Packet ───────────────────────────────────────
    Route::get('loans/{loan}/packet.pdf', [LoanPacketController::class, 'download']);

    // ── Dashboard analytics ───────────────────────────────────
    Route::get('dashboard', [DashboardController::class, 'index']);

    // ── User Management (super-admin only) ────────────────────
    Route::prefix('users')->group(function () {
        Route::get('/',                              [UserManagementController::class, 'index']);
        Route::post('/',                             [UserManagementController::class, 'store']);
        Route::get('/{userAccount}',                 [UserManagementController::class, 'show']);
        Route::put('/{userAccount}',                 [UserManagementController::class, 'update']);
        Route::post('/{userAccount}/toggle-active',  [UserManagementController::class, 'toggleActive']);
        Route::post('/{userAccount}/reset-password', [UserManagementController::class, 'resetPassword']);
    });

    // ── Member Portal Accounts (admin management) ─────────────────
    Route::prefix('member-portal-accounts')->group(function () {
        Route::get('/',                              [MemberPortalAccountController::class, 'index']);
        Route::post('/',                             [MemberPortalAccountController::class, 'store']);
        Route::get('/{memberPortalAccount}',         [MemberPortalAccountController::class, 'show']);
        Route::put('/{memberPortalAccount}',         [MemberPortalAccountController::class, 'update']);
        Route::post('/{memberPortalAccount}/toggle-active',  [MemberPortalAccountController::class, 'toggleActive']);
        Route::post('/{memberPortalAccount}/reset-password', [MemberPortalAccountController::class, 'resetPassword']);
    });

    // ── Billing ───────────────────────────────────────────────
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

});
