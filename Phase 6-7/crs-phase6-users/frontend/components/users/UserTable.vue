<template>
  <div class="user-table-wrap">

    <DataTable
      :value="users"
      class="crs-table"
      stripedRows
      :loading="loading"
    >
      <template #empty>
        <div class="empty-state" style="padding:30px 0">
          <div class="empty-icon">◎</div>
          <div class="text-muted">No users found.</div>
        </div>
      </template>

      <!-- Avatar + name + email -->
      <Column header="User" style="min-width:220px">
        <template #body="{ data: u }">
          <div class="user-cell">
            <div class="avatar" :class="`role-${u.role}`">{{ initials(u.name) }}</div>
            <div>
              <div class="user-name">
                {{ u.name }}
                <span v-if="u.must_change_password"
                  class="pw-flag" v-tooltip.top="'Must change password on next login'">
                  🔑
                </span>
              </div>
              <div class="user-email mono text-muted">{{ u.email }}</div>
            </div>
          </div>
        </template>
      </Column>

      <!-- Role -->
      <Column header="Role" style="width:150px">
        <template #body="{ data: u }">
          <span :class="['role-badge', `role-${u.role}`]">{{ u.role_label }}</span>
        </template>
      </Column>

      <!-- Status -->
      <Column header="Status" style="width:110px">
        <template #body="{ data: u }">
          <Tag
            :value="u.is_active ? 'Active' : 'Inactive'"
            :severity="u.is_active ? 'success' : 'danger'"
          />
        </template>
      </Column>

      <!-- Last login -->
      <Column header="Last login" style="width:170px">
        <template #body="{ data: u }">
          <template v-if="u.last_login_at">
            <div class="mono" style="font-size:12px">{{ u.last_login_at_human }}</div>
            <div class="text-muted mono" style="font-size:10px">{{ u.last_login_ip }}</div>
          </template>
          <span v-else class="text-muted" style="font-size:12px">Never</span>
        </template>
      </Column>

      <!-- Member since -->
      <Column header="Created" style="width:120px">
        <template #body="{ data: u }">
          <span class="text-muted" style="font-size:12px">{{ u.created_at_human }}</span>
        </template>
      </Column>

      <!-- Actions -->
      <Column header="" style="width:130px">
        <template #body="{ data: u }">
          <div class="actions">
            <!-- Edit -->
            <Button
              icon="pi pi-pencil"
              text rounded size="small"
              v-tooltip.top="'Edit user'"
              @click="emit('edit', u)"
            />

            <!-- Reset password -->
            <Button
              icon="pi pi-key"
              text rounded size="small"
              severity="warning"
              v-tooltip.top="'Reset password'"
              @click="emit('reset-password', u)"
            />

            <!-- Deactivate / reactivate -->
            <Button
              :icon="u.is_active ? 'pi pi-ban' : 'pi pi-check-circle'"
              text rounded size="small"
              :severity="u.is_active ? 'danger' : 'success'"
              :v-tooltip.top="u.is_active ? 'Deactivate' : 'Reactivate'"
              :disabled="isCurrentUser(u.id)"
              v-tooltip.top="isCurrentUser(u.id) ? 'Cannot deactivate yourself' : (u.is_active ? 'Deactivate' : 'Reactivate')"
              @click="emit('toggle-active', u)"
            />
          </div>
        </template>
      </Column>

    </DataTable>

  </div>
</template>

<script setup>
import DataTable from 'primevue/datatable'
import Column    from 'primevue/column'
import Button    from 'primevue/button'
import Tag       from 'primevue/tag'

defineProps({
  users:   { type: Array,   default: () => [] },
  loading: { type: Boolean, default: false },
  currentUserId: { type: Number, default: null },
})
const emit = defineEmits(['edit', 'reset-password', 'toggle-active'])

function initials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}

function isCurrentUser(id) {
  // passed from parent — prevents self-deactivation
  return false // replace with: id === currentUserId
}
</script>

<style scoped>
.user-table-wrap { width:100%; }

/* User cell */
.user-cell    { display:flex; align-items:center; gap:10px; }
.avatar       { width:34px; height:34px; border-radius:50%; display:flex; align-items:center;
                justify-content:center; font-size:11px; font-weight:600; flex-shrink:0; }
.avatar.role-super-admin  { background:var(--purple-100); color:var(--purple-800); }
.avatar.role-manager      { background:var(--blue-100);   color:var(--blue-800); }
.avatar.role-loan-officer { background:var(--teal-100,#E1F5EE);  color:var(--teal-800,#085041); }
.avatar.role-staff        { background:var(--surface-200,#e0e0e0); color:var(--text-color-secondary); }
.avatar.role-board        { background:var(--amber-100,#FAC775);  color:var(--amber-800,#633806); }

.user-name  { font-size:13px; font-weight:500; display:flex; align-items:center; gap:5px; }
.user-email { font-size:11px; }
.pw-flag    { font-size:12px; cursor:default; }

/* Role badge */
.role-badge { font-size:11px; padding:2px 8px; border-radius:4px; font-weight:500; white-space:nowrap; }
.role-badge.role-super-admin  { background:var(--purple-50,#EEEDFE); color:var(--purple-800,#3C3489); }
.role-badge.role-manager      { background:var(--blue-50,#E6F1FB);   color:var(--blue-800,#0C447C); }
.role-badge.role-loan-officer { background:#E1F5EE;  color:#085041; }
.role-badge.role-staff        { background:var(--surface-200,#e0e0e0); color:var(--text-color-secondary); }
.role-badge.role-board        { background:#FAEEDA;  color:#633806; }

/* Actions */
.actions { display:flex; gap:2px; }
</style>
