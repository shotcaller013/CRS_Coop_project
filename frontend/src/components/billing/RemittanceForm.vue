<template>
  <Teleport to="body">
    <div v-if="visible" class="modal-overlay" @click.self="close">
      <div class="modal" style="max-width:460px">
        <div class="modal-header">
          <span class="modal-title">Upload remittance</span>
          <button class="close-btn" :disabled="store.saving" @click="close">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <div class="modal-body">
          <div v-if="bill" class="bill-info">
            <div class="bi-row"><span>Bill no.</span>   <span class="mono fw-600">{{ bill.bill_no }}</span></div>
            <div class="bi-row"><span>Total amount</span><span class="mono fw-600">{{ peso(bill.total_amount) }}</span></div>
            <div class="bi-row"><span>Already remitted</span><span class="mono" style="color:var(--green)">{{ peso(bill.amount_remitted) }}</span></div>
            <div class="bi-row fw-600"><span>Balance due</span><span class="mono" style="color:var(--red)">{{ peso(bill.balance) }}</span></div>
          </div>

          <div class="form-grid mt-12">

            <div class="form-group">
              <label class="form-label">Amount remitted <span class="req">*</span></label>
              <input v-model.number="form.amount" type="number" step="0.01"
                :max="bill?.balance" class="form-input" :class="{ invalid: errors.amount }"
                placeholder="0.00" />
              <span v-if="errors.amount" class="err-msg">{{ errors.amount }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">O.R. number</label>
              <input v-model="form.or_number" class="form-input" placeholder="Official receipt #" />
            </div>

            <div class="form-group">
              <label class="form-label">Remittance date <span class="req">*</span></label>
              <input v-model="form.remittance_date" type="date" class="form-input"
                :class="{ invalid: errors.remittance_date }"
                :max="today" />
              <span v-if="errors.remittance_date" class="err-msg">{{ errors.remittance_date }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Remittance document</label>
              <input type="file" ref="fileInput" accept=".pdf,.jpg,.jpeg,.png"
                class="form-input file-input" @change="onFileChange" />
              <span class="hint-msg">PDF, JPG, or PNG — max 10MB</span>
            </div>

            <div class="form-group full">
              <label class="form-label">Notes</label>
              <textarea v-model="form.notes" class="form-textarea" rows="2"></textarea>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" :disabled="store.saving" @click="close">Cancel</button>
          <button class="btn btn-primary" style="background:var(--green);border-color:var(--green)"
            :disabled="store.saving" @click="submit">
            <span v-if="store.saving" class="spinner-sm"></span>
            Upload remittance
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { useBillStore } from '@/stores/bill.store'
import { useCurrency }  from '@/composables/useCurrency'
import { useToast }     from '@/composables/useToast'

const props = defineProps({
  visible: { type: Boolean, required: true },
  bill:    { type: Object,  default: null },
})
const emit = defineEmits(['update:visible', 'uploaded'])

const store   = useBillStore()
const { formatCurrency } = useCurrency()
const toast   = useToast()
const peso    = n => formatCurrency(n ?? 0)
const today   = computed(() => new Date().toISOString().slice(0, 10))

const fileInput  = ref(null)
const form       = reactive({ amount: null, or_number: '', remittance_date: '', notes: '' })
const errors     = reactive({})
let   selectedFile = null

function close() { if (!store.saving) emit('update:visible', false) }

watch(() => props.visible, v => {
  if (!v) return
  Object.keys(errors).forEach(k => delete errors[k])
  Object.assign(form, { amount: null, or_number: '', remittance_date: today.value, notes: '' })
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
      remittance_date: form.remittance_date,
      notes:           form.notes || undefined,
    }, selectedFile)
    toast.success(res.message ?? 'Remittance uploaded.')
    emit('update:visible', false)
    emit('uploaded', res.data)
  } catch (e) {
    Object.assign(errors, e?.response?.data?.errors ?? {})
    toast.error(e?.response?.data?.message || 'Upload failed.')
  }
}
</script>

<style scoped>
.bill-info { display: flex; flex-direction: column; gap: 4px; background: var(--surface-2);
             border-radius: 7px; padding: 11px 14px; font-size: 12.5px; }
.bi-row    { display: flex; justify-content: space-between; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px; }
.form-group.full { grid-column: 1 / -1; }
@media (max-width: 540px) {
  .form-grid { grid-template-columns: 1fr; }
  .form-group.full { grid-column: 1; }
}
.req      { color: var(--red); }
.err-msg  { font-size: 11px; color: var(--red); margin-top: 2px; }
.hint-msg { font-size: 11px; color: var(--ink-muted); margin-top: 2px; }
.invalid  { border-color: var(--red) !important; }
.file-input { padding: 6px 8px; font-size: 12px; cursor: pointer; }
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
