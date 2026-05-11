<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)"
    header="Generate billing statement"
    modal :style="{ width: '480px' }" :closable="!store.saving">

    <div class="form-grid">

      <!-- Company -->
      <div class="field full">
        <label class="field-label">Company <span class="req">*</span></label>
        <Dropdown v-model="form.company_id"
          :options="companies" optionLabel="name" optionValue="id"
          placeholder="Select company" :invalid="!!errors.company_id"
          style="width:100%" />
        <small class="error">{{ errors.company_id }}</small>
      </div>

      <!-- Billing period -->
      <div class="field">
        <label class="field-label">Period start <span class="req">*</span></label>
        <Calendar v-model="form.billing_period_start" dateFormat="yy-mm-dd"
          :showIcon="true" :invalid="!!errors.billing_period_start" style="width:100%" />
        <small class="error">{{ errors.billing_period_start }}</small>
      </div>
      <div class="field">
        <label class="field-label">Period end <span class="req">*</span></label>
        <Calendar v-model="form.billing_period_end" dateFormat="yy-mm-dd"
          :showIcon="true" :minDate="form.billing_period_start"
          :invalid="!!errors.billing_period_end" style="width:100%" />
        <small class="error">{{ errors.billing_period_end }}</small>
      </div>

      <!-- Notes -->
      <div class="field full">
        <label class="field-label">Notes</label>
        <Textarea v-model="form.notes" rows="2" style="width:100%" autoResize
          placeholder="Optional notes for this billing cycle" />
      </div>

      <!-- Info box -->
      <div class="field full">
        <div class="info-box">
          The system will include all <strong>PENDING</strong> and <strong>OVERDUE</strong>
          amortization periods for members of the selected company whose due dates fall
          within the billing period. The bill starts as a <strong>DRAFT</strong> —
          review the line items before issuing.
        </div>
      </div>

    </div>

    <template #footer>
      <Button label="Cancel" text @click="emit('update:visible', false)" :disabled="store.saving" />
      <Button label="Generate bill" icon="pi pi-file" @click="submit" :loading="store.saving" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import Dialog   from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'
import Button   from 'primevue/button'
import { useBillStore } from '@/stores/bill.store'
import { useToast }     from '@/composables/useToast'
import api from '@/services/api'

const props = defineProps({ visible: { type: Boolean, required: true } })
const emit  = defineEmits(['update:visible', 'created'])

const store     = useBillStore()
const toast     = useToast()
const companies = ref([])

const form   = reactive({ company_id: null, billing_period_start: null, billing_period_end: null, notes: '' })
const errors = reactive({})

watch(() => props.visible, v => {
  if (!v) return
  Object.keys(errors).forEach(k => delete errors[k])
  Object.assign(form, { company_id: null, billing_period_start: null, billing_period_end: null, notes: '' })
})

onMounted(async () => {
  const res   = await api.get('/companies')
  companies.value = res.data.data
})

const toDateStr = (d) => d ? new Date(d).toISOString().slice(0, 10) : null

async function submit() {
  Object.keys(errors).forEach(k => delete errors[k])
  if (!form.company_id)           errors.company_id           = 'Required'
  if (!form.billing_period_start) errors.billing_period_start = 'Required'
  if (!form.billing_period_end)   errors.billing_period_end   = 'Required'
  if (Object.keys(errors).length) return

  try {
    const res = await store.create({
      company_id:           form.company_id,
      billing_period_start: toDateStr(form.billing_period_start),
      billing_period_end:   toDateStr(form.billing_period_end),
      notes: form.notes || null,
    })
    toast.success(res.message)
    emit('update:visible', false)
    emit('created', res.data)
  } catch (e) {
    const errs = e?.response?.data?.errors ?? {}
    Object.assign(errors, errs)
    toast.error(e?.response?.data?.message || 'Could not generate bill.')
  }
}
</script>

<style scoped>
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px 16px; }
.field     { display:flex; flex-direction:column; gap:4px; }
.field.full{ grid-column:1/-1; }
.field-label{font-size:12px;font-weight:500;color:var(--text-color-secondary);}
.req       { color:var(--red-500); }
.error     { font-size:11px;color:var(--red-500);min-height:14px; }
.info-box  { font-size:12px;padding:10px 12px;background:var(--surface-ground);border-radius:6px;border-left:3px solid var(--primary-color);line-height:1.6; }
</style>
