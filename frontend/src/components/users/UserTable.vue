<template>
  <div class="user-table-wrap">
    <div v-if="loading" class="empty-state"><div class="spinner"></div></div>

    <template v-else>
      <div v-if="!users.length" class="empty-state">
        <div class="empty-icon">◎</div>
        <div class="text-muted">No users found.</div>
      </div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Role</th>
            <th>Status</th>
            <th>Last login</th>
            <th>Created</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>
              <div class="user-cell">
                <div class="avatar" :class="`role-${u.role}`">{{ initials(u.name) }}</div>
                <div>
                  <div class="user-name">
                    {{ u.name }}
                    <span v-if="u.must_change_password" class="pw-flag" title="Must change password on next login">🔑</span>
                  </div>
                  <div class="user-email mono text-muted">{{ u.email }}</div>
                </div>
              </div>
            </td>
            <td><span :class="['role-badge', `role-${u.role}`]">{{ u.role_label ?? u.role }}</span></td>
            <td>
              <span :class="['badge', u.is_active ? 'badge-approved' : 'badge-rejected']">
                {{ u.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <template v-if="u.last_login_at">
                <div class="mono" style="font-size:12px">{{ u.last_login_at_human ?? u.last_login_at }}</div>
                <div class="text-muted mono" style="font-size:10px">{{ u.last_login_ip }}</div>
              </template>
              <span v-else class="text-muted" style="font-size:12px">Never</span>
            </td>
            <td><span class="text-muted" style="font-size:12px">{{ u.created_at_human ?? u.created_at }}</span></td>
            <td>
              <div class="actions">
                <button class="icon-btn" title="Edit user" @click="emit('edit', u)">
                  <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="icon-btn icon-btn-warn" title="Reset password" @click="emit('reset-password', u)">
                  <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </button>
                <button
                  :class="['icon-btn', u.is_active ? 'icon-btn-danger' : 'icon-btn-success']"
                  :title="isCurrentUser(u.id) ? 'Cannot deactivate yourself' : (u.is_active ? 'Deactivate' : 'Reactivate')"
                  :disabled="isCurrentUser(u.id)"
                  @click="emit('toggle-active', u)"
                >
                  <svg v-if="u.is_active" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                  <svg v-else viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </template>
  </div>
</template>

<script setup>
const props = defineProps({
  users:         { type: Array,   default: () => [] },
  loading:       { type: Boolean, default: false },
  currentUserId: { type: Number,  default: null },
})
const emit = defineEmits(['edit', 'reset-password', 'toggle-active'])

function initials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}
function isCurrentUser(id) { return id === props.currentUserId }
</script>

<style scoped>
.user-table-wrap { width: 100%; }

.user-cell  { display: flex; align-items: center; gap: 10px; }
.avatar     { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center;
              justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; color: #fff; }
.avatar.role-super-admin  { background: var(--purple); }
.avatar.role-manager      { background: var(--blue); }
.avatar.role-loan-officer { background: var(--green); }
.avatar.role-staff        { background: var(--ink-muted); }
.avatar.role-board        { background: var(--amber); }

.user-name  { font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 5px; }
.user-email { font-size: 11px; }
.pw-flag    { font-size: 12px; }

.role-badge { font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 600; white-space: nowrap; }
.role-badge.role-super-admin  { background: var(--purple-pale); color: var(--purple); }
.role-badge.role-manager      { background: var(--blue-pale);   color: var(--blue); }
.role-badge.role-loan-officer { background: var(--green-pale);  color: var(--green); }
.role-badge.role-staff        { background: var(--surface-2);   color: var(--ink-muted); }
.role-badge.role-board        { background: var(--amber-pale);  color: var(--amber); }

.actions { display: flex; gap: 2px; }
.icon-btn {
  width: 30px; height: 30px; border-radius: 6px; border: none; background: transparent;
  display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
  color: var(--ink-muted); transition: all var(--tx);
}
.icon-btn svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.icon-btn:hover         { background: var(--surface-2); color: var(--ink); }
.icon-btn-warn          { color: var(--amber); }
.icon-btn-warn:hover    { background: var(--amber-pale); }
.icon-btn-danger        { color: var(--red); }
.icon-btn-danger:hover  { background: var(--red-pale); }
.icon-btn-success       { color: var(--green); }
.icon-btn-success:hover { background: var(--green-pale); }
.icon-btn:disabled      { opacity: 0.3; cursor: not-allowed; }
</style>
