<template>
  <div class="page-wrap">
    <header class="topbar">
      <span class="topbar-page">Eligibility Check</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Pre-loan compliance check before application</span>
    </header>

    <div class="page-content">
      <div class="elig-layout">
        <!-- Form -->
        <div class="elig-form-card">
          <div class="card-head">Check Eligibility</div>
          <div class="form-body">
            <div class="field">
              <label>Member</label>
              <select v-model="form.member_id" @change="onMemberChange">
                <option value="">-- Select member --</option>
                <option v-for="m in members" :key="m.id" :value="m.id">
                  {{ m.member_no }} · {{ m.full_name || (m.first_name + ' ' + m.last_name) }}
                </option>
              </select>
            </div>
            <div class="field">
              <label>Loan Type</label>
              <select v-model="form.loan_type_id">
                <option value="">-- Select loan type --</option>
                <option v-for="lt in loanTypes" :key="lt.id" :value="lt.id">{{ lt.label }}</option>
              </select>
            </div>
            <div class="field">
              <label>Requested Amount (₱)</label>
              <input v-model.number="form.amount" type="number" min="0" placeholder="e.g. 50000" />
            </div>
            <div class="field">
              <label>Term (months)</label>
              <input v-model.number="form.term_months" type="number" min="1" placeholder="e.g. 12" />
            </div>
            <button class="run-btn" :disabled="!canRun || loading" @click="runCheck">
              {{ loading ? 'Checking…' : 'Run Eligibility Check' }}
            </button>
          </div>
        </div>

        <!-- Results -->
        <div v-if="result" class="elig-result-card">
          <div class="card-head result-head">
            <span>Result</span>
            <span :class="['decision-badge', result.eligible ? 'badge-pass' : 'badge-fail']">
              {{ result.eligible ? 'ELIGIBLE' : 'NOT ELIGIBLE' }}
            </span>
          </div>

          <div class="checks-list">
            <div v-for="check in result.checks" :key="check.label" class="check-row">
              <span :class="['check-icon', check.passed ? 'icon-pass' : 'icon-fail']">
                {{ check.passed ? '✓' : '✗' }}
              </span>
              <div class="check-info">
                <strong>{{ check.label }}</strong>
                <span>{{ check.message }}</span>
              </div>
            </div>
          </div>

          <div v-if="result.eligible" class="proceed-bar">
            <router-link to="/loans/create" class="proceed-btn">
              Proceed to Loan Application →
            </router-link>
          </div>
        </div>

        <div v-else-if="!loading" class="elig-empty">
          <div class="empty-icon">⊛</div>
          <div class="empty-title">Fill in the form and run the check</div>
          <div class="empty-sub">The system will validate all cooperative eligibility rules automatically.</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { memberService } from '@/services/member.service'
import { loanService }   from '@/services/loan.service'
import api               from '@/services/api'
import { useToast }      from '@/composables/useToast'

const { error } = useToast()

const members   = ref([])
const loanTypes = ref([])
const loading   = ref(false)
const result    = ref(null)

const form = ref({ member_id: '', loan_type_id: '', amount: '', term_months: '' })

const canRun = computed(() =>
  form.value.member_id && form.value.loan_type_id && form.value.amount > 0 && form.value.term_months > 0
)

onMounted(async () => {
  const [mRes, ltRes] = await Promise.all([
    memberService.dropdown().catch(() => ({ data: { data: [] } })),
    loanService.loanTypes().catch(() => ({ data: { data: [] } })),
  ])
  members.value   = mRes.data?.data ?? mRes.data ?? []
  loanTypes.value = ltRes.data?.data ?? ltRes.data ?? []
})

function onMemberChange() {
  result.value = null
}

async function runCheck() {
  loading.value = true
  result.value  = null
  try {
    const res = await api.post('/loans/eligibility-check', {
      member_id:    form.value.member_id,
      loan_type_id: form.value.loan_type_id,
      amount:       form.value.amount,
      term_months:  form.value.term_months,
    })
    result.value = res.data?.data ?? res.data
  } catch (e) {
    error(e?.response?.data?.message || e?.message || 'Eligibility check failed.')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.page-wrap    { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.topbar {
  display: flex; align-items: center; gap: 8px;
  padding: 14px 24px; border-bottom: 1px solid var(--border);
  background: white; flex-shrink: 0;
}
.topbar-page { font-weight: 600; font-size: 14px; }
.topbar-sep  { color: var(--ink-muted); }
.topbar-sub  { font-size: 12px; color: var(--ink-muted); }

.page-content { flex: 1; overflow-y: auto; padding: 24px; }

.elig-layout {
  display: grid;
  grid-template-columns: 340px 1fr;
  gap: 20px;
  align-items: start;
}

.elig-form-card, .elig-result-card {
  background: white;
  border: 1px solid var(--border);
  border-radius: 10px;
  overflow: hidden;
}

.card-head {
  padding: 14px 18px;
  border-bottom: 1px solid var(--border);
  font-size: 14px; font-weight: 700;
}
.result-head { display: flex; justify-content: space-between; align-items: center; }

.form-body { padding: 18px; display: flex; flex-direction: column; gap: 14px; }

.field { display: flex; flex-direction: column; gap: 5px; }
.field label { font-size: 11px; font-weight: 600; color: var(--ink-muted); text-transform: uppercase; letter-spacing: .5px; }
.field select, .field input {
  border: 1px solid var(--border); border-radius: 6px;
  padding: 8px 10px; font-size: 13px; color: var(--ink);
  background: var(--surface); outline: none;
  transition: border-color .15s;
}
.field select:focus, .field input:focus { border-color: var(--crs-red); }

.run-btn {
  background: var(--crs-red); color: white; border: none;
  border-radius: 7px; padding: 10px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: opacity .15s;
}
.run-btn:disabled { opacity: .5; cursor: not-allowed; }
.run-btn:not(:disabled):hover { opacity: .88; }

.decision-badge {
  border-radius: 99px; padding: 3px 12px;
  font-size: 11px; font-weight: 700; letter-spacing: .05em;
}
.badge-pass { background: #dcfce7; color: #166534; }
.badge-fail { background: #fee2e2; color: #991b1b; }

.checks-list { padding: 4px 0; }
.check-row {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 12px 18px;
  border-bottom: 1px solid var(--border);
}
.check-row:last-child { border-bottom: none; }
.check-icon {
  width: 22px; height: 22px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.icon-pass { background: #dcfce7; color: #166534; }
.icon-fail { background: #fee2e2; color: #991b1b; }
.check-info { display: flex; flex-direction: column; gap: 2px; }
.check-info strong { font-size: 13px; }
.check-info span   { font-size: 12px; color: var(--ink-muted); }

.proceed-bar { padding: 14px 18px; border-top: 1px solid var(--border); background: #f0fdf4; }
.proceed-btn {
  display: inline-block; background: #16a34a; color: white;
  padding: 9px 18px; border-radius: 7px;
  font-size: 13px; font-weight: 600; text-decoration: none;
  transition: opacity .15s;
}
.proceed-btn:hover { opacity: .88; }

.elig-empty {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 60px 20px; gap: 8px;
  background: white; border: 1px solid var(--border); border-radius: 10px;
}
.empty-icon  { font-size: 32px; color: var(--ink-muted); }
.empty-title { font-size: 15px; font-weight: 600; color: var(--ink); }
.empty-sub   { font-size: 13px; color: var(--ink-muted); text-align: center; }

@media (max-width: 768px) {
  .elig-layout { grid-template-columns: 1fr; }
}
</style>
