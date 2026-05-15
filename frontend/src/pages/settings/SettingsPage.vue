<template>
  <div class="settings-wrap">
    <!-- Left nav -->
    <aside class="settings-nav">
      <div class="snav-title">Settings</div>
      <button v-for="tab in tabs" :key="tab.key"
        :class="['snav-item', activeTab === tab.key && 'active']"
        @click="activeTab = tab.key">
        <span class="snav-icon">{{ tab.icon }}</span>{{ tab.label }}
      </button>
    </aside>

    <!-- Content -->
    <div class="settings-body">

      <!-- ── Coop Profile ── -->
      <template v-if="activeTab === 'profile'">
        <div class="s-header">
          <div>
            <div class="s-title">Cooperative profile</div>
            <div class="s-sub">Identity and signatory information printed on all loan PDFs</div>
          </div>
        </div>
        <div class="card s-card">
          <div class="s-section">Organization</div>
          <div class="form-2col">
            <div class="form-group">
              <label class="form-label">Cooperative name *</label>
              <input v-model="profileForm.name" class="form-input" placeholder="Full registered name" />
            </div>
            <div class="form-group">
              <label class="form-label">CDA registration number</label>
              <input v-model="profileForm.cda_reg_no" class="form-input" placeholder="e.g. 9909-XXX" />
            </div>
            <div class="form-group" style="grid-column:span 2">
              <label class="form-label">Address</label>
              <input v-model="profileForm.address" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">Contact number</label>
              <input v-model="profileForm.contact" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">Email address</label>
              <input v-model="profileForm.email" class="form-input" type="email" />
            </div>
            <div class="form-group">
              <label class="form-label">Fiscal year start</label>
              <select v-model="profileForm.fiscal_year_start" class="form-select">
                <option v-for="m in months" :key="m">{{ m }}</option>
              </select>
            </div>
          </div>

          <div class="s-section">PDF signatories</div>
          <div class="form-2col">
            <div class="form-group">
              <label class="form-label">HR Manager signatory</label>
              <input v-model="profileForm.hr_signatory" class="form-input"
                placeholder="Name as it appears on the form" />
              <span class="field-hint">Auto-fills the HR Manager line on all loan PDFs</span>
            </div>
            <div class="form-group">
              <label class="form-label">COOP Manager / Loan Officer signatory</label>
              <input v-model="profileForm.coop_signatory" class="form-input"
                placeholder="Name as it appears on the form" />
              <span class="field-hint">Auto-fills the COOP Manager line on all loan PDFs</span>
            </div>
          </div>

          <div class="s-actions">
            <button class="btn btn-primary" :disabled="settingStore.saving" @click="saveProfile">
              {{ settingStore.saving ? 'Saving…' : 'Save profile' }}
            </button>
          </div>
        </div>
      </template>

      <!-- ── Loan Types ── -->
      <template v-if="activeTab === 'loan-types'">
        <div class="s-header">
          <div>
            <div class="s-title">Loan type configuration</div>
            <div class="s-sub">Amounts, terms, rates, qualifications, and approval levels</div>
          </div>
          <button class="btn btn-primary btn-sm" @click="openNewLoanType">+ Add loan type</button>
        </div>

        <div v-if="settingStore.loading" class="s-loading">
          <div class="spinner"></div>
        </div>

        <div v-for="lt in settingStore.loanTypes" :key="lt.id" class="card lt-card">
          <div class="lt-header">
            <div>
              <div class="lt-name">{{ lt.label }}</div>
              <div class="lt-code mono">{{ lt.code }}</div>
            </div>
            <div class="lt-actions">
              <span :class="['badge', lt.is_active ? 'badge-active' : 'badge-closed']">
                {{ lt.is_active ? 'Active' : 'Inactive' }}
              </span>
              <button class="btn btn-ghost btn-sm" @click="openEditLoanType(lt)">Edit</button>
              <button class="btn btn-ghost btn-sm s-danger" @click="confirmDeleteLoanType(lt)">Delete</button>
            </div>
          </div>
          <div class="lt-grid">
            <div class="lt-cell"><div class="lt-clabel">Amount range</div><div class="lt-cval mono">{{ peso(lt.min_amount) }} – {{ peso(lt.max_amount) }}</div></div>
            <div class="lt-cell"><div class="lt-clabel">Cap method</div><div class="lt-cval">{{ capLabel(lt.amount_cap_method) }}</div></div>
            <div class="lt-cell"><div class="lt-clabel">Term</div><div class="lt-cval">{{ lt.min_term }}–{{ lt.max_term }} months</div></div>
            <div class="lt-cell"><div class="lt-clabel">Rate</div><div class="lt-cval mono">{{ pct(lt.annual_rate_min) }}–{{ pct(lt.annual_rate_max) }} (default {{ pct(lt.annual_rate_default) }})</div></div>
            <div class="lt-cell"><div class="lt-clabel">Eligible status</div><div class="lt-cval">{{ (lt.allowed_emp_statuses||[]).join(', ') || 'Any' }}</div></div>
            <div class="lt-cell"><div class="lt-clabel">Concurrent</div><div class="lt-cval">{{ lt.allow_concurrent ? 'Allowed' : 'Not allowed' }}</div></div>
            <div class="lt-cell"><div class="lt-clabel">Penalty rate</div><div class="lt-cval mono">{{ pct(lt.penalty_rate) }}/mo</div></div>
            <div class="lt-cell"><div class="lt-clabel">Approval levels</div><div class="lt-cval">{{ lt.thresholds?.length ? lt.thresholds.length + ' level(s)' : 'Manager only' }}</div></div>
          </div>
        </div>
      </template>

      <!-- ── Preferences ── -->
      <template v-if="activeTab === 'preferences'">
        <div class="s-header">
          <div class="s-title">System preferences</div>
          <div class="s-sub">Scheduler and loan defaults</div>
        </div>
        <div class="card s-card">
          <div class="s-section">Loan defaults</div>
          <div class="form-2col">
            <div class="form-group">
              <label class="form-label">Default payment frequency</label>
              <select v-model="prefForm['loans.default_frequency']" class="form-select">
                <option value="monthly">Monthly</option>
                <option value="bimonthly">Bi-Monthly</option>
                <option value="weekly">Weekly</option>
              </select>
            </div>
          </div>

          <div class="s-section">Scheduler</div>
          <div class="form-2col">
            <div class="form-group">
              <label class="form-label">Overdue detection run time</label>
              <input v-model="prefForm['scheduler.overdue_check_time']" class="form-input" placeholder="02:00" />
              <span class="field-hint">24-hour format. Runs daily via artisan schedule:run</span>
            </div>
          </div>

          <div class="s-actions">
            <button class="btn btn-primary" :disabled="settingStore.saving" @click="savePreferences">
              {{ settingStore.saving ? 'Saving…' : 'Save preferences' }}
            </button>
          </div>
        </div>
      </template>
      <!-- ── Member Portal Access ── -->
      <template v-if="activeTab === 'member-access'">
        <div class="s-header">
          <div>
            <div class="s-title">Member Portal Access</div>
            <div class="s-sub">Create member usernames and passwords for the external member dashboard</div>
          </div>
          <span class="badge badge-active">{{ activePortalCount }} Active</span>
        </div>

        <div class="card s-card mpa-layout">
          <!-- Create / Edit form -->
          <div class="mpa-form-col">
            <div class="s-section" style="margin-top:0">New Access</div>
            <div class="form-group">
              <label class="form-label">Member *</label>
              <select v-model="portalForm.member_id" class="form-select" @change="syncPortalDefaults">
                <option value="">Select member</option>
                <option v-for="m in allMembers" :key="m.id" :value="m.id">
                  {{ memberFullName(m) }} · {{ m.member_no }} · {{ m.company }}
                </option>
              </select>
            </div>
            <div class="form-2col">
              <div class="form-group">
                <label class="form-label">Username *</label>
                <input v-model="portalForm.username" class="form-input" placeholder="e.g. crs00081" />
              </div>
              <div class="form-group">
                <label class="form-label">Login Email</label>
                <input v-model="portalForm.email" class="form-input" type="email" />
              </div>
              <div class="form-group">
                <label class="form-label">Temporary Password *</label>
                <input v-model="portalForm.password" class="form-input" />
              </div>
              <div class="form-group" style="display:flex;align-items:center;gap:8px;padding-top:24px">
                <input id="fpc" v-model="portalForm.force_password_change" type="checkbox" />
                <label for="fpc" class="form-label" style="margin:0">Require password change</label>
              </div>
            </div>

            <div style="margin-top:12px">
              <div class="s-section">Portal Modules</div>
              <div class="mpa-modules">
                <label v-for="mod in PORTAL_MODULES" :key="mod.key" class="mpa-module-check">
                  <input type="checkbox"
                    :checked="portalForm.modules.includes(mod.key)"
                    @change="togglePortalModule(mod.key, $event.target.checked)" />
                  {{ mod.label }}
                </label>
              </div>
            </div>

            <div class="s-actions">
              <button class="btn btn-ghost btn-sm" @click="resetPortalForm">Clear</button>
              <button class="btn btn-primary" :disabled="settingStore.saving" @click="submitPortalAccess">
                {{ settingStore.saving ? 'Saving…' : 'Save access' }}
              </button>
            </div>
          </div>

          <!-- Existing accounts list -->
          <div class="mpa-list-col">
            <div class="s-section" style="margin-top:0">Existing Logins</div>
            <div v-if="!settingStore.portalAccounts.length" class="mpa-empty">
              No member portal access has been created yet.
            </div>
            <div v-for="acc in settingStore.portalAccounts" :key="acc.id" class="mpa-row">
              <div class="mpa-info">
                <div class="mpa-name">{{ acc.member_name }}</div>
                <div class="mpa-meta">{{ acc.member_no }} · @{{ acc.username }}<span v-if="acc.email"> · {{ acc.email }}</span></div>
                <div class="mpa-modules-text">{{ (acc.modules || []).map(moduleLabel).join(', ') }}</div>
              </div>
              <div class="mpa-actions">
                <span :class="['badge', acc.active ? 'badge-active' : 'badge-closed']">
                  {{ acc.active ? 'Active' : 'Disabled' }}
                </span>
                <button class="btn btn-ghost btn-sm" @click="doResetPortalPassword(acc)">Reset PW</button>
                <button class="btn btn-ghost btn-sm" @click="doTogglePortalAccount(acc)">
                  {{ acc.active ? 'Disable' : 'Enable' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- ── Loan Type Modal ── -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box">
        <div class="modal-header">
          <div class="modal-title">{{ editingLoanType ? 'Edit loan type' : 'New loan type' }}</div>
          <button class="btn btn-ghost btn-sm" @click="showModal = false">✕</button>
        </div>
        <div class="modal-body">
          <LoanTypeForm
            :modelValue="loanTypeForm"
            :errors="ltErrors"
            :saving="settingStore.saving"
            @submit="saveLoanType"
            @cancel="showModal = false"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import LoanTypeForm        from '@/components/settings/LoanTypeForm.vue'
import { useSettingStore } from '@/stores/setting.store'
import { useCurrency }     from '@/composables/useCurrency'
import { useToast }        from '@/composables/useToast'
import { api }             from '@/composables/useApi'

const settingStore       = useSettingStore()
const { formatCurrency } = useCurrency()
const { success, error } = useToast()
const peso = (n) => formatCurrency(n)
const pct  = (n) => n != null ? (n * 100).toFixed(0) + '%' : '—'
const capLabel = (m) => ({ fixed: 'Fixed limit', salary_multiplier: 'Salary ×', both: 'Lower of both' }[m] || m)

// ── Member Portal Access ──────────────────────────────────────
const PORTAL_MODULES = [
  { key: 'dashboard',    label: 'Dashboard' },
  { key: 'loans',        label: 'My Loans' },
  { key: 'payments',     label: 'Payment History' },
  { key: 'shareCapital', label: 'Share Capital' },
  { key: 'beneficiaries',label: 'Beneficiaries' },
  { key: 'profile',      label: 'Profile' },
]

const allMembers = ref([])
const portalForm = reactive({
  member_id: '',
  username: '',
  email: '',
  password: 'member123',
  force_password_change: true,
  modules: PORTAL_MODULES.map(m => m.key),
})

const activePortalCount = computed(() =>
  settingStore.portalAccounts.filter(a => a.active).length
)

function memberFullName(m) {
  return [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ').replace(/\s+/g, ' ').trim()
}

function autoUsername(m) {
  return String(m.member_no || '').toLowerCase().replace(/[^a-z0-9]/g, '')
}

function syncPortalDefaults() {
  const m = allMembers.value.find(m => Number(m.id) === Number(portalForm.member_id))
  if (!m) return
  portalForm.username = autoUsername(m)
  portalForm.email = m.email || ''
}

function togglePortalModule(key, checked) {
  const set = new Set(portalForm.modules)
  checked ? set.add(key) : set.delete(key)
  portalForm.modules = [...set]
}

function resetPortalForm() {
  Object.assign(portalForm, {
    member_id: '', username: '', email: '',
    password: 'member123', force_password_change: true,
    modules: PORTAL_MODULES.map(m => m.key),
  })
}

function moduleLabel(key) {
  return PORTAL_MODULES.find(m => m.key === key)?.label || key
}

async function submitPortalAccess() {
  const m = allMembers.value.find(m => Number(m.id) === Number(portalForm.member_id))
  if (!m) { error('Select a member.'); return }
  try {
    const existing = settingStore.portalAccounts.find(a => Number(a.member_id) === Number(m.id))
    const payload = {
      member_id: m.id,
      username: portalForm.username || autoUsername(m),
      email: portalForm.email || m.email || '',
      password: portalForm.password,
      force_password_change: portalForm.force_password_change,
      modules: portalForm.modules.length ? [...portalForm.modules] : PORTAL_MODULES.map(m => m.key),
      is_active: true,
    }
    if (existing) {
      await settingStore.updatePortalAccount(existing.id, payload)
      success('Member portal access updated.')
    } else {
      await settingStore.createPortalAccount(payload)
      success('Member portal access created.')
    }
    resetPortalForm()
  } catch (e) { error(e?.message || 'Failed to save portal access.') }
}

async function doResetPortalPassword(account) {
  try {
    const res = await settingStore.resetPortalPassword(account.id)
    success(`Password for ${account.member_name} reset to: ${res.temp_password}`)
  } catch (e) { error(e?.message || 'Reset failed.') }
}

async function doTogglePortalAccount(account) {
  try {
    await settingStore.togglePortalAccount(account.id)
    success(`Account ${account.active ? 'disabled' : 'enabled'}.`)
  } catch (e) { error(e?.message || 'Toggle failed.') }
}

const activeTab = ref('profile')
const tabs = [
  { key: 'profile',        label: 'Coop profile',         icon: '◎ ' },
  { key: 'loan-types',     label: 'Loan types',            icon: '✦ ' },
  { key: 'preferences',    label: 'Preferences',           icon: '⚙ ' },
  { key: 'member-access',  label: 'Member Portal Access',  icon: '◍ ' },
]

const months = ['January','February','March','April','May','June',
                'July','August','September','October','November','December']

const profileForm = reactive({
  name: '', cda_reg_no: '', address: '', contact: '', email: '',
  fiscal_year_start: 'January', hr_signatory: '', coop_signatory: '',
})
const prefForm    = reactive({})
const showModal   = ref(false)
const editingLoanType = ref(null)
const loanTypeForm    = ref({})
const ltErrors        = ref({})

async function saveProfile() {
  try {
    await settingStore.updateProfile({ ...profileForm })
    success('Profile saved!')
  } catch (e) { error(e?.response?.data?.message || 'Save failed.') }
}

function openNewLoanType() {
  editingLoanType.value = null
  loanTypeForm.value = {
    code: '', label: '', min_amount: 5000, max_amount: 50000,
    amount_cap_method: 'fixed', salary_multiplier: null,
    min_term: 3, max_term: 24,
    annual_rate_default: 0.12, annual_rate_min: 0.10, annual_rate_max: 0.14,
    allowed_emp_statuses: ['REGULAR'],
    min_share_capital: 0, min_tenure_months: 0,
    allow_concurrent: false, penalty_rate: 0.02, is_active: true, thresholds: [],
  }
  ltErrors.value = {}
  showModal.value = true
}

function openEditLoanType(lt) {
  editingLoanType.value = lt
  loanTypeForm.value = { ...lt, thresholds: lt.thresholds ? [...lt.thresholds] : [] }
  ltErrors.value = {}
  showModal.value = true
}

async function saveLoanType(form) {
  ltErrors.value = {}
  try {
    if (editingLoanType.value) {
      await settingStore.updateLoanType(editingLoanType.value.id, form)
      success('Loan type updated!')
    } else {
      await settingStore.createLoanType(form)
      success('Loan type created!')
    }
    showModal.value = false
  } catch (e) {
    if (e?.response?.status === 422) ltErrors.value = e.response.data.errors || {}
    else error(e?.response?.data?.message || 'Save failed.')
  }
}

function confirmDeleteLoanType(lt) {
  if (!window.confirm(`Delete "${lt.label}"? This cannot be undone.`)) return
  settingStore.deleteLoanType(lt.id)
    .then(() => success('Loan type deleted.'))
    .catch(e => error(e?.response?.data?.message || 'Cannot delete — active loans exist.'))
}

async function savePreferences() {
  try {
    await settingStore.updatePreferences({ ...prefForm })
    success('Preferences saved!')
  } catch { error('Save failed.') }
}

onMounted(async () => {
  await Promise.all([
    settingStore.fetchProfile(),
    settingStore.fetchLoanTypes(),
    settingStore.fetchPreferences(),
    settingStore.fetchPortalAccounts(),
    api.getMembers().then(rows => { allMembers.value = rows || [] }).catch(() => {}),
  ])
  if (settingStore.profile) Object.assign(profileForm, settingStore.profile)
  if (settingStore.preferences) {
    const flat = Object.values(settingStore.preferences).reduce((a, g) => ({ ...a, ...g }), {})
    Object.assign(prefForm, flat)
  }
})
</script>

<style scoped>
.settings-wrap { display: flex; height: 100%; overflow: hidden; }

/* Nav */
.settings-nav {
  width: 196px; min-width: 196px;
  border-right: 1px solid var(--border);
  padding: 20px 10px; display: flex; flex-direction: column; gap: 2px;
  background: white;
}
.snav-title {
  font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.6px; color: var(--ink-muted);
  padding: 0 10px; margin-bottom: 10px;
}
.snav-item {
  display: flex; align-items: center; gap: 6px;
  padding: 9px 10px; border-radius: 7px; border: none;
  background: transparent; color: var(--ink-muted);
  cursor: pointer; font-size: 13px; text-align: left;
  transition: all var(--tx); font-family: var(--font-sans);
}
.snav-item:hover { color: var(--ink); background: var(--surface-2); }
.snav-item.active { color: var(--crs-red); background: var(--crs-red-pale); font-weight: 600; }
.snav-icon { width: 18px; }

/* Body */
.settings-body { flex: 1; overflow-y: auto; padding: 28px; display: flex; flex-direction: column; gap: 16px; }

/* Section header */
.s-header { display: flex; justify-content: space-between; align-items: flex-end; }
.s-title  { font-size: 19px; font-weight: 600; }
.s-sub    { font-size: 12px; color: var(--ink-muted); margin-top: 2px; }

/* Card sections */
.s-card { padding: 22px 24px; }
.s-section {
  font-size: 10.5px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.5px; color: var(--ink-muted);
  padding-bottom: 10px; border-bottom: 1px solid var(--border);
  margin: 18px 0 14px;
}
.s-section:first-child { margin-top: 0; }
.form-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
.field-hint { font-size: 11px; color: var(--ink-muted); margin-top: 3px; display: block; }
.s-actions { display: flex; justify-content: flex-end; padding-top: 18px; border-top: 1px solid var(--border); margin-top: 18px; }
.s-loading { display: flex; justify-content: center; padding: 40px; }

/* Loan type cards */
.lt-card { margin-bottom: 0; }
.lt-header { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--border); }
.lt-name   { font-size: 15px; font-weight: 600; }
.lt-code   { font-size: 11px; color: var(--ink-muted); margin-top: 2px; }
.lt-actions { display: flex; align-items: center; gap: 8px; }
.s-danger:hover { color: var(--red) !important; }
.lt-grid { display: grid; grid-template-columns: repeat(4, 1fr); }
.lt-cell { padding: 10px 18px; border-right: 1px solid var(--border); }
.lt-cell:last-child { border-right: none; }
.lt-cell:nth-child(n+5) { border-top: 1px solid var(--border); }
.lt-clabel { font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--ink-muted); }
.lt-cval   { font-size: 13px; margin-top: 3px; }

/* Spinner */
.spinner { width: 28px; height: 28px; border: 3px solid var(--border); border-top-color: var(--crs-red); border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Member Portal Access */
.mpa-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding: 22px 24px; }
.mpa-form-col { display: flex; flex-direction: column; gap: 12px; }
.mpa-list-col { display: flex; flex-direction: column; gap: 8px; overflow-y: auto; max-height: 70vh; }
.mpa-modules { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; margin-top: 8px; }
.mpa-module-check { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--ink-muted); border: 1px solid var(--border); border-radius: 6px; padding: 7px 10px; cursor: pointer; }
.mpa-row { border: 1px solid var(--border); border-radius: 8px; padding: 12px; display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; background: var(--surface-1, #fafafa); }
.mpa-info { flex: 1; min-width: 0; }
.mpa-name { font-weight: 600; font-size: 14px; }
.mpa-meta { font-size: 12px; color: var(--ink-muted); margin-top: 2px; }
.mpa-modules-text { font-size: 11px; color: var(--ink-muted); margin-top: 4px; font-style: italic; }
.mpa-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; justify-content: flex-end; min-width: 160px; }
.mpa-empty { border: 1px dashed var(--border); border-radius: 8px; padding: 20px; text-align: center; color: var(--ink-muted); font-size: 13px; }
.badge-active { background: #dcfce7; color: #166534; border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 600; }
.badge-closed { background: #f1f5f9; color: #64748b; border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 600; }

/* Modal */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center; z-index: 200;
}
.modal-box {
  background: white; border-radius: 12px; width: 760px; max-width: 96vw;
  max-height: 90vh; display: flex; flex-direction: column;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 18px 22px; border-bottom: 1px solid var(--border);
}
.modal-title { font-size: 16px; font-weight: 600; }
.modal-body  { padding: 22px; overflow-y: auto; }
</style>
