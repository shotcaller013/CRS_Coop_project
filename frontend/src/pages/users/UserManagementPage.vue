<template>
  <div class="page-wrap">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <div class="page-title serif">User management</div>
        <div class="page-sub">Create and manage system user accounts — super-admin only</div>
      </div>
      <button class="btn btn-primary" @click="openCreate">+ New user</button>
    </div>

    <!-- Summary stats -->
    <div v-if="store.meta" class="meta-row">
      <div class="meta-stat">
        <span class="meta-val">{{ store.meta.total }}</span>
        <span class="meta-label">Total users</span>
      </div>
      <div class="meta-stat">
        <span class="meta-val" style="color:var(--green)">{{ store.meta.active }}</span>
        <span class="meta-label">Active</span>
      </div>
      <div v-if="store.meta.inactive > 0" class="meta-stat">
        <span class="meta-val" style="color:var(--red)">{{ store.meta.inactive }}</span>
        <span class="meta-label">Inactive</span>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
      <div class="filter-group" style="flex:1">
        <input v-model="store.filters.search" class="form-input"
          placeholder="Search name or email…" @keyup.enter="load" />
      </div>
      <div class="filter-group">
        <select v-model="store.filters.role" class="form-select" style="width:170px">
          <option value="">All roles</option>
          <option v-for="r in roleOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
        </select>
      </div>
      <div class="filter-group">
        <select v-model="store.filters.is_active" class="form-select" style="width:150px">
          <option value="">All statuses</option>
          <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
      </div>
      <button class="btn btn-primary" :disabled="store.loading" @click="load">
        <span v-if="store.loading" class="spinner-sm"></span>
        Search
      </button>
      <button class="btn btn-secondary" title="Reset filters" @click="resetAndLoad">↺</button>
    </div>

    <!-- User table -->
    <div class="page-content">
      <UserTable
        :users="store.users"
        :loading="store.loading"
        :current-user-id="currentUserId"
        @edit="openEdit"
        @reset-password="openResetPassword"
        @toggle-active="handleToggleActive"
      />
    </div>

    <!-- Create / Edit dialog -->
    <UserForm v-model:visible="formVisible" :user="editTarget" @saved="onSaved" />

    <!-- Reset password modal -->
    <Teleport to="body">
      <div v-if="resetDialogVisible" class="modal-overlay" @click.self="closeResetDialog">
        <div class="modal" style="max-width:440px">
          <div class="modal-header">
            <span class="modal-title">Reset password</span>
            <button class="close-btn" @click="closeResetDialog">
              <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>

          <div class="modal-body">
            <template v-if="!tempPassword">
              <p style="font-size:13px;margin:0 0 16px;line-height:1.6">
                Reset the password for <strong>{{ resetTarget?.name }}</strong>?
                A temporary password will be generated. The user will be required to change it on next login.
                All existing login sessions will be invalidated.
              </p>
              <div style="display:flex;justify-content:flex-end;gap:8px">
                <button class="btn btn-secondary" @click="closeResetDialog">Cancel</button>
                <button class="btn btn-primary" :disabled="resetting" @click="confirmResetPassword"
                  style="background:var(--amber);border-color:var(--amber)">
                  <span v-if="resetting" class="spinner-sm"></span>
                  Reset password
                </button>
              </div>
            </template>

            <template v-else>
              <div class="temp-pw-box">
                <div class="temp-pw-label">Temporary password for {{ resetTarget?.name }}</div>
                <div class="temp-pw-value mono">{{ tempPassword }}</div>
                <small class="temp-pw-hint">
                  Give this password to the user securely. It is shown only once and will not be stored
                  in plain text. They will be required to change it immediately after logging in.
                </small>
              </div>
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px">
                <button class="btn btn-secondary" @click="copyPassword">Copy password</button>
                <button class="btn btn-primary" @click="closeTempPw">Done</button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Confirm modal (toggle active) -->
    <Teleport to="body">
      <div v-if="confirmState.visible" class="modal-overlay">
        <div class="modal" style="max-width:420px">
          <div class="modal-header">
            <span class="modal-title">{{ confirmState.header }}</span>
          </div>
          <div class="modal-body">
            <p style="font-size:13px;line-height:1.6;margin:0 0 16px">{{ confirmState.message }}</p>
            <div style="display:flex;justify-content:flex-end;gap:8px">
              <button class="btn btn-secondary" @click="confirmState.visible = false">{{ confirmState.rejectLabel }}</button>
              <button :class="['btn', confirmState.acceptClass]" @click="confirmState.onAccept()">{{ confirmState.acceptLabel }}</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import UserForm  from '@/components/users/UserForm.vue'
import UserTable from '@/components/users/UserTable.vue'
import { useUserStore } from '@/stores/user.store'
import { useToast }     from '@/composables/useToast'

const currentUserId = ref(null)

const store = useUserStore()
const toast = useToast()

// ── Form dialog ──────────────────────────────────────────────
const formVisible = ref(false)
const editTarget  = ref(null)

function openCreate() { editTarget.value = null; formVisible.value = true }
function openEdit(user) { editTarget.value = user; formVisible.value = true }
function onSaved() { /* store already refreshed inside UserForm */ }

// ── Reset password ───────────────────────────────────────────
const resetDialogVisible = ref(false)
const resetTarget        = ref(null)
const tempPassword       = ref(null)
const resetting          = ref(false)

