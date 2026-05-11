// src/router/routes/user.routes.js
export const userRoutes = [
  {
    path: '/users',
    name: 'users.index',
    component: () => import('@/pages/users/UserManagementPage.vue'),
    meta: {
      title: 'User Management',
      requiresRole: 'super-admin',
    },
  },
]

// ── src/router/index.js ──────────────────────────────────────
// import { userRoutes } from './routes/user.routes'
// const routes = [ ...userRoutes, /* ...existing */ ]

// ── App.vue sidebar — add under Administration ───────────────
// This is already in the sidebar built in Phase 5!
// The Administration group already has:
//   Settings  → /settings
//   Audit log → /audit-logs
//
// Add User management between them:
//   <NavItem to="/users" icon="◎" label="User management" :collapsed="isCollapsed" />

// ── Auth store — wire up currentUserId ───────────────────────
// In UserManagementPage.vue, replace:
//   const currentUserId = ref(null)
// with:
//   import { useAuthStore } from '@/stores/auth.store'
//   const auth = useAuthStore()
//   const currentUserId = computed(() => auth.user?.id)
//
// And pass it to UserTable:
//   :current-user-id="currentUserId"
//
// In UserTable.vue, replace the isCurrentUser function:
//   function isCurrentUser(id) { return id === props.currentUserId }

// ── must_change_password frontend guard ──────────────────────
// In your auth flow (after login), check the flag and redirect:
// if (user.must_change_password) {
//   router.push('/change-password')
// }
// Build a simple ChangePasswordPage.vue that:
//   1. Calls PUT /api/v1/auth/password with { current_password, new_password }
//   2. On success, updates must_change_password = false
// The route should be accessible even without full auth (just a valid token).
