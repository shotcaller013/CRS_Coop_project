<template>
  <div class="page-wrap">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <div class="page-title serif">User management</div>
        <div class="page-sub">Create and manage system user accounts — super-admin only</div>
      </div>
      <Button
        icon="pi pi-plus"
        label="New user"
        @click="openCreate"
      />
    </div>

    <!-- Summary stats -->
    <div class="meta-row" v-if="store.meta">
      <div class="meta-stat">
        <span class="meta-val">{{ store.meta.total }}</span>
        <span class="meta-label">Total users</span>
      </div>
      <div class="meta-stat">
        <span class="meta-val" style="color:var(--green-600)">{{ store.meta.active }}</span>
        <span class="meta-label">Active</span>
      </div>
      <div class="meta-stat" v-if="store.meta.inactive > 0">
        <span class="meta-val" style="color:var(--red-500)">{{ store.meta.inactive }}</span>
        <span class="meta-label">Inactive</span>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
      <div class="filter-group" style="flex:1">
        <InputText
          v-model="store.filters.search"
          placeholder="Search name or email…"
          style="width:100%"
          @keyup.enter="load"
        />
      </div>
      <div class="filter-group">
        <Dropdown
          v-model="store.filters.role"
          :options="roleOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="All roles"
          showClear
          style="width:170px"
        />
      </div>
      <div class="filter-group">
        <Dropdown
          v-model="store.filters.is_active"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="All statuses"
          showClear
          style="width:150px"
        />
      </div>
      <Button icon="pi pi-search" label="Search" @click="load" :loading="store.loading" />
      <Button icon="pi pi-refresh" outlined severity="secondary"
        v-tooltip.top="'Reset filters'" @click="resetAndLoad" />
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
    <UserForm
      v-model:visible="formVisible"
      :user="editTarget"
      @saved="onSaved"
    />

    <!-- Reset password dialog -->
    <Dialog
      v-model:visible="resetDialogVisible"
      header="Reset password"
      modal
      :style="{ width: '440px' }"
    >
      <template v-if="!tempPassword">
        <p style="font-size:13px;margin-bottom:16px">
          Reset the password for <strong>{{ resetTarget?.name }}</strong>?
          A temporary password will be generated. The user will be required to change it on next login.
          All existing login sessions will be invalidated.
        </p>
        <div style="display:flex;justify-content:flex-end;gap:8px">
          <Button label="Cancel" text @click="resetDialogVisible = false" />
          <Button label="Reset password" icon="pi pi-key" severity="warning"
            @click="confirmResetPassword" :loading="resetting" />
        </div>
      </template>

      <!-- Show temp password after reset -->
      <template v-else>
        <div class="temp-pw-box">
          <div class="temp-pw-label">Temporary password for {{ resetTarget?.name }}</div>
          <div class="temp-pw-value mono">{{ tempPassword }}</div>
          <small class="temp-pw-hint">
            Give this password to the user securely. It is shown only once and will not be stored in plain text.
            They will be required to change it immediately after logging in.
          </small>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px">
          <Button label="Copy password" icon="pi pi-copy" outlined @click="copyPassword" />
          <Button label="Done" @click="closeTempPw" />
        </div>
      </template>
    </Dialog>

    <ConfirmDialog />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Button       from 'primevue/button'
import InputText    from 'primevue/inputtext'
import Dropdown     from 'primevue/dropdown'
import Dialog       from 'primevue/dialog'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import UserForm  from '@/components/users/UserForm.vue'
import UserTable from '@/components/users/UserTable.vue'
import { useUserStore } from '@/stores/user.store'
import { useToast }     from '@/composables/useToast'

// ── Replace with actual auth store ─────────────────────────
const currentUserId = ref(null) // replace: useAuthStore().user?.id

const store   = useUserStore()
const confirm = useConfirm()
const toast   = useToast()

// ── Form dialog state ───────────────────────────────────────
const formVisible = ref(false)
const editTarget  = ref(null)

