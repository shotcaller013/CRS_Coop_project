// src/stores/dashboard.store.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { dashboardService } from '@/services/dashboard.service'

export const useDashboardStore = defineStore('dashboard', () => {
  const data    = ref(null)
  const loading = ref(false)
  const error   = ref(null)

  async function fetch() {
    loading.value = true
    error.value   = null
    try {
      const res = await dashboardService.get()
      data.value  = res.data.data
    } catch (e) {
      error.value = e?.response?.data?.message || 'Failed to load dashboard.'
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, fetch }
})
