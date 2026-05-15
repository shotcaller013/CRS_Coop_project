<template>
  <div class="page-wrap">

    <!-- Page Header -->
    <header class="page-header">
      <div class="page-header-text">
        <h1 class="page-title">Audit Logs</h1>
        <p class="page-subtitle">System event history for compliance and accountability</p>
      </div>
    </header>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-wrap">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          v-model="filters.search"
          type="text"
          placeholder="Search actor, action, record…"
          class="search-input"
          @keyup.enter="load"
        />
      </div>

      <select v-model="filters.module" class="filter-select" @change="load">
        <option value="">All modules</option>
        <option v-for="m in moduleOptions" :key="m" :value="m">{{ m }}</option>
      </select>

      <select v-model="filters.action" class="filter-select" @change="load">
        <option value="">All actions</option>
        <option v-for="a in actionOptions" :key="a" :value="a">{{ a }}</option>
      </select>

      <input v-model="filters.date_from" type="date" class="filter-date" @change="load" />
      <input v-model="filters.date_to"   type="date" class="filter-date" @change="load" />

      <button class="run-btn" @click="load">Search</button>
    </div>

    <div class="page-content" :class="{ 'with-panel': !!detail }">

      <!-- Table Area -->
      <div class="table-area">

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
        </div>

        <template v-else-if="logs.length">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>Actor</th>
                  <th>Module</th>
                  <th>Action</th>
                  <th>Record</th>
                  <th>Risk</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="log in logs"
                  :key="log.id"
                  :class="['log-row', detail?.id === log.id && 'log-row-active']"
                  :style="detail?.id === log.id ? { borderLeft: '3px solid ' + riskBorderColor(log.risk_level || log.risk) } : {}"
                  @click="detail = log"
                >
                  <!-- Timestamp -->
                  <td class="td-timestamp">
                    <span class="ts-date">{{ formatDate(log.created_at) }}</span>
                    <span class="ts-time">{{ formatTime(log.created_at) }}</span>
                  </td>

                  <!-- Actor -->
                  <td class="td-actor">
                    <div class="actor-wrap">
                      <div
                        class="actor-avatar"
                        :style="{ background: avatarColor(log.actor_name || log.causer_name) }"
                      >{{ actorInitials(log.actor_name || log.causer_name) }}</div>
                      <div class="actor-info">
                        <div class="actor-name">{{ log.actor_name || log.causer_name || '—' }}</div>
                        <div class="actor-role">{{ log.actor_role || log.causer_type || '' }}</div>
                      </div>
                    </div>
                  </td>

                  <!-- Module -->
                  <td class="td-module">
                    <span :class="['module-badge', moduleClass(log.module || log.log_name)]">
                      {{ log.module || log.log_name || '—' }}
                    </span>
                  </td>

                  <!-- Action -->
                  <td class="td-action">
                    <span :class="['action-text', actionClass(log.action || log.description)]">
                      {{ log.action || log.description || '—' }}
                    </span>
                  </td>

                  <!-- Record -->
                  <td class="td-record">
                    {{ log.subject_type ? log.subject_type + ' #' + log.subject_id : (log.record_label || '—') }}
                  </td>

                  <!-- Risk -->
                  <td class="td-risk">
                    <span :class="['risk-badge', riskClass(log.risk_level || log.risk)]">
                      {{ log.risk_level || log.risk || 'LOW' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="pager">
            <span class="pager-info">
              Showing {{ pageStart }}–{{ pageEnd }} of {{ total }}
            </span>
            <div class="pager-controls">
              <button
                class="pager-btn"
                :disabled="page <= 1"
                @click="changePage(page - 1)"
                title="Previous page"
              >&#8249;</button>

              <template v-for="p in visiblePages" :key="p">
                <button
                  v-if="p !== '…'"
                  :class="['pager-btn', p === page && 'pager-btn-active']"
                  @click="changePage(p)"
                >{{ p }}</button>
                <span v-else class="pager-ellipsis">…</span>
              </template>

              <button
                class="pager-btn"
                :disabled="page >= totalPages"
                @click="changePage(page + 1)"
                title="Next page"
              >&#8250;</button>
            </div>
          </div>
        </template>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
              <!-- Document body -->
              <rect x="10" y="6" width="28" height="36" rx="3" fill="#F0F1F4" stroke="#D4D5DA" stroke-width="1.5"/>
              <!-- Document lines -->
              <line x1="17" y1="15" x2="31" y2="15" stroke="#A8AABB" stroke-width="1.5" stroke-linecap="round"/>
              <line x1="17" y1="21" x2="31" y2="21" stroke="#A8AABB" stroke-width="1.5" stroke-linecap="round"/>
              <line x1="17" y1="27" x2="25" y2="27" stroke="#A8AABB" stroke-width="1.5" stroke-linecap="round"/>
              <!-- Magnifier circle -->
              <circle cx="37" cy="37" r="10" fill="white" stroke="#D4D5DA" stroke-width="1.5"/>
              <circle cx="37" cy="37" r="5.5" fill="none" stroke="#A8AABB" stroke-width="1.5"/>
              <!-- Magnifier handle -->
              <line x1="41" y1="41" x2="45.5" y2="45.5" stroke="#A8AABB" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="empty-title">No audit logs found</div>
          <div class="empty-sub">Try adjusting your filters or date range</div>
        </div>
      </div>

      <!-- Detail Panel -->
      <div v-if="detail" class="detail-panel">
        <!-- Sticky Header -->
        <div class="dp-head">
          <div class="dp-head-left">
            <span class="dp-event-id">Event #{{ detail.id }}</span>
            <span :class="['risk-badge', riskClass(detail.risk_level || detail.risk)]">
              {{ detail.risk_level || detail.risk || 'LOW' }}
            </span>
          </div>
          <button class="dp-close" @click="detail = null" title="Close">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="dp-body">
          <!-- Event Info Section -->
          <div class="dp-section-title">Event Info</div>
          <div class="dp-info-block">
            <div class="dp-row">
              <span class="dp-label">Timestamp</span>
              <strong class="dp-value mono">{{ formatDateTime(detail.created_at) }}</strong>
            </div>
            <div class="dp-row">
              <span class="dp-label">Actor</span>
              <strong class="dp-value">{{ detail.actor_name || detail.causer_name || '—' }}</strong>
            </div>
            <div class="dp-row">
              <span class="dp-label">Role</span>
              <strong class="dp-value">{{ detail.actor_role || detail.causer_type || '—' }}</strong>
            </div>
            <div class="dp-row">
              <span class="dp-label">Module</span>
              <strong class="dp-value">{{ detail.module || detail.log_name || '—' }}</strong>
            </div>
            <div class="dp-row">
              <span class="dp-label">Action</span>
              <strong class="dp-value" :class="actionClass(detail.action || detail.description)">{{ detail.action || detail.description || '—' }}</strong>
            </div>
            <div class="dp-row">
              <span class="dp-label">IP Address</span>
              <strong class="dp-value mono">{{ detail.ip_address || '—' }}</strong>
            </div>
            <div class="dp-row">
              <span class="dp-label">Record</span>
              <strong class="dp-value">{{ detail.subject_type ? detail.subject_type + ' #' + detail.subject_id : (detail.record_label || '—') }}</strong>
            </div>
          </div>

          <!-- Changes Section -->
          <template v-if="detail.old_values || detail.properties?.old || detail.new_values || detail.properties?.attributes || detail.payload">
            <div class="dp-section-title" style="margin-top:16px">Changes</div>

            <div v-if="detail.old_values || detail.properties?.old" class="dp-change-block">
              <div class="dp-change-label">Before</div>
              <pre class="dp-pre">{{ JSON.stringify(detail.old_values || detail.properties?.old, null, 2) }}</pre>
            </div>

            <div v-if="detail.new_values || detail.properties?.attributes" class="dp-change-block">
              <div class="dp-change-label">After</div>
              <pre class="dp-pre">{{ JSON.stringify(detail.new_values || detail.properties?.attributes, null, 2) }}</pre>
            </div>

            <div v-if="detail.payload" class="dp-change-block">
              <div class="dp-change-label">Payload</div>
              <pre class="dp-pre">{{ JSON.stringify(detail.payload, null, 2) }}</pre>
            </div>
          </template>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { auditLogService } from '@/services/audit-log.service'
import { useToast }        from '@/composables/useToast'

const { error } = useToast()

const logs       = ref([])
const detail     = ref(null)
const loading    = ref(false)
const page       = ref(1)
const perPage    = 20
const total      = ref(0)

const filters = ref({ search: '', module: '', action: '', date_from: '', date_to: '' })

const moduleOptions = ['Member', 'Loan', 'Payment', 'Bill', 'ShareCapital', 'User', 'Setting', 'Auth']
const actionOptions = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'APPROVE', 'REJECT', 'VOID']

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage)))

