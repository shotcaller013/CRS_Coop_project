// src/stores/bill.store.js
import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import { billService } from '@/services/bill.service'

export const useBillStore = defineStore('billing', () => {
  const bills      = ref([])
  const selected   = ref(null)
  const loading    = ref(false)
  const saving     = ref(false)
  const pagination = reactive({ currentPage:1, lastPage:1, total:0 })

  const filters = reactive({ company_id:null, status:null, date_from:null, date_to:null, per_page:20 })

  async function fetchBills(page = 1) {
    loading.value = true
    try {
      const params = { ...filters, page }
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k] })
      const res  = await billService.index(params)
      bills.value = res.data.data
      Object.assign(pagination, res.data.meta)
    } finally { loading.value = false }
  }

  async function fetchOne(id) {
    loading.value = true
    try {
      const res    = await billService.show(id)
      selected.value = res.data.data
      return selected.value
    } finally { loading.value = false }
  }

  async function create(payload) {
    saving.value = true
    try {
      const res = await billService.store(payload)
      await fetchBills()
      return res.data
    } finally { saving.value = false }
  }

  async function issue(id) {
    const res = await billService.issue(id)
    if (selected.value?.id === id) await fetchOne(id)
    await fetchBills()
    return res.data
  }

  async function uploadRemittance(id, data, file) {
    saving.value = true
    try {
      const res = await billService.uploadRemittance(id, data, file)
      if (selected.value?.id === id) await fetchOne(id)
      return res.data
    } finally { saving.value = false }
  }

  async function settle(id) {
    const res = await billService.settle(id)
    if (selected.value?.id === id) await fetchOne(id)
    await fetchBills()
    return res.data
  }

  async function cancel(id) {
    const res = await billService.cancel(id)
    await fetchBills()
    return res.data
  }

  function resetFilters() {
    Object.assign(filters, { company_id:null, status:null, date_from:null, date_to:null, per_page:20 })
  }

  return {
    bills, selected, loading, saving, pagination, filters,
    fetchBills, fetchOne, create, issue, uploadRemittance, settle, cancel, resetFilters,
  }
})
