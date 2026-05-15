<template>
  <div class="page-wrap">
    <header class="topbar">
      <span class="topbar-page">Beneficiaries</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Manage member beneficiary declarations</span>
    </header>

    <div class="page-content">
      <!-- Member list -->
      <div class="member-panel">
        <div class="panel-head">
          <input v-model="memberSearch" type="text" placeholder="Search member…" class="search-input" />
        </div>
        <div class="member-list">
          <div v-if="membersLoading" class="panel-loading"><div class="spinner"></div></div>
          <div v-else-if="!filteredMembers.length" class="panel-empty">No members found.</div>
          <button
            v-for="m in filteredMembers"
            :key="m.id"
            :class="['member-item', selectedMember?.id === m.id && 'member-item-active']"
            @click="selectMember(m)"
          >
            <div class="mi-name">{{ m.first_name }} {{ m.last_name }}</div>
            <div class="mi-meta">{{ m.member_no }}</div>
          </button>
        </div>
      </div>

      <!-- Beneficiary workspace -->
      <div class="bene-area">
        <template v-if="selectedMember">
          <!-- Header -->
          <div class="bene-header">
            <div>
              <div class="bh-name">{{ selectedMember.first_name }} {{ selectedMember.last_name }}</div>
              <div class="bh-meta">{{ selectedMember.member_no }}</div>
            </div>
            <div class="bh-actions">
              <span :class="['alloc-badge', primaryAlloc === 100 ? 'alloc-ok' : 'alloc-warn']">
                Primary: {{ primaryAlloc }}%
              </span>
              <a :href="declarationUrl" target="_blank" rel="noopener" class="pdf-btn">↓ Declaration PDF</a>
            </div>
          </div>

          <!-- Add/Edit form -->
          <div class="bene-form-card">
            <div class="card-head">{{ editingId ? 'Edit Beneficiary' : 'Add Beneficiary' }}</div>
            <div class="bene-form">
              <div class="field">
                <label>Full Name</label>
                <input v-model="form.name" type="text" placeholder="Juan dela Cruz" />
              </div>
              <div class="field">
                <label>Relationship</label>
                <select v-model="form.relationship">
                  <option value="">-- Select --</option>
                  <option>Spouse</option>
                  <option>Child</option>
                  <option>Parent</option>
                  <option>Sibling</option>
                  <option>Legal Guardian</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="field">
                <label>Type</label>
                <select v-model="form.type">
                  <option value="Primary">Primary</option>
                  <option value="Secondary">Secondary</option>
                </select>
              </div>
              <div class="field">
                <label>Allocation (%)</label>
                <input v-model.number="form.allocation" type="number" min="1" max="100" />
              </div>
              <div class="field">
                <label>Date of Birth</label>
                <input v-model="form.date_of_birth" type="date" />
              </div>
              <div class="field">
                <label>Contact</label>
                <input v-model="form.contact" type="text" placeholder="09XXXXXXXXX" />
              </div>
              <div class="form-actions">
                <button v-if="editingId" class="cancel-btn" @click="resetForm">Cancel</button>
                <button class="save-btn" :disabled="!canSave || saving" @click="saveBeneficiary">
                  {{ saving ? 'Saving…' : (editingId ? 'Update' : 'Add Beneficiary') }}
                </button>
              </div>
            </div>
          </div>

          <!-- Beneficiary list -->
          <div class="bene-list-card">
            <div class="card-head">Beneficiaries</div>
            <div v-if="beneLoading" class="loading-state"><div class="spinner"></div></div>
            <div v-else-if="!beneficiaries.length" class="empty-row">No beneficiaries on file. Add one above.</div>
            <template v-else>
              <div v-for="section in ['Primary', 'Secondary']" :key="section">
                <div v-if="byType(section).length" class="bene-section-label">{{ section }}</div>
                <div v-for="b in byType(section)" :key="b.id" class="bene-row">
                  <div class="bene-info">
                    <strong>{{ b.name }}</strong>
                    <span>{{ b.relationship }} · {{ b.contact || 'No contact' }}</span>
                    <span v-if="b.date_of_birth">Born {{ formatDate(b.date_of_birth) }}</span>
                  </div>
                  <div class="bene-right">
                    <span class="alloc-chip">{{ b.allocation }}%</span>
                    <button class="edit-btn" @click="startEdit(b)">Edit</button>
                    <button class="del-btn" @click="deleteBeneficiary(b)">Delete</button>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </template>

        <div v-else class="bene-empty">
          <div class="empty-icon">◈</div>
          <div class="empty-title">Select a member</div>
          <div class="empty-sub">Choose a member from the left panel to manage their beneficiaries.</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { memberService } from '@/services/member.service'
