// src/stores/portal.store.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { portalService } from '@/services/portal.service'

const SESSION_KEY = 'crs-member-portal-session'
const TOKEN_KEY   = 'crs-member-portal-token'

export const usePortalStore = defineStore('portal', () => {
  const session  = ref(loadSession())
  const data     = ref(null)
  const loading  = ref(false)

  const isLoggedIn  = computed(() => !!session.value?.token)
  const member      = computed(() => session.value?.member ?? null)
  const access      = computed(() => session.value?.access ?? null)

  function loadSession() {
    try { return JSON.parse(sessionStorage.getItem(SESSION_KEY) || 'null') }
    catch { return null }
  }

  function saveSession(s) {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(s))
    sessionStorage.setItem(TOKEN_KEY, s.token)
    session.value = s
  }

  function clearSession() {
    sessionStorage.removeItem(SESSION_KEY)
    sessionStorage.removeItem(TOKEN_KEY)
    session.value = null
    data.value    = null
  }

  async function login(identifier, password) {
    const result = await portalService.login(identifier, password)
    saveSession(result)
    return result
  }

  async function fetchDashboard() {
    loading.value = true
    try {
      data.value = await portalService.getDashboard()
    } finally {
      loading.value = false
    }
  }

  return {
    session, data, loading, isLoggedIn, member, access,
    login, fetchDashboard, clearSession,
  }
})