const pageStart = computed(() => total.value === 0 ? 0 : (page.value - 1) * perPage + 1)
const pageEnd   = computed(() => Math.min(page.value * perPage, total.value))

const visiblePages = computed(() => {
  const total = totalPages.value
  const cur   = page.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const pages = []
  if (cur <= 4) {
    for (let i = 1; i <= 5; i++) pages.push(i)
    pages.push('…')
    pages.push(total)
  } else if (cur >= total - 3) {
    pages.push(1)
    pages.push('…')
    for (let i = total - 4; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    pages.push('…')
    for (let i = cur - 1; i <= cur + 1; i++) pages.push(i)
    pages.push('…')
    pages.push(total)
  }
  return pages
})

function formatDateTime(v) {
  if (!v) return '—'
  return new Date(v).toLocaleString('en-PH', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatDate(v) {
  if (!v) return '—'
  return new Date(v).toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' })
}

function formatTime(v) {
  if (!v) return ''
  return new Date(v).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })
}

function riskClass(r) {
  const v = (r || '').toUpperCase()
  if (v === 'HIGH')   return 'risk-high'
  if (v === 'MEDIUM') return 'risk-medium'
  return 'risk-low'
}

function riskBorderColor(r) {
  const v = (r || '').toUpperCase()
  if (v === 'HIGH')   return '#dc2626'
  if (v === 'MEDIUM') return '#d97706'
  return '#16a34a'
}

function actorInitials(name) {
  if (!name) return '?'
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map(w => w[0]?.toUpperCase() || '')
    .join('')
}

const AVATAR_COLORS = ['#5B2AA7', '#1A3A8B', '#1A6B3C', '#8B1A1A', '#92620A', '#0E7490', '#be185d', '#7c3aed']
function avatarColor(name) {
  if (!name) return '#7C7F8E'
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length]
}

