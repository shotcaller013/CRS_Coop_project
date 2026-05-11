<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)"
    header="Upload remittance" modal :style="{ width: '460px' }" :closable="!store.saving">

    <div class="rem-info" v-if="bill">
      <div class="ri-row"><span>Bill no.</span><span class="mono fw-500">{{ bill.bill_no }}</span></div>
      <div class="ri-row"><span>Total amount</span><span class="mono fw-500">{{ peso(bill.total_amount) }}</span></div>
      <div class="ri-row"><span>Already remitted</span><span class="mono" style="color:var(--green-600)">{{ peso(bill.amount_remitted) }}</span></div>
      <div class="ri-row fw-500"><span>Balance due</span><span class="mono" style="color:var(--red-500)">{{ peso(bill.balance) }}</span></div>
    </div>

    <div class="form-grid" style="margin-top:14px">

      <!-- Amount -->
      <div class="field">
        <label class="field-label">Amount remitted <span class="req">*</span></label>
        <InputNumber v-model="form.amount"
          mode="currency" currency="PHP" locale="en-PH"
          :max="bill?.balance" :invalid="!!errors.amount" style="width:100%" />
        <small class="error">{{ errors.amount }}</small>
      </div>

      <!-- OR number -->
      <div class="field">
        <label class="field-label">O.R. number</label>
        <InputText v-model="form.or_number" style="width:100%" placeholder="Official receipt #" />
      </div>

      <!-- Date -->
      <div class="field">
        <label class="field-label">Remittance date <span class="req">*</span></label>
        <Calendar v-model="form.remittance_date" dateFormat="yy-mm-dd"
          :showIcon="true" :maxDate="new Date()" style="width:100%" />
        <small class="error">{{ errors.remittance_date }}</small>
      </div>

      <!-- File upload -->
      <div class="field">
        <label class="field-label">Remittance document</label>
        <input type="file" ref="fileInput" accept=".pdf,.jpg,.jpeg,.png"
          @change="onFileChange" class="file-input" />
        <small class="hint">PDF, JPG, or PNG — max 10MB</small>
      </div>

      <!-- Notes -->
      <div class="field full">
        <label class="field-label">Notes</label>
        <Textarea v-model="form.notes" rows="2" style="width:100%" autoResize />
      </div>

    </div>

    <template #footer>
      <Button label="Cancel" text @click="emit('update:visible', false)" :disabled="store.saving" />
      <Button label="Upload remittance" icon="pi pi-upload"
        severity="success" @click="submit" :loading="store.saving" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import Dialog      from 'primevue/dialog'
import InputText   from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Calendar    from 'primevue/calendar'
import Textarea    from 'primevue/textarea'
import Button      from 'primevue/button'
import { useBillStore } from '@/stores/bill.store'
import { useCurrency }  from '@/composables/useCurrency'
import { useToast }     from '@/composables/useToast'

const props = defineProps({
  visible: { type: Boolean, required: true },
  bill:    { type: Object,  default: null  },
})
const emit  = defineEmits(['update:visible', 'uploaded'])

const store   = useBillStore()
const { formatCurrency } = useCurrency()
const toast   = useToast()
const peso    = (n) => formatCurrency(n)

const fileInput = ref(null)
const form      = reactive({ amount: null, or_number: '', remittance_date: new Date(), notes: '' })
const errors    = reactive({})
let   selectedFile = null

watch(() => props.visible, v => {
  if (!v) return
  Object.keys(errors).forEach(k => delete errors[k])
  Object.assign(form, { amount: null, or_number: '', remittance_date: new Date(), notes: '' })
  selectedFile = null
  if (fileInput.value) fileInput.value.value = ''
})

function onFileChange(e) { selectedFile = e.target.files[0] ?? null }

async function submit() {
  Object.keys(errors).forEach(k => delete errors[k])
  if (!form.amount || form.amount <= 0) { errors.amount = 'Amount is required'; return }
  if (!form.remittance_date)            { errors.remittance_date = 'Required'; return }

  try {
    const res = await store.uploadRemittance(props.bill.id, {
      amount:          form.amount,
      or_number:       form.or_number || undefined,
      remittance_date: new Date(form.remittance_date).toISOString().slice(0, 10),
      notes:           form.notes || undefined,
    }, selectedFile)
    toast.success(res.message)
    emit('update:visible', false)
    emit('uploaded', res.data)
  } catch (e) {
    Object.assign(errors, e?.response?.data?.errors ?? {})
    toast.error(e?.response?.data?.message || 'Upload failed.')
  }
}
</script>

<style scoped>
.rem-info   { display:flex;flex-direction:column;gap:4px;background:var(--surface-ground);border-radius:6px;padding:10px 12px;font-size:12px; }
.ri-row     { display:flex;justify-content:space-between; }
.form-grid  { display:grid;grid-template-columns:1fr 1fr;gap:12px 16px; }
.field      { display:flex;flex-direction:column;gap:4px; }
.field.full { grid-column:1/-1; }
.field-label{ font-size:12px;font-weight:500;color:var(--text-color-secondary); }
.req        { color:var(--red-500); }
.error      { font-size:11px;color:var(--red-500);min-height:14px; }
.hint       { font-size:11px;color:var(--text-color-secondary); }
.file-input { font-size:12px;padding:4px 0; }
</style>