function openCreate() { editTarget.value = null; formVisible.value = true }
function openEdit(user) { editTarget.value = user; formVisible.value = true }
function onSaved() { /* store already refreshed inside UserForm */ }

// ── Reset password dialog state ─────────────────────────────
const resetDialogVisible = ref(false)
const resetTarget        = ref(null)
const tempPassword       = ref(null)
const resetting          = ref(false)

function openResetPassword(user) {
  resetTarget.value  = user
  tempPassword.value = null
  resetDialogVisible.value = true
}

async function confirmResetPassword() {
  resetting.value = true
  try {
    const res      = await store.resetPassword(resetTarget.value.id)
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

// ── Toggle active ────────────────────────────────────────────
async function handleToggleActive(user) {
  const action = user.is_active ? 'deactivate' : 'reactivate'
  confirm.require({
    message: `${user.is_active ? 'Deactivate' : 'Reactivate'} ${user.name}?
      ${user.is_active ? 'They will lose access immediately and all sessions will be terminated.' : 'They will be able to log in again.'}`,
    header:  `${user.is_active ? 'Deactivate' : 'Reactivate'} user`,
    icon:    user.is_active ? 'pi pi-ban' : 'pi pi-check-circle',
    rejectLabel: 'Cancel',
    acceptLabel: user.is_active ? 'Deactivate' : 'Reactivate',
    acceptClass: user.is_active ? 'p-button-danger' : 'p-button-success',
    accept: async () => {
      try {
        const res = await store.toggleActive(user.id)
        toast.success(res.message)
      } catch (e) {
        toast.error(e?.response?.data?.message || `Could not ${action} user.`)
      }
    },
  })
}

// ── Filters ─────────────────────────────────────────────────
const roleOptions = [
  { label: 'Super Admin',   value: 'super-admin'  },
  { label: 'Manager',       value: 'manager'      },
  { label: 'Loan Officer',  value: 'loan-officer' },
  { label: 'Staff',         value: 'staff'        },
  { label: 'Board Member',  value: 'board'        },
]

const statusOptions = [
  { label: 'Active',   value: 'true'  },
  { label: 'Inactive', value: 'false' },
]

async function load() { await store.fetchUsers() }

async function resetAndLoad() {
  store.resetFilters()
  await store.fetchUsers()
}

onMounted(load)
</script>

<style scoped>
.page-wrap    { display:flex; flex-direction:column; height:100%; overflow:hidden; }

/* Header */
.page-header  { display:flex; align-items:center; justify-content:space-between;
                padding:18px 24px; border-bottom:1px solid var(--surface-border); flex-shrink:0; }
.page-title   { font-size:22px; color:var(--text-color); }
.page-sub     { font-size:12px; color:var(--text-color-secondary); margin-top:2px; }

/* Meta stats */
.meta-row     { display:flex; gap:20px; padding:10px 24px;
                border-bottom:0.5px solid var(--surface-border); flex-shrink:0; }
.meta-stat    { display:flex; align-items:baseline; gap:6px; }
.meta-val     { font-size:20px; font-weight:500; }
.meta-label   { font-size:12px; color:var(--text-color-secondary); }

/* Filter bar */
.filter-bar   { display:flex; align-items:center; gap:8px; padding:12px 20px;
                border-bottom:0.5px solid var(--surface-border);
                background:var(--surface-ground); flex-shrink:0; }
.filter-group { display:flex; flex-direction:column; }

/* Content */
.page-content { flex:1; overflow-y:auto; padding:16px 20px; }

/* Temp password box */
.temp-pw-box   { background:var(--surface-ground); border-radius:var(--border-radius-md);
                  padding:16px 18px; display:flex; flex-direction:column; gap:8px; }
.temp-pw-label { font-size:12px; color:var(--text-color-secondary); }
.temp-pw-value { font-size:22px; font-weight:500; letter-spacing:.1em;
                  background:var(--surface-card); border:1px solid var(--surface-border);
                  border-radius:6px; padding:10px 14px; text-align:center; }
.temp-pw-hint  { font-size:11px; color:var(--text-color-secondary); line-height:1.5; }
</style>
