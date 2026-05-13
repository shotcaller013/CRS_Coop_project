// src/router/routes/billing.routes.js
export const billingRoutes = [
  {
    path: '/billing',
    name: 'billing.index',
    component: () => import('@/pages/billing/BillingPage.vue'),
    meta: {
      title: 'Billing',
      requiresPermission: 'view-payment',
    },
  },
]

// ── src/router/index.js ──────────────────────────────────────
// import { billingRoutes } from './routes/billing.routes'
// const routes = [ ...billingRoutes, /* ...existing */ ]

// ── App.vue sidebar — add under Payments ─────────────────────
// <NavItem to="/billing" icon="◻" label="Billing" :collapsed="isCollapsed" />

// ── Run migrations in order ──────────────────────────────────
// php artisan migrate
// This runs all 4 billing migrations:
//   2026_07_01_000010_create_bills_table
//   2026_07_01_000011_create_bill_items_table
//   2026_07_01_000012_add_billed_status_to_amortization_schedules
//   2026_07_01_000013_create_bill_remittances_table
//
// ── Add policy ───────────────────────────────────────────────
// In AuthServiceProvider::$policies:
//   \App\Models\Bill::class => \App\Policies\BillPolicy::class,
//
// ── Add routes ───────────────────────────────────────────────
// Paste routes_snippet.php contents into routes/api.php
//
// ── Storage symlink (for remittance file downloads) ──────────
// php artisan storage:link
// Remittance files are stored in storage/app/remittances/ (private, served via controller)
