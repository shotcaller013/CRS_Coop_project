export const toolsRoutes = [
  {
    path: '/eligibility',
    name: 'eligibility',
    component: () => import('@/pages/eligibility/EligibilityPage.vue'),
    meta: { title: 'Eligibility Check' },
  },
  {
    path: '/loan-packet',
    name: 'loan-packet',
    component: () => import('@/pages/loan-packet/LoanPacketPage.vue'),
    meta: { title: 'Loan Packet' },
  },
  {
    path: '/restructuring',
    name: 'restructuring',
    component: () => import('@/pages/restructuring/RestructuringPage.vue'),
    meta: { title: 'Loan Restructuring' },
  },
  {
    path: '/share-capital',
    name: 'share-capital',
    component: () => import('@/pages/share-capital/ShareCapitalPage.vue'),
    meta: { title: 'Share Capital' },
  },
  {
    path: '/beneficiaries',
    name: 'beneficiaries',
    component: () => import('@/pages/beneficiaries/BeneficiariesPage.vue'),
    meta: { title: 'Beneficiaries' },
  },
  {
    path: '/audit-logs',
    name: 'audit-logs',
    component: () => import('@/pages/audit-logs/AuditLogPage.vue'),
    meta: { title: 'Audit Logs' },
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('@/pages/notifications/NotificationsPage.vue'),
    meta: { title: 'Notifications' },
  },
]