function moduleClass(mod) {
  const m = (mod || '').toLowerCase()
  if (m === 'member')       return 'mod-member'
  if (m === 'loan')         return 'mod-loan'
  if (m === 'payment')      return 'mod-payment'
  if (m === 'bill')         return 'mod-bill'
  if (m === 'sharecapital') return 'mod-sharecapital'
  if (m === 'user')         return 'mod-user'
  if (m === 'auth')         return 'mod-auth'
  return 'mod-default'
}

function actionClass(action) {
  const a = (action || '').toUpperCase()
  if (a === 'CREATE' || a === 'APPROVE') return 'act-green'
  if (a === 'UPDATE' || a === 'LOGIN' || a === 'LOGOUT') return 'act-blue'
  if (a === 'DELETE' || a === 'VOID' || a === 'REJECT') return 'act-red'
  if (a === 'LOGIN' || a === 'LOGOUT')  return 'act-amber'
  return 'act-default'
}

async function load() {
  loading.value = true
  detail.value  = null
  try {
    const params = { page: page.value, per_page: perPage }
    if (filters.value.search)    params.search    = filters.value.search
    if (filters.value.module)    params.module    = filters.value.module
    if (filters.value.action)    params.action    = filters.value.action
    if (filters.value.date_from) params.date_from = filters.value.date_from
    if (filters.value.date_to)   params.date_to   = filters.value.date_to

    const res = await auditLogService.index(params)
    const d   = res.data
    logs.value  = d?.data ?? d ?? []
    total.value = d?.meta?.total ?? d?.total ?? logs.value.length
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to load audit logs.')
  } finally {
    loading.value = false
  }
}

