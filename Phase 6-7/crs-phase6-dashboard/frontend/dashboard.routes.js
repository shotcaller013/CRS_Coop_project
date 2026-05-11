// src/router/routes/dashboard.routes.js
export const dashboardRoutes = [
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/pages/DashboardPage.vue'),
    meta: { title: 'Dashboard' },
  },
]

// ── src/router/index.js ──────────────────────────────────────
// import { dashboardRoutes } from './routes/dashboard.routes'
// const routes = [ ...dashboardRoutes, /* ...existing */ ]

// ── Chart.js install ──────────────────────────────────────────
// DashboardPage.vue imports Chart from 'chart.js/auto'
// Run: npm install chart.js
// The 'chart.js/auto' import registers all chart types automatically.

// ── Auth integration ─────────────────────────────────────────
// DashboardPage.vue has a placeholder for role checking:
//   const isManagerOrAbove = computed(() => true)
// Replace with your actual auth store:
//   import { useAuthStore } from '@/stores/auth.store'
//   const auth = useAuthStore()
//   const isManagerOrAbove = computed(() =>
//     auth.hasAnyRole(['manager','super-admin'])
//   )
//
// The share capital widget is gated behind isManagerOrAbove.
// All other widgets are visible to all roles.

// ── App.vue sidebar already has / pointing to Dashboard ──────
// No sidebar change needed — the existing nav item covers it.
