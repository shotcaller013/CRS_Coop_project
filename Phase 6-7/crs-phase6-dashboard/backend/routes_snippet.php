<?php
// ── routes/api.php — add inside v1 auth:sanctum group ──────────
use App\Http\Controllers\Api\DashboardController;

Route::get('dashboard', [DashboardController::class, 'index']);

// ── No new policy, migration, or seeder needed ─────────────────
// The endpoint is accessible to any authenticated user.
// Role-sensitive widgets (share capital, overdue) are
// conditionally rendered on the frontend.