import api               from '@/services/api'
import { useDate }       from '@/composables/useDate'
import { useToast }      from '@/composables/useToast'

const { formatDate }     = useDate()
const { success, error } = useToast()

const members        = ref([])
const beneficiaries  = ref([])
const selectedMember = ref(null)
const memberSearch   = ref('')
const membersLoading = ref(false)
const beneLoading    = ref(false)
const saving         = ref(false)
const editingId      = ref(null)

const form = ref({ name: '', relationship: '', type: 'Primary', allocation: '', date_of_birth: '', contact: '' })

const filteredMembers = computed(() => {
  const q = memberSearch.value.toLowerCase()
  if (!q) return members.value
  return members.value.filter(m =>
    (m.first_name + ' ' + m.last_name).toLowerCase().includes(q) ||
    (m.member_no || '').toLowerCase().includes(q)
  )
})

const canSave = computed(() =>
  form.value.name && form.value.relationship && form.value.allocation > 0
)

const primaryAlloc = computed(() =>
  byType('Primary').reduce((s, b) => s + (b.allocation || 0), 0)
)

const declarationUrl = computed(() =>
  selectedMember.value
    ? `/api/v1/members/${selectedMember.value.id}/beneficiaries/declaration.pdf`
    : '#'
)

function byType(type) {
  return beneficiaries.value.filter(b => b.type === type || b.beneficiary_type === type)
}

function resetForm() {
  editingId.value = null
  form.value = { name: '', relationship: '', type: 'Primary', allocation: '', date_of_birth: '', contact: '' }
}

function startEdit(b) {
  editingId.value = b.id
  form.value = {
    name:          b.name,
    relationship:  b.relationship,
    type:          b.type || b.beneficiary_type || 'Primary',
    allocation:    b.allocation,
    date_of_birth: b.date_of_birth || '',
    contact:       b.contact || '',
  }
}

async function selectMember(m) {
  selectedMember.value = m
  resetForm()
  await loadBeneficiaries()
}

async function loadBeneficiaries() {
  beneLoading.value = true
  try {
    const res         = await api.get(`/members/${selectedMember.value.id}/beneficiaries`)
    beneficiaries.value = res.data?.data ?? res.data ?? []
  } catch {
    error('Failed to load beneficiaries.')
  } finally {
    beneLoading.value = false
  }
}

