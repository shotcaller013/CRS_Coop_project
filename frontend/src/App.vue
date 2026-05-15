<template>
  <router-view v-if="route.meta.public" />

  <div v-else class="app-shell">
    <!-- Mobile top bar -->
    <div class="mobile-topbar">
      <button class="hamburger" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="3" y1="6"  x2="21" y2="6"/>
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <span class="mobile-brand-name">CRS Credit Coop</span>
    </div>

    <!-- Sidebar overlay (mobile) -->
    <div v-if="sidebarOpen" class="sidebar-overlay" @click="sidebarOpen = false"></div>

    <aside class="sidebar" :class="{ 'mobile-open': sidebarOpen }">
      <div class="sidebar-brand">
        <div class="brand-logo">CRS</div>
        <div>
          <div class="brand-name">CRS Holdings<br>Employees Credit Coop</div>
          <div class="brand-sub">Mandaue City · Cebu</div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section">Overview</div>
        <router-link to="/" class="nav-item" exact-active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
          Dashboard
        </router-link>

        <div class="nav-section">People</div>
        <router-link to="/members" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          Members
          <span v-if="pendingCount > 0" class="nav-badge">{{ pendingCount }}</span>
        </router-link>
        <router-link to="/beneficiaries" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          Beneficiaries
        </router-link>

        <div class="nav-section">Loans</div>
        <router-link to="/loans" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          Applications
        </router-link>
        <router-link to="/eligibility" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          Eligibility
        </router-link>
        <router-link to="/pipeline" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
          Pipeline
        </router-link>
        <router-link to="/releasing" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Releasing
        </router-link>
        <router-link to="/monitoring" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          Monitoring
        </router-link>
        <router-link to="/restructuring" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          Restructuring
        </router-link>
        <router-link to="/loan-packet" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Loan Packet
        </router-link>
        <router-link to="/payments" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          Payments
        </router-link>

        <div class="nav-section">Finance</div>
        <router-link to="/share-capital" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          Share Capital
        </router-link>
        <router-link to="/billing" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
          Billing
        </router-link>

        <div class="nav-section">Reports</div>
        <router-link to="/reports" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="12" y2="17"/></svg>
          Reports
        </router-link>

        <div class="nav-section">System</div>
        <router-link to="/audit-logs" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><line x1="8" y1="9" x2="10" y2="9"/></svg>
          Audit Logs
        </router-link>
        <router-link to="/notifications" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          Notifications
        </router-link>
        <router-link to="/users" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Users
        </router-link>
        <router-link to="/settings" class="nav-item" active-class="nav-active">
          <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Settings
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <div class="user-card">
          <div class="user-av">{{ initials }}</div>
          <div class="user-info">
            <div class="user-name">{{ user?.name ?? 'User' }}</div>
            <div class="user-role">{{ user?.email ?? '' }}</div>
          </div>
          <button class="logout-btn" title="Sign out" @click="handleLogout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          </button>
        </div>
      </div>
    </aside>

    <main class="main-area">
      <router-view />
    </main>

    <div class="toast-container">
      <div v-for="t in toasts" :key="t.id" :class="['toast', `toast-${t.type}`]">
        {{ t.message }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from './composables/useToast'
import { useAuth } from './composables/useAuth'
import { api } from './composables/useApi'
import { useMembersStats } from './composables/useMembersStats'

const { toasts } = useToast()
const { user, clearSession } = useAuth()
const route  = useRoute()
const router = useRouter()
const { pendingCount } = useMembersStats()

const sidebarOpen = ref(false)
watch(route, () => { sidebarOpen.value = false })

const initials = computed(() => {
  const name = user.value?.name ?? ''
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) || '?'
})

async function handleLogout() {
  try { await api.logout() } catch {}
  clearSession()
  router.replace('/login')
}
</script>

<style scoped>
.app-shell {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: var(--surface);
}

/* ── Sidebar ───────────────────────────────────────────── */
.sidebar {
  width: var(--sidebar-w);
  min-width: var(--sidebar-w);
  background: var(--crs-red);
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0; top: 0; bottom: 0;
  z-index: 100;
  box-shadow: 4px 0 20px rgba(139,26,26,0.25);
}