function changePage(p) {
  page.value = p
  load()
}

onMounted(load)
</script>

<style scoped>
/* ── Layout ── */
.page-wrap {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  background: var(--surface);
}

/* ── Page Header ── */
.page-header {
  padding: 22px 28px 18px;
  background: white;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.page-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--ink);
  letter-spacing: -0.3px;
  line-height: 1.2;
}
.page-subtitle {
  margin-top: 4px;
  font-size: 13px;
  color: var(--ink-muted);
}

/* ── Filter Bar ── */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  padding: 12px 24px;
  background: white;
  border-bottom: 1px solid var(--border);
  box-shadow: 0 2px 6px rgba(26,27,31,0.05);
  flex-shrink: 0;
}

.search-wrap {
  position: relative;
  flex: 1;
  min-width: 200px;
}
.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ink-muted);
  pointer-events: none;
}
.search-input {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 7px 10px 7px 32px;
  font-size: 13px;
  outline: none;
  color: var(--ink);
  transition: border-color .15s;
}
.search-input:focus { border-color: var(--crs-red); }

.filter-select,
.filter-date {
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 7px 10px;
  font-size: 13px;
  outline: none;
  background: white;
  color: var(--ink);
  cursor: pointer;
  transition: border-color .15s;
}
.filter-select:focus,
.filter-date:focus { border-color: var(--crs-red); }