async function saveBeneficiary() {
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/beneficiaries/${editingId.value}`, form.value)
      success('Beneficiary updated.')
    } else {
      await api.post(`/members/${selectedMember.value.id}/beneficiaries`, form.value)
      success('Beneficiary added.')
    }
    resetForm()
    await loadBeneficiaries()
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to save beneficiary.')
  } finally {
    saving.value = false
  }
}

async function deleteBeneficiary(b) {
  if (!confirm(`Delete beneficiary "${b.name}"?`)) return
  try {
    await api.delete(`/beneficiaries/${b.id}`)
    success('Beneficiary removed.')
    await loadBeneficiaries()
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to delete beneficiary.')
  }
}

onMounted(async () => {
  membersLoading.value = true
  try {
    const res     = await memberService.index({ per_page: 500 })
    members.value = res.data?.data ?? res.data ?? []
  } catch {
    error('Failed to load members.')
  } finally {
    membersLoading.value = false
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
  grid-template-columns: 260px 1fr;
}

/* Member panel */
.member-panel {
  border-right: 1px solid var(--border); display: flex; flex-direction: column; overflow: hidden;
}
.panel-head { padding: 10px; border-bottom: 1px solid var(--border); }
.search-input {
  width: 100%; border: 1px solid var(--border); border-radius: 6px;
  padding: 7px 10px; font-size: 12px; outline: none;
}
.search-input:focus { border-color: var(--crs-red); }
.member-list   { flex: 1; overflow-y: auto; }
.panel-loading { display: flex; justify-content: center; padding: 30px; }
.panel-empty   { padding: 20px; font-size: 13px; color: var(--ink-muted); text-align: center; }

.member-item {
  width: 100%; text-align: left; padding: 10px 14px;
  border: none; border-bottom: 1px solid var(--border);
  background: white; cursor: pointer; transition: background .1s;
}
.member-item:hover     { background: var(--surface); }
.member-item-active    { background: #fef2f2; border-left: 3px solid var(--crs-red); }
.mi-name { font-size: 13px; font-weight: 600; }
.mi-meta { font-size: 11px; color: var(--ink-muted); margin-top: 2px; font-family: var(--font-mono); }

/* Beneficiary area */
.bene-area { overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }

.bene-header {
  display: flex; justify-content: space-between; align-items: center;
  background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px 18px;
}
.bh-name  { font-size: 16px; font-weight: 700; }
.bh-meta  { font-size: 12px; color: var(--ink-muted); margin-top: 3px; }
.bh-actions { display: flex; align-items: center; gap: 10px; }

.alloc-badge { font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 99px; }
.alloc-ok   { background: #dcfce7; color: #166534; }
.alloc-warn { background: #fef3c7; color: #92400e; }

.pdf-btn {
  background: var(--crs-red); color: white; text-decoration: none;
  border-radius: 6px; padding: 7px 14px; font-size: 12px; font-weight: 600;
}

/* Form */
.bene-form-card { background: white; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.card-head { padding: 12px 18px; border-bottom: 1px solid var(--border); font-size: 13px; font-weight: 700; }
.bene-form {
  padding: 14px 18px;
  display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr; gap: 10px;
  align-items: end;
}
.field { display: flex; flex-direction: column; gap: 4px; }
.field label { font-size: 11px; font-weight: 600; color: var(--ink-muted); text-transform: uppercase; letter-spacing: .4px; }
.field input, .field select {
  border: 1px solid var(--border); border-radius: 6px;
  padding: 7px 10px; font-size: 13px; outline: none; background: white;
}
.field input:focus, .field select:focus { border-color: var(--crs-red); }
.form-actions { display: flex; gap: 8px; align-items: flex-end; }

.cancel-btn {
  border: 1px solid var(--border); background: white; border-radius: 7px;
  padding: 8px 14px; font-size: 13px; cursor: pointer;
}
.save-btn {
  background: var(--crs-red); color: white; border: none;
  border-radius: 7px; padding: 8px 16px; font-size: 13px; font-weight: 600;
  cursor: pointer; white-space: nowrap;
}
.save-btn:disabled { opacity: .5; cursor: not-allowed; }
.save-btn:not(:disabled):hover { opacity: .88; }

/* Beneficiary list */
.bene-list-card { background: white; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }

.bene-section-label {
  padding: 7px 18px; font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .5px; color: var(--ink-muted); background: var(--surface);
  border-bottom: 1px solid var(--border);
}
.bene-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 18px; border-bottom: 1px solid var(--border);
}
.bene-row:last-child { border-bottom: none; }
.bene-info { display: flex; flex-direction: column; gap: 2px; }
.bene-info strong { font-size: 13px; font-weight: 600; }
.bene-info span   { font-size: 12px; color: var(--ink-muted); }
.bene-right { display: flex; align-items: center; gap: 8px; }
.alloc-chip { font-size: 12px; font-weight: 700; padding: 2px 10px; border-radius: 99px; background: #dbeafe; color: #1e40af; }

.edit-btn {
  border: 1px solid var(--border); background: white; border-radius: 5px;
  padding: 4px 10px; font-size: 11px; cursor: pointer;
}
.del-btn {
  border: 1px solid #fca5a5; background: none; border-radius: 5px;
  padding: 4px 10px; font-size: 11px; font-weight: 600; color: #991b1b; cursor: pointer;
}
.del-btn:hover { background: #fee2e2; }

.empty-row { padding: 24px 18px; font-size: 13px; color: var(--ink-muted); text-align: center; }
.loading-state { display: flex; justify-content: center; padding: 30px; }
.spinner { width: 24px; height: 24px; border: 3px solid var(--border); border-top-color: var(--crs-red); border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.bene-empty {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 8px; padding: 60px;
}
.empty-icon  { font-size: 36px; color: var(--ink-muted); }
.empty-title { font-size: 15px; font-weight: 600; }
.empty-sub   { font-size: 13px; color: var(--ink-muted); text-align: center; }

@media (max-width: 900px) {
  .page-content { grid-template-columns: 1fr; }
  .bene-form    { grid-template-columns: 1fr 1fr; }
}
</style>
