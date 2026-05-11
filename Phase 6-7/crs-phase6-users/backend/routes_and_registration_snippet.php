<?php
// ── routes/api.php — add inside v1 auth:sanctum group ──────────
use App\Http\Controllers\Api\UserManagementController;

Route::prefix('users')->group(function () {
    Route::get('/',                            [UserManagementController::class, 'index']);
    Route::post('/',                           [UserManagementController::class, 'store']);
    Route::get('/{userAccount}',               [UserManagementController::class, 'show']);
    Route::put('/{userAccount}',               [UserManagementController::class, 'update']);
    Route::post('/{userAccount}/toggle-active',[UserManagementController::class, 'toggleActive']);
    Route::post('/{userAccount}/reset-password',[UserManagementController::class, 'resetPassword']);
});

// ── app/Providers/AuthServiceProvider.php ─────────────────────
// Add to $policies:
\App\Models\User::class => \App\Policies\UserManagementPolicy::class,

// ── app/Http/Kernel.php ────────────────────────────────────────
// Register middleware aliases in $middlewareAliases (or $routeMiddleware):
'check.active'     => \App\Http\Middleware\CheckUserActive::class,
'update.last.login'=> \App\Http\Middleware\UpdateLastLogin::class,

// Add both to the api middleware group so they run on every authenticated request:
// In $middlewareGroups['api'], after 'auth:sanctum' add:
//   \App\Http\Middleware\CheckUserActive::class,
//   \App\Http\Middleware\UpdateLastLogin::class,

// ── No new seeder needed ───────────────────────────────────────
// UserManagementPolicy checks hasRole('super-admin') directly.
// The super-admin role was seeded in Phase 1.