function openResetPassword(user) {
  resetTarget.value        = user
  tempPassword.value       = null
  resetDialogVisible.value = true
}

function closeResetDialog() {
  if (resetting.value) return
  resetDialogVisible.value = false
}

async function confirmResetPassword() {
  resetting.value = true
  try {
    const res          = await store.resetPassword(resetTarget.value.id)
    tempPassword.value = res.temp_password
    await store.fetchUsers()
  } catch {
    toast.error('Password reset failed.')
    resetDialogVisible.value = false
  } finally { resetting.value = false }
}

function copyPassword() {
  navigator.clipboard.writeText(tempPassword.value)
  toast.success('Copied to clipboard.')
}

function closeTempPw() {
  tempPassword.value       = null
  resetDialogVisible.value = false
}

// ── Toggle active (confirm) ──────────────────────────────────
const confirmState = reactive({
  visible: false, header: '', message: '',
  acceptLabel: 'Confirm', rejectLabel: 'Cancel',
  acceptClass: 'btn-primary', onAccept: () => {},
})

function handleToggleActive(user) {
  const deactivating = user.is_active
  Object.assign(confirmState, {
    visible:     true,
    header:      deactivating ? 'Deactivate user' : 'Reactivate user',
    message:     deactivating
      ? `Deactivate ${user.name}? They will lose access immediately and all sessions will be terminated.`
      : `Reactivate ${user.name}? They will be able to log in again.`,
    acceptLabel: deactivating ? 'Deactivate' : 'Reactivate',
    rejectLabel: 'Cancel',
    acceptClass: deactivating ? 'btn-danger' : 'btn-success',
    onAccept:    async () => {
      confirmState.visible = false
      try {
        const res = await store.toggleActive(user.id)
        toast.success(res.message)
      } catch (e) {
        toast.error(e?.response?.data?.message || `Could not ${deactivating ? 'deactivate' : 'reactivate'} user.`)
      }
    },
  })
}

// ── Filters ──────────────────────────────────────────────────
const roleOptions = [
  { label: 'Super Admin',  value: 'super-admin'  },
  { label: 'Manager',      value: 'manager'      },
  { label: 'Loan Officer', value: 'loan-officer' },
  { label: 'Staff',        value: 'staff'        },
  { label: 'Board Member', value: 'board'        },
]

const statusOptions = [
  { label: 'Active',   value: 'true'  },
  { label: 'Inactive', value: 'false' },
]

async function load() { await store.fetchUsers() }
async function resetAndLoad() { store.resetFilters(); await store.fetchUsers() }

onMounted(load)
</script>

<style scoped>
.page-wrap   { display:flex; flex-direction:column; height:100%; overflow:hidden; }
.page-header { display:flex; align-items:center; justify-content:space-between;
               padding:18px 24px; border-bottom:1px solid var(--border); flex-shrink:0; }
.page-title  { font-size:22px; }
.page-sub    { font-size:12px; color:var(--ink-muted); margin-top:2px; }

.meta-row    { display:flex; gap:20px; padding:10px 24px;
               border-bottom:0.5px solid var(--border); flex-shrink:0; }
.meta-stat   { display:flex; align-items:baseline; gap:6px; }
.meta-val    { font-size:20px; font-weight:500; }
.meta-label  { font-size:12px; color:var(--ink-muted); }

.filter-bar  { display:flex; align-items:center; gap:8px; padding:12px 20px;
               border-bottom:0.5px solid var(--border);
               background:var(--surface-2); flex-shrink:0; }
.filter-group { display:flex; flex-direction:column; }

.page-content { flex:1; overflow-y:auto; padding:16px 20px; }

.temp-pw-box   { background:var(--surface-2); border-radius:8px; padding:16px 18px;
                 display:flex; flex-direction:column; gap:8px; }
.temp-pw-label { font-size:12px; color:var(--ink-muted); }
.temp-pw-value { font-size:22px; font-weight:500; letter-spacing:.1em;
                 background:white; border:1px solid var(--border);
                 border-radius:6px; padding:10px 14px; text-align:center; }
.temp-pw-hint  { font-size:11px; color:var(--ink-muted); line-height:1.5; }

.btn-danger        { background:var(--red); border-color:var(--red); color:#fff; }
.btn-danger:hover  { opacity:.9; }
.btn-success       { background:var(--green); border-color:var(--green); color:#fff; }
.btn-success:hover { opacity:.9; }

.close-btn {
  background:none; border:none; cursor:pointer; color:var(--ink-muted);
  padding:4px; border-radius:4px; display:flex; align-items:center;
}
.close-btn:hover { background:var(--surface-2); color:var(--ink); }
.close-btn svg { width:18px; height:18px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; }

.spinner-sm {
  display:inline-block; width:14px; height:14px;
  border:2px solid rgba(255,255,255,.3); border-top-color:white;
  border-radius:50%; animation:spin .7s linear infinite;
}
@keyframes spin { to { transform:rotate(360deg); } }

@media (max-width: 768px) {
  .meta-row   { padding: 8px 16px; gap: 16px; flex-wrap: wrap; }
  .filter-bar { flex-wrap: wrap; }
  .filter-bar .filter-group { flex: 1 1 140px; }
  .filter-bar .form-select { width: 100% !important; }
}
</style>
