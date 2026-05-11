// src/stores/user.store.js
import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import { userService } from '@/services/user.service'

export const useUserStore = defineStore('userManagement', () => {
  const users   = ref([])
  const meta    = ref(null)
  const loading = ref(false)
  const saving  = ref(false)

  const filters = reactive({ is_active: null, role: null, search: '' })

  async function fetchUsers() {
    loading.value = true
    try {
      const params = { ...filters }
      Object.keys(params).forEach(k => { if (params[k] === null || params[k] === '') delete params[k] })
      const res  = await userService.index(params)
      users.value = res.data.data
      meta.value  = res.data.meta
    } finally { loading.value = false }
  }

  async function createUser(payload) {
    saving.value = true
    try {
      const res = await userService.store(payload)
      await fetchUsers()
      return res.data
    } finally { saving.value = false }
  }

  async function updateUser(id, payload) {
    saving.value = true
    try {
      const res = await userService.update(id, payload)
      await fetchUsers()
      return res.data
    } finally { saving.value = false }
  }

  async function toggleActive(id) {
    const res = await userService.toggleActive(id)
    await fetchUsers()
    return res.data
  }

  async function resetPassword(id) {
    const res = await userService.resetPassword(id)
    return res.data // contains temp_password
  }

  function resetFilters() {
    filters.is_active = null
    filters.role      = null
    filters.search    = ''
  }

  return {
    users, meta, loading, saving, filters,
    fetchUsers, createUser, updateUser, toggleActive, resetPassword, resetFilters,
  }
})
