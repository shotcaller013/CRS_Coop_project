import { createRouter, createWebHistory } from 'vue-router'
import { memberRoutes }    from './routes/member.routes'
import { loanRoutes }      from './routes/loan.routes'
import { settingsRoutes }  from './routes/settings.routes'
import { reportRoutes }    from './routes/report.routes'
import { dashboardRoutes } from './routes/dashboard.routes'
import { billingRoutes }   from './routes/billing.routes'
import { userRoutes }      from './routes/user.routes'
import { portalRoutes }    from './routes/portal.routes'
import { toolsRoutes }     from './routes/tools.routes'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { public: true } },
    ...portalRoutes,

    { path: '/members',   name: 'members',   component: () => import('@/views/MembersView.vue') },
    { path: '/loans',     name: 'loans',     component: () => import('@/views/LoanOfficerView.vue') },
    { path: '/pipeline',  name: 'pipeline',  component: () => import('@/views/PipelineView.vue') },
    { path: '/releasing', name: 'releasing', component: () => import('@/views/ReleasingView.vue') },
    { path: '/monitoring',name: 'monitoring',component: () => import('@/views/MonitoringView.vue') },

    ...dashboardRoutes,
    ...memberRoutes,
    ...loanRoutes,
    ...settingsRoutes,
    ...reportRoutes,
    ...billingRoutes,
    ...userRoutes,
    ...toolsRoutes,
  ],
})

router.beforeEach((to) => {
  const token = localStorage.getItem('crs_token')
  if (!to.meta.public && !token) {
    return { name: 'login' }
  }
  if (to.name === 'login' && token) {
    return { name: 'dashboard' }
  }
})

export default router
