<template>
  <div class="page-wrap">
    <header class="topbar">
      <span class="topbar-page">Loan Restructuring</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Modify existing loan terms and preview new amortization</span>
    </header>

    <div class="page-content">
      <!-- Loan list -->
      <div class="loan-panel">
        <div class="panel-head">
          <input v-model="loanSearch" type="text" placeholder="Search loan or member…" class="search-input" />
        </div>
        <div class="loan-items">
          <div v-if="loansLoading" class="panel-loading"><div class="spinner"></div></div>
          <div v-else-if="!filteredLoans.length" class="panel-empty">No active loans found.</div>
          <button
            v-for="loan in filteredLoans"
            :key="loan.id"
            :class="['loan-item', selected?.id === loan.id && 'loan-item-active']"
            @click="selectLoan(loan)"
          >
            <div class="li-top">
              <span class="loan-no">{{ loan.loan_no }}</span>
              <span class="status-pill">{{ loan.status }}</span>
            </div>
            <div class="li-name">{{ loan.member_name || loan.member?.full_name }}</div>
            <div class="li-meta">{{ loan.loan_type_label || loan.loan_type?.name }} · {{ peso(loan.outstanding_balance ?? loan.principal) }}</div>
          </button>
        </div>
      </div>

      <!-- Restructure workspace -->
      <div class="workspace">
        <template v-if="selected">
          <!-- Current terms -->
          <div class="terms-card">
            <div class="card-head">Current Terms</div>
            <div class="terms-grid">
              <div class="ti"><span>Loan No.</span><strong class="mono">{{ selected.loan_no }}</strong></div>
              <div class="ti"><span>Principal</span><strong>{{ peso(selected.principal) }}</strong></div>
              <div class="ti"><span>Outstanding</span><strong>{{ peso(selected.outstanding_balance ?? selected.principal) }}</strong></div>
              <div class="ti"><span>Rate / month</span><strong>{{ selected.interest_rate }}%</strong></div>
              <div class="ti"><span>Term</span><strong>{{ selected.term_months }} months</strong></div>
              <div class="ti"><span>Monthly</span><strong>{{ peso(selected.monthly_amortization) }}</strong></div>
            </div>
          </div>

          <!-- New terms form -->
          <div class="form-card">
            <div class="card-head">New Terms</div>
            <div class="restr-form">
              <div class="field">
                <label>New Principal (₱)</label>
                <input v-model.number="form.new_principal" type="number" min="0" @input="clearPreview" />
              </div>
              <div class="field">
                <label>New Rate (% / month)</label>
                <input v-model.number="form.new_rate" type="number" min="0" step="0.01" @input="clearPreview" />
              </div>
              <div class="field">
                <label>New Term (months)</label>
                <input v-model.number="form.new_term" type="number" min="1" @input="clearPreview" />
              </div>
              <div class="field">
                <label>First Due Date</label>
                <input v-model="form.first_due_date" type="date" @input="clearPreview" />
              </div>
              <div class="field field-wide">
                <label>Reason</label>
                <select v-model="form.reason">
                  <option value="">-- Select reason --</option>
                  <option>Extended term due to financial hardship</option>
                  <option>Rate adjustment per board approval</option>
                  <option>Consolidation of loans</option>
                  <option>Settlement agreement</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="field field-wide">
                <label>Notes</label>
                <input v-model="form.notes" type="text" placeholder="Additional notes…" />
              </div>
              <div class="form-actions">
                <button class="preview-btn" :disabled="!canRestructure || previewing" @click="previewRestructure">
                  {{ previewing ? 'Computing…' : 'Preview' }}
                </button>
                <button class="submit-btn" :disabled="!preview || submitting" @click="submitRestructure">
                  {{ submitting ? 'Restructuring…' : 'Confirm Restructure' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Preview -->
          <div v-if="preview" class="preview-card">
            <div class="card-head">New Amortization Preview</div>
            <div class="preview-summary">
              <div class="ps-item">
                <span>New Monthly</span>
                <strong>{{ peso(preview.monthly_amortization) }}</strong>
              </div>
              <div class="ps-item">
                <span>Total Interest</span>
                <strong>{{ peso(preview.total_interest) }}</strong>
              </div>
              <div class="ps-item">
                <span>Total Payable</span>
                <strong>{{ peso(preview.total_payable) }}</strong>
              </div>
            </div>
            <div class="sched-table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>#</th><th>Due Date</th><th class="text-right">Principal</th>
                    <th class="text-right">Interest</th><th class="text-right">Total</th>
                    <th class="text-right">Balance</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in (preview.schedule || []).slice(0, 24)" :key="row.period">
                    <td class="mono">{{ row.period }}</td>
                    <td class="mono" style="font-size:12px">{{ formatDate(row.due_date) }}</td>
                    <td class="text-right mono">{{ peso(row.principal) }}</td>
                    <td class="text-right mono">{{ peso(row.interest) }}</td>
                    <td class="text-right mono" style="font-weight:600">{{ peso(row.total) }}</td>
                    <td class="text-right mono">{{ peso(row.balance) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- History -->
          <div v-if="history.length" class="history-card">
            <div class="card-head">Restructuring History</div>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Date</th><th>Old Terms</th><th>New Terms</th><th>Reason</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="h in history" :key="h.id">
                  <td class="mono" style="font-size:11px">{{ formatDate(h.created_at) }}</td>
                  <td style="font-size:12px">{{ h.old_term_months }}mo · {{ h.old_rate }}% · {{ peso(h.old_principal) }}</td>
                  <td style="font-size:12px">{{ h.new_term_months }}mo · {{ h.new_rate }}% · {{ peso(h.new_principal) }}</td>
                  <td style="font-size:12px;color:var(--ink-muted)">{{ h.reason || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <div v-else class="ws-empty">
          <div class="empty-icon">⊖</div>
          <div class="empty-title">Select a loan to restructure</div>
          <div class="empty-sub">Choose an active loan from the left panel to begin the restructuring process.</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { loanService }  from '@/services/loan.service'
import api              from '@/services/api'
import { useCurrency }  from '@/composables/useCurrency'
import { useDate }      from '@/composables/useDate'
import { useToast }     from '@/composables/useToast'

const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()
const { success, error } = useToast()

const loans       = ref([])
const history     = ref([])
const selected    = ref(null)
const preview     = ref(null)
const loanSearch  = ref('')
const loansLoading = ref(false)
const previewing  = ref(false)
const submitting  = ref(false)

const form = ref({
  new_principal: '', new_rate: '', new_term: '', first_due_date: '', reason: '', notes: ''
})

const peso = (n) => formatCurrency(n)

const filteredLoans = computed(() => {
  const q = loanSearch.value.toLowerCase()
  return loans.value.filter(l =>
    !q ||
    (l.loan_no || '').toLowerCase().includes(q) ||
    (l.member_name || l.member?.full_name || '').toLowerCase().includes(q)
  )
})

const canRestructure = computed(() =>
  form.value.new_principal > 0 &&
  form.value.new_rate >= 0 &&
  form.value.new_term > 0 &&
  form.value.first_due_date
)

function clearPreview() {
  preview.value = null
}

async function selectLoan(loan) {
  selected.value = loan
  preview.value  = null
  history.value  = []
  form.value     = { new_principal: loan.outstanding_balance ?? loan.principal, new_rate: loan.interest_rate, new_term: '', first_due_date: '', reason: '', notes: '' }

  try {
    const res    = await api.get(`/loans/${loan.id}/restructurings`)
    history.value = res.data?.data ?? res.data ?? []
  } catch {}
}

async function previewRestructure() {
  previewing.value = true
  try {
    const res = await api.post(`/loans/${selected.value.id}/restructurings/preview`, {
      new_principal:  form.value.new_principal,
      new_rate:       form.value.new_rate,
      new_term_months: form.value.new_term,
      first_due_date: form.value.first_due_date,
    })
    preview.value = res.data?.data ?? res.data
  } catch (e) {
    error(e?.response?.data?.message || 'Preview failed.')
  } finally {
    previewing.value = false
  }
}

async function submitRestructure() {
  submitting.value = true
  try {
    await api.post(`/loans/${selected.value.id}/restructurings`, {
      new_principal:   form.value.new_principal,
      new_rate:        form.value.new_rate,
      new_term_months: form.value.new_term,
      first_due_date:  form.value.first_due_date,
      reason:          form.value.reason,
      notes:           form.value.notes,
    })
    success('Loan restructured successfully.')
    await selectLoan(selected.value)
  } catch (e) {
    error(e?.response?.data?.message || 'Restructuring failed.')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  loansLoading.value = true
  try {
    const res  = await loanService.index({ status: 'ACTIVE', per_page: 500 })
    loans.value = res.data?.data ?? res.data ?? []
  } catch {
    error('Failed to load loans.')
  } finally {
    loansLoading.value = false
  }
})
</script>

<style scoped>
.page-wrap { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.topbar {
  display: flex; align-items: center; gap: 8px;
  padding: 14px 24px; border-bottom: 1px solid var(--border);
  background: white; flex-shrink: 0;
}
.topbar-page { font-weight: 600; font-size: 14px; }
.topbar-sep  { color: var(--ink-muted); }
.topbar-sub  { font-size: 12px; color: var(--ink-muted); }

.page-content {
  flex: 1; overflow: hidden; display: grid;
  grid-template-columns: 280px 1fr;
}

/* Loan panel */
.loan-panel {
  border-right: 1px solid var(--border); display: flex; flex-direction: column; overflow: hidden;
}
.panel-head { padding: 10px; border-bottom: 1px solid var(--border); }
.search-input {
  width: 100%; border: 1px solid var(--border); border-radius: 6px;
  padding: 7px 10px; font-size: 12px; outline: none;
}
.search-input:focus { border-color: var(--crs-red); }
.loan-items    { flex: 1; overflow-y: auto; }
.panel-loading { display: flex; justify-content: center; padding: 30px; }
.panel-empty   { padding: 20px; font-size: 13px; color: var(--ink-muted); text-align: center; }

.loan-item {
  width: 100%; text-align: left; padding: 11px 14px;
  border: none; border-bottom: 1px solid var(--border);
  background: white; cursor: pointer; transition: background .1s;
}
.loan-item:hover     { background: var(--surface); }
.loan-item-active    { background: #fef2f2; border-left: 3px solid var(--crs-red); }
.li-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; }
.loan-no    { font-size: 12px; font-weight: 700; font-family: var(--font-mono); }
.status-pill { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px; background: #dcfce7; color: #166534; }
.li-name    { font-size: 12px; font-weight: 600; }
.li-meta    { font-size: 11px; color: var(--ink-muted); margin-top: 2px; }

/* Workspace */
.workspace { overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }

.terms-card, .form-card, .preview-card, .history-card {
  background: white; border: 1px solid var(--border); border-radius: 10px; overflow: hidden;
}
.card-head { padding: 12px 18px; border-bottom: 1px solid var(--border); font-size: 13px; font-weight: 700; }

.terms-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); padding: 12px 18px; gap: 12px;
}
.ti { display: flex; flex-direction: column; gap: 3px; }
.ti span   { font-size: 11px; color: var(--ink-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.ti strong { font-size: 14px; font-weight: 700; }

.restr-form {
  padding: 14px 18px;
  display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px;
}
.field { display: flex; flex-direction: column; gap: 4px; }
.field-wide { grid-column: span 2; }
.field label { font-size: 11px; font-weight: 600; color: var(--ink-muted); text-transform: uppercase; letter-spacing: .4px; }
.field input, .field select {
  border: 1px solid var(--border); border-radius: 6px;
  padding: 7px 10px; font-size: 13px; outline: none; background: white;
}
.field input:focus, .field select:focus { border-color: var(--crs-red); }
.form-actions { grid-column: span 4; display: flex; gap: 10px; justify-content: flex-end; }

.preview-btn {
  border: 1px solid var(--border); background: white; border-radius: 7px;
  padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.preview-btn:disabled { opacity: .5; cursor: not-allowed; }
.submit-btn {
  background: var(--crs-red); color: white; border: none;
  border-radius: 7px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.submit-btn:disabled { opacity: .5; cursor: not-allowed; }
.submit-btn:not(:disabled):hover { opacity: .88; }

.preview-summary {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 14px 18px;
  border-bottom: 1px solid var(--border);
}
.ps-item { display: flex; flex-direction: column; gap: 3px; }
.ps-item span   { font-size: 11px; color: var(--ink-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.ps-item strong { font-size: 15px; font-weight: 700; }

.sched-table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.data-table th {
  padding: 8px 12px; text-align: left; font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .4px; color: var(--ink-muted);
  background: var(--surface); border-bottom: 1px solid var(--border);
}
.data-table td { padding: 8px 12px; border-bottom: 1px solid var(--border); }
.data-table tr:last-child td { border-bottom: none; }
.text-right { text-align: right; }

.ws-empty {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 8px; padding: 60px;
}
.empty-icon  { font-size: 36px; color: var(--ink-muted); }
.empty-title { font-size: 15px; font-weight: 600; }
.empty-sub   { font-size: 13px; color: var(--ink-muted); text-align: center; }

.spinner { width: 24px; height: 24px; border: 3px solid var(--border); border-top-color: var(--crs-red); border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.mono { font-family: var(--font-mono); }

@media (max-width: 900px) {
  .page-content    { grid-template-columns: 1fr; }
  .restr-form      { grid-template-columns: 1fr 1fr; }
  .form-actions    { grid-column: span 2; }
  .field-wide      { grid-column: span 2; }
}
</style>
