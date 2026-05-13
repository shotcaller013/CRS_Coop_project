<template>
  <Teleport to="body">
    <div v-if="visible" class="modal-overlay" @click.self="close">
      <div class="modal" style="max-width:480px">
        <div class="modal-header">
          <span class="modal-title">Generate billing statement</span>
          <button class="close-btn" :disabled="store.saving" @click="close">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-grid">

            <div class="form-group full">
              <label class="form-label">Company <span class="req">*</span></label>
              <select v-if="companies.length" v-model="form.company_id"
                class="form-select" :class="{ invalid: errors.company_id }">
                <option value="" disabled>Select company…</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <input v-else v-model="form.company_name" class="form-input"
                placeholder="Company name" :class="{ invalid: errors.company_id }" />
              <span v-if="errors.company_id" class="err-msg">{{ errors.company_id }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Period start <span class="req">*</span></label>
              <input v-model="form.billing_period_start" type="date" class="form-input"
                :class="{ invalid: errors.billing_period_start }" />
              <span v-if="errors.billing_period_start" class="err-msg">{{ errors.billing_period_start }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Period end <span class="req">*</span></label>
              <input v-model="form.billing_period_end" type="date" class="form-input"
                :class="{ invalid: errors.billing_period_end }"
                :min="form.billing_period_start" />
              <span v-if="errors.billing_period_end" class="err-msg">{{ errors.billing_period_end }}</span>
            </div>

            <div class="form-group full">
              <label class="form-label">Notes</label>
              <textarea v-model="form.notes" class="form-textarea" rows="2"
                placeholder="Optional notes for this billing cycle"></textarea>
            </div>

            <div class="form-group full">
              <div class="info-box">
                The system will include all <strong>PENDING</strong> and <strong>OVERDUE</strong>
                amortization periods for members of the selected company whose due dates fall
                within the billing period. The bill starts as a <strong>DRAFT</strong> —
                review the line items before issuing.
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" :disabled="store.saving" @click="close">Cancel</button>
          <button class="btn btn-primary" :disabled="store.saving" @click="submit">
            <span v-if="store.saving" class="spinner-sm"></span>
            Generate bill
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useBillStore } from '@/stores/bill.store'
import { useToast }     from '@/composables/useToast'
import api from '@/services/api'

const props = defineProps({ visible: { type: Boolean, required: true } })
const emit  = defineEmits(['update:visible', 'created'])

const store     = useBillStore()
const toast     = useToast()
const companies = ref([])

const form   = reactive({ company_id: '', company_name: '', billing_period_start: '', billing_period_end: '', notes: '' })
const errors = reactive({})

function close() { if (!store.saving) emit('update:visible', false) }

watch(() => props.visible, v => {
  if (!v) return
  Object.keys(errors).forEach(k => delete errors[k])
  Object.assign(form, { company_id: '', company_name: '', billing_period_start: '', billing_period_end: '', notes: '' })
})

onMounted(async () => {
  try {
    const res = await api.get('/companies')
    companies.value = res.data?.data ?? res.data ?? []
  } catch { /* no companies endpoint — use name field */ }
})

async function submit() {
  Object.keys(errors).forEach(k => delete errors[k])
  if (!form.company_id && !form.company_name) { errors.company_id = 'Required'; return }
  if (!form.billing_period_start)             { errors.billing_period_start = 'Required'; return }
  if (!form.billing_period_end)               { errors.billing_period_end = 'Required'; return }

  try {
    const payload = {
      company_id:           form.company_id   || undefined,
      company_name:         form.company_name || undefined,
      billing_period_start: form.billing_period_start,
      billing_period_end:   form.billing_period_end,
      notes:                form.notes || undefined,
    }
    const res = await store.create(payload)
    toast.success(res.message ?? 'Bill generated.')
    emit('update:visible', false)
    emit('created', res.data)
  } catch (e) {
    Object.assign(errors, e?.response?.data?.errors ?? {})
    toast.error(e?.response?.data?.message || 'Could not generate bill.')
  }
}
</script>

<style scoped>
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px; }
.form-group.full { grid-column: 1 / -1; }
@media (max-width: 540px) {
  .form-grid { grid-template-columns: 1fr; }
  .form-group.full { grid-column: 1; }
}
.req     { color: var(--red); }
.err-msg { font-size: 11px; color: var(--red); margin-top: 2px; }
.invalid { border-color: var(--red) !important; }
.info-box {
  font-size: 12px; padding: 10px 12px; background: var(--surface-2);
  border-radius: 6px; border-left: 3px solid var(--crs-red); line-height: 1.6;
  color: var(--ink-soft);
}
.close-btn {
  background: none; border: none; cursor: pointer; color: var(--ink-muted);
  padding: 4px; border-radius: 4px; display: flex; align-items: center;
}
.close-btn:hover { background: var(--surface-2); color: var(--ink); }
.close-btn svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; }
.spinner-sm {
  width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