.sidebar-brand {
  padding: 20px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  display: flex;
  align-items: center;
  gap: 11px;
}
.brand-logo {
  width: 44px; height: 44px;
  background: white;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-mono);
  font-size: 13px; font-weight: 700;
  color: var(--crs-red);
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  flex-shrink: 0;
}
.brand-name {
  font-size: 11px; font-weight: 700;
  color: white;
  letter-spacing: 0.04em;
  line-height: 1.3;
  text-transform: uppercase;
}
.brand-sub { font-size: 9.5px; color: rgba(255,255,255,0.55); margin-top: 2px; }

.sidebar-nav { flex: 1; padding: 10px 0; overflow-y: auto; }

.nav-section {
  padding: 14px 18px 4px;
  font-size: 9px; font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.35);
}

.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 18px;
  color: rgba(255,255,255,0.65);
  font-size: 13px; font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: all var(--tx);
  position: relative;
}
.nav-item:hover { color: white; background: rgba(255,255,255,0.08); }
.nav-active {
  color: white !important;
  background: rgba(255,255,255,0.12) !important;
  font-weight: 600;
}
.nav-active::after {
  content: '';
  position: absolute;
  right: 0; top: 6px; bottom: 6px;
  width: 3px;
  background: var(--omni-orange);
  border-radius: 3px 0 0 3px;
}

.nav-badge {
  margin-left: auto;
  margin-right: 8px;
  font-size: 10px; font-weight: 700;
  padding: 2px 7px; border-radius: 99px;
  background: var(--omni-orange); color: white;
}

.nav-icon {
  width: 17px; height: 17px;
  flex-shrink: 0;
  opacity: 0.85;
  stroke: currentColor;
  fill: none;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

/* ── Footer ────────────────────────────────────────────── */
.sidebar-footer {
  padding: 14px 18px;
  border-top: 1px solid rgba(255,255,255,0.1);
}
.user-card { display: flex; align-items: center; gap: 10px; }
.user-av {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: var(--omni-orange);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700;
  color: white;
  flex-shrink: 0;
}
.user-info { flex: 1; min-width: 0; }
.user-name { font-size: 12.5px; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role { font-size: 10.5px; color: rgba(255,255,255,0.45); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.logout-btn {
  background: none; border: none; cursor: pointer;
  color: rgba(255,255,255,0.4);
  padding: 4px; border-radius: 5px;
  display: flex; align-items: center;
  transition: color var(--tx);
  flex-shrink: 0;
}
.logout-btn svg { width: 15px; height: 15px; }
.logout-btn:hover { color: #fca5a5; }

/* ── Main ──────────────────────────────────────────────── */
.main-area {
  margin-left: var(--sidebar-w);
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background: var(--surface);
}

/* ── Mobile topbar (hidden on desktop) ─────────────────── */
.mobile-topbar { display: none; }
.sidebar-overlay { display: none; }

/* ── Responsive ─────────────────────────────────────────── */
@media (max-width: 768px) {
  .app-shell { flex-direction: column; }

  /* Mobile topbar */
  .mobile-topbar {
    display: flex; align-items: center; gap: 12px;
    padding: 0 16px; height: 52px;
    background: var(--crs-red); color: white;
    position: fixed; top: 0; left: 0; right: 0;
    z-index: 300; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  }
  .hamburger {
    background: none; border: none; cursor: pointer;
    color: white; display: flex; align-items: center; padding: 4px;
  }
  .hamburger svg { width: 22px; height: 22px; }
  .mobile-brand-name {
    font-size: 14px; font-weight: 700; color: white;
    letter-spacing: 0.02em;
  }

  /* Sidebar slides in/out */
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.25s ease;
    z-index: 301;
  }
  .sidebar.mobile-open { transform: translateX(0); }

  /* Overlay behind sidebar */
  .sidebar-overlay {
    display: block; position: fixed; inset: 0;
    background: rgba(0,0,0,0.5); z-index: 300;
  }

  /* Main area fills full width, offset by topbar height */
  .main-area {
    margin-left: 0;
    padding-top: 52px;
  }
}
</style>