.run-btn {
  background: var(--crs-red);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 7px 18px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s;
  white-space: nowrap;
}
.run-btn:hover { background: #701515; }

/* ── Content grid ── */
.page-content {
  flex: 1;
  overflow: hidden;
  display: grid;
  grid-template-columns: 1fr;
}
.page-content.with-panel { grid-template-columns: 1fr 360px; }

/* ── Table Area ── */
.table-area {
  overflow-y: auto;
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.table-wrap {
  background: white;
  border: 1px solid var(--border);
  border-radius: 10px;
  overflow: hidden;
  box-shadow: var(--shadow-xs);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.data-table th {
  padding: 10px 14px;
  text-align: left;
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--ink-muted);
  background: var(--surface);
  border-bottom: 1px solid var(--border);
}
.data-table td {
  padding: 11px 14px;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.data-table tbody tr:last-child td { border-bottom: none; }

/* Row states */
.log-row { cursor: pointer; transition: background .12s; border-left: 3px solid transparent; }
.log-row:hover      { background: #f8f8fa; }
.log-row-active     { background: #fef2f2; }

/* ── Timestamp cell ── */
.td-timestamp { white-space: nowrap; }
.ts-date {
  display: block;
  font-family: var(--font-mono);
  font-size: 11.5px;
  color: var(--ink);
  font-weight: 500;
}
.ts-time {
  display: block;
  font-family: var(--font-mono);
  font-size: 10.5px;
  color: var(--ink-muted);
  margin-top: 2px;
}

/* ── Actor cell ── */
.actor-wrap { display: flex; align-items: center; gap: 9px; }
.actor-avatar {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  color: white;
  letter-spacing: 0;
}
.actor-name {
  font-weight: 600;
  font-size: 12.5px;
  color: var(--ink);
  white-space: nowrap;
}
.actor-role {
  font-size: 10.5px;
  color: var(--ink-muted);
  margin-top: 1px;
}

/* ── Module badges ── */
.module-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 99px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  white-space: nowrap;
}
.mod-member      { background: #ede9fe; color: #5B2AA7; }
.mod-loan        { background: #dbeafe; color: #1e40af; }
.mod-payment     { background: #dcfce7; color: #166534; }
.mod-bill        { background: #fef3c7; color: #92400e; }
.mod-sharecapital{ background: #ccfbf1; color: #0f766e; }
.mod-user        { background: #f1f5f9; color: #475569; }
.mod-auth        { background: #fee2e2; color: #991b1b; }
.mod-default     { background: #f1f5f9; color: #6b7280; }

/* ── Action text ── */
.action-text {
  font-size: 12.5px;
  font-weight: 700;
}
.act-green   { color: #16a34a; }
.act-blue    { color: #1d4ed8; }
.act-red     { color: #dc2626; }
.act-amber   { color: #d97706; }
.act-default { color: var(--ink); }

/* ── Record cell ── */
.td-record {
  font-size: 11.5px;
  color: var(--ink-muted);
  font-family: var(--font-mono);
}

/* ── Risk badge ── */
.risk-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 99px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  white-space: nowrap;
}
.risk-high   { background: #fee2e2; color: #991b1b; }
.risk-medium { background: #fef3c7; color: #92400e; }
.risk-low    { background: #f0fdf4; color: #166534; }

/* ── Pagination ── */
.pager {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 4px;
  flex-shrink: 0;
}
.pager-info {
  font-size: 12px;
  color: var(--ink-muted);
}
.pager-controls {
  display: flex;
  align-items: center;
  gap: 4px;
}
.pager-btn {
  width: 30px;
  height: 30px;
  border: 1px solid var(--border);
  background: white;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ink);
  transition: background .12s, color .12s, border-color .12s;
  line-height: 1;
}
.pager-btn:hover:not(:disabled) {
  background: var(--crs-red);
  color: white;
  border-color: var(--crs-red);
}
.pager-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.pager-btn-active {
  background: var(--crs-red);
  color: white;
  border-color: var(--crs-red);
}
.pager-ellipsis {
  width: 30px;
  text-align: center;
  font-size: 13px;
  color: var(--ink-muted);
}

/* ── Loading ── */
.loading-state { display: flex; justify-content: center; padding: 72px; }
.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid var(--border);
  border-top-color: var(--crs-red);
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Empty State ── */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 72px 32px;
  gap: 10px;
  background: white;
  border: 1px solid var(--border);
  border-radius: 10px;
  box-shadow: var(--shadow-xs);
}
.empty-icon-wrap { opacity: 0.7; }
.empty-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--ink);
  margin-top: 4px;
}
.empty-sub {
  font-size: 13px;
  color: var(--ink-muted);
}

/* ── Detail Panel ── */
.detail-panel {
  border-left: 1px solid var(--border);
  background: white;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.dp-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 18px;
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  background: white;
  z-index: 2;
  gap: 10px;
}
.dp-head-left {
  display: flex;
  align-items: center;
  gap: 10px;
}
.dp-event-id {
  font-size: 14px;
  font-weight: 700;
  color: var(--ink);
}
.dp-close {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--ink-muted);
  padding: 5px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background .12s, color .12s;
}
.dp-close:hover {
  background: var(--surface);
  color: var(--ink);
}

.dp-body {
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.dp-section-title {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--ink-muted);
  margin-bottom: 8px;
}

.dp-info-block {
  border: 1px solid var(--border);
  border-radius: 8px;
  overflow: hidden;
}
.dp-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
  padding: 9px 12px;
  border-bottom: 1px solid var(--border);
}
.dp-row:last-child { border-bottom: none; }
.dp-label {
  font-size: 11.5px;
  color: var(--ink-muted);
  white-space: nowrap;
  flex-shrink: 0;
}
.dp-value {
  font-size: 12px;
  font-weight: 600;
  color: var(--ink);
  text-align: right;
}
.dp-value.mono { font-family: var(--font-mono); }

.dp-change-block { margin-top: 10px; }
.dp-change-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--ink-muted);
  margin-bottom: 5px;
}
.dp-pre {
  background: #1e293b;
  color: #e2e8f0;
  border-radius: 7px;
  padding: 10px 12px;
  font-size: 10.5px;
  font-family: var(--font-mono);
  white-space: pre-wrap;
  word-break: break-all;
  max-height: 220px;
  overflow-y: auto;
  line-height: 1.6;
}

.mono { font-family: var(--font-mono); }
</style>
