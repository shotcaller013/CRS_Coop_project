export const portalRoutes = [
  {
    path: '/portal',
    name: 'portal-login',
    component: () => import('@/pages/portal/PortalLoginPage.vue'),
    meta: { public: true },
  },
  {
    path: '/portal/dashboard',
    name: 'portal-dashboard',
    component: () => import('@/pages/portal/PortalDashboardPage.vue'),
    meta: { public: true },
  },
]
