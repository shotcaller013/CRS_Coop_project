<template>
  <div class="page-wrap">
    <!-- Page Header with pill tab switcher -->
    <header class="topbar">
      <div class="topbar-title-group">
        <span class="topbar-page">Notification Logs</span>
        <span class="topbar-sub">SMS, email, and system notification history</span>
      </div>
      <div class="tab-pill-group">
        <button :class="['pill-btn', activeTab === 'logs' && 'pill-active']" @click="activeTab = 'logs'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          Logs
        </button>
        <button :class="['pill-btn', activeTab === 'settings' && 'pill-active']" @click="switchToSettings">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Settings
        </button>
      </div>
    </header>

    <!-- ── Logs tab ── -->
    <template v-if="activeTab === 'logs'">
      <!-- Filter bar -->
      <div class="filter-bar">
        <div class="search-wrap">
          <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="filters.search" type="text" placeholder="Search recipient, event…" class="search-input" @keyup.enter="load" />
        </div>
        <select v-model="filters.channel" class="filter-select" @change="load">
          <option value="">All channels</option>
          <option value="SMS">SMS</option>
          <option value="Email">Email</option>
          <option value="System">System</option>
        </select>
        <select v-model="filters.status" class="filter-select" @change="load">
          <option value="">All statuses</option>
          <option value="Queued">Queued</option>
          <option value="Sent">Sent</option>
          <option value="Failed">Failed</option>
          <option value="Disabled">Disabled</option>
        </select>
        <button class="run-btn" @click="load">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Search
        </button>
      </div>

      <div class="page-content">
        <div v-if="loading" class="loading-state"><div class="spinner"></div></div>

        <template v-else-if="logs.length">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>Channel</th>
                  <th>Recipient</th>
                  <th>Event</th>
                  <th>Message</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs" :key="log.id" class="data-row">
                  <td class="ts-cell">
                    <span class="ts-date">{{ formatDateOnly(log.created_at || log.sent_at) }}</span>
                    <span class="ts-time">{{ formatTimeOnly(log.created_at || log.sent_at) }}</span>
                  </td>
                  <td>
                    <span :class="['channel-badge', channelClass(log.channel)]">
                      <span class="ch-icon">{{ channelIcon(log.channel) }}</span>
                      {{ log.channel }}
                    </span>
                  </td>
                  <td class="recipient-cell">{{ log.recipient || log.contact || '—' }}</td>
                  <td class="event-cell">{{ log.event || log.subject || '—' }}</td>
                  <td class="msg-cell">
                    <div class="msg-truncate">{{ log.message || log.body || '—' }}</div>
                  </td>
                  <td>
                    <span :class="['status-badge', statusClass(log.status)]">{{ log.status }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="pager">
            <span class="pager-info">
              Showing {{ pageStart }}–{{ pageEnd }} of {{ total }} records
            </span>
            <div class="pager-controls">
              <button class="pager-btn" :disabled="page <= 1" @click="changePage(page - 1)">‹</button>
              <template v-for="p in pageRange" :key="p">
                <button
                  v-if="p !== '…'"
                  :class="['pager-btn', p === page && 'pager-active']"
                  @click="changePage(p)"
                >{{ p }}</button>
                <span v-else class="pager-ellipsis">…</span>
              </template>
              <button class="pager-btn" :disabled="page >= totalPages" @click="changePage(page + 1)">›</button>
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <div v-else class="empty-state">
          <div class="empty-icon-wrap">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="empty-svg">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </div>
          <div class="empty-title">No notification logs found</div>
          <div class="empty-sub">Notifications are logged when SMS/email events are triggered by the system.</div>
        </div>
      </div>
    </template>

    <!-- ── Settings tab ── -->
    <template v-if="activeTab === 'settings'">
      <div class="settings-content">
        <div v-if="settingsLoading" class="loading-state"><div class="spinner"></div></div>
        <template v-else-if="settings">

          <!-- SMS Settings card -->
          <div class="settings-card sms-accent">
            <div class="card-head">
              <div class="card-head-left">
                <div class="card-head-icon amber-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                </div>
                <span class="card-head-title">SMS Settings</span>
              </div>
              <span class="provider-badge">{{ settings.sms_provider || 'Semaphore' }}</span>
            </div>
            <div class="settings-body">
              <div class="setting-row">
                <div class="setting-info">
                  <strong>SMS Provider</strong>
                  <span>API key used to authenticate with the SMS gateway</span>
                </div>
                <input v-model="settings.sms_api_key" type="text" placeholder="API Key" class="setting-input" />
              </div>
              <div class="setting-row">
                <div class="setting-info">
                  <strong>Sender Name</strong>
                  <span>Name shown in SMS header</span>
                </div>
                <input v-model="settings.sms_sender_name" type="text" placeholder="CRSCOOP" class="setting-input" />
              </div>
            </div>
          </div>

          <!-- Notification Events card -->
          <div class="settings-card events-accent">
            <div class="card-head">
              <div class="card-head-left">
                <div class="card-head-icon blue-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <span class="card-head-title">Notification Events</span>
              </div>
            </div>
            <div class="settings-body">
              <div
                v-for="(enabled, event, idx) in (settings.events || {})"
                :key="event"
                :class="['setting-row', idx % 2 === 1 && 'setting-row-alt']"
              >
                <div class="setting-info">
                  <strong>{{ formatEvent(event) }}</strong>
                  <span>{{ eventDesc(event) }}</span>
                </div>
                <label class="toggle">
                  <input type="checkbox" :checked="enabled" @change="settings.events[event] = $event.target.checked" />
                  <span class="toggle-track"></span>
                </label>
              </div>
            </div>
          </div>

          <!-- Actions footer card -->
          <div class="settings-actions-card">
            <div class="actions-left">
              <span class="test-label">Send a test SMS to verify your settings</span>
              <div class="test-sms-row">
                <input v-model="testNumber" type="text" placeholder="09XXXXXXXXX" class="test-input" />
                <button class="test-btn" :disabled="!testNumber || testLoading" @click="sendTest">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                  {{ testLoading ? 'Sending…' : 'Send Test SMS' }}
                </button>
              </div>
            </div>
            <button class="save-btn" :disabled="saveLoading" @click="saveSettings">
              <svg v-if="!saveLoading" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              {{ saveLoading ? 'Saving…' : 'Save Settings' }}
            </button>
          </div>

        </template>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { notificationService } from '@/services/notification.service'
import { useToast }            from '@/composables/useToast'

const { success, error } = useToast()

const logs          = ref([])
const settings      = ref(null)
const loading       = ref(false)
const settingsLoading = ref(false)
const saveLoading   = ref(false)
const testLoading   = ref(false)
const page          = ref(1)
const perPage       = 25
const total         = ref(0)
const activeTab     = ref('logs')
const testNumber    = ref('')
const filters       = ref({ search: '', channel: '', status: '' })

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage)))

function formatDateTime(v) {
  if (!v) return '—'
  return new Date(v).toLocaleString('en-PH', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatDateOnly(v) {
  if (!v) return '—'
  return new Date(v).toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' })
}

function formatTimeOnly(v) {
  if (!v) return ''
  return new Date(v).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })
}

function channelClass(c) {
  if (c === 'SMS')    return 'ch-sms'
  if (c === 'Email')  return 'ch-email'
  return 'ch-system'
}

function channelIcon(c) {
  if (c === 'SMS')   return '📱'
  if (c === 'Email') return '✉️'
  return '🔔'
}

function statusClass(s) {
  if (s === 'Sent')     return 'st-sent'
  if (s === 'Failed')   return 'st-failed'
  if (s === 'Queued')   return 'st-queued'
  return 'st-disabled'
}

function formatEvent(k) {
  return k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function eventDesc(k) {
  const map = {
    loan_submitted:    'Notify when a loan application is submitted',
    loan_approved:     'Notify when a loan is approved',
    loan_released:     'Notify when a loan is released/disbursed',
    payment_due:       'Remind members before their payment due date',
    payment_posted:    'Confirm when a payment is posted',
    payment_overdue:   'Alert when a payment period becomes overdue',
    bill_issued:       'Notify companies when a billing is issued',
  }
  return map[k] || 'System notification event'
}

async function load() {
  loading.value = true
  try {
    const params = { page: page.value, per_page: perPage }
    if (filters.value.search)  params.search  = filters.value.search
    if (filters.value.channel) params.channel = filters.value.channel
    if (filters.value.status)  params.status  = filters.value.status

    const res   = await notificationService.index(params)
    const d     = res.data
    logs.value  = d?.data ?? d ?? []
    total.value = d?.meta?.total ?? d?.total ?? logs.value.length
  } catch (e) {
    error('Failed to load notification logs.')
  } finally {
    loading.value = false
  }
}

async function switchToSettings() {
  activeTab.value = 'settings'
  if (settings.value) return
  settingsLoading.value = true
  try {
    const res       = await notificationService.settings()
    settings.value  = res.data?.data ?? res.data ?? {}
  } catch {
    error('Failed to load notification settings.')
  } finally {
    settingsLoading.value = false
  }
}

async function saveSettings() {
  saveLoading.value = true
  try {
    await notificationService.updateSettings(settings.value)
    success('Notification settings saved.')
  } catch {
    error('Failed to save settings.')
  } finally {
    saveLoading.value = false
  }
}

async function sendTest() {
  testLoading.value = true
  try {
    await notificationService.testSms({ number: testNumber.value })
    success('Test SMS sent to ' + testNumber.value)
    testNumber.value = ''
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to send test SMS.')
  } finally {
    testLoading.value = false
  }
}

function changePage(p) {
  page.value = p
  load()
}

// Pagination helpers (new, purely computed — no logic change)
const pageStart = computed(() => Math.min((page.value - 1) * perPage + 1, total.value))
const pageEnd   = computed(() => Math.min(page.value * perPage, total.value))

const pageRange = computed(() => {
  const tp = totalPages.value
  const p  = page.value
  if (tp <= 7) return Array.from({ length: tp }, (_, i) => i + 1)
  const pages = []
  pages.push(1)
  if (p > 3)         pages.push('…')
  for (let i = Math.max(2, p - 1); i <= Math.min(tp - 1, p + 1); i++) pages.push(i)
  if (p < tp - 2)    pages.push('…')
  pages.push(tp)
  return pages
})

onMounted(load)
</script>

<style scoped>
/* ── Layout ── */
.page-wrap { display: flex; flex-direction: column; height: 100%; overflow: hidden; }

/* ── Header ── */
.topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 24px; border-bottom: 1px solid var(--border);
  background: white; flex-shrink: 0; gap: 16px;
}
.topbar-title-group { display: flex; flex-direction: column; gap: 2px; }
.topbar-page { font-weight: 700; font-size: 15px; color: var(--ink); line-height: 1.2; }
.topbar-sub  { font-size: 12px; color: var(--ink-muted); }

/* Pill tab switcher */
.tab-pill-group {
  display: flex; gap: 0; border: 1px solid var(--border); border-radius: 8px;
  overflow: hidden; background: white; flex-shrink: 0;
}
.pill-btn {
  display: flex; align-items: center; gap: 5px;
  padding: 6px 14px; font-size: 12px; font-weight: 500;
  border: none; background: transparent; cursor: pointer;
  color: var(--ink-muted); transition: color .15s, background .15s;
}
.pill-btn:first-child { border-right: 1px solid var(--border); }
.pill-btn:hover:not(.pill-active) { background: var(--surface); color: var(--ink); }
.pill-active { background: var(--crs-red) !important; color: white !important; }

/* ── Filter bar ── */
.filter-bar {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  padding: 10px 24px; border-bottom: 1px solid var(--border);
  background: white; flex-shrink: 0;
}
.search-wrap {
  position: relative; flex: 1; min-width: 180px;
}
.search-icon {
  position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
  color: var(--ink-muted); pointer-events: none;
}
.search-input {
  width: 100%; border: 1px solid var(--border); border-radius: 8px;
  padding: 7px 10px 7px 32px; font-size: 12px; outline: none;
  background: white; transition: border-color .15s; box-sizing: border-box;
}
.search-input:focus { border-color: var(--crs-red); }
.filter-select {
  border: 1px solid var(--border); border-radius: 8px;
  padding: 7px 10px; font-size: 12px; outline: none; background: white;
  color: var(--ink); cursor: pointer; transition: border-color .15s;
}
.filter-select:focus { border-color: var(--crs-red); }
.run-btn {
  display: flex; align-items: center; gap: 5px;
  background: var(--crs-red); color: white; border: none;
  border-radius: 8px; padding: 7px 14px; font-size: 12px; font-weight: 600;
  cursor: pointer; transition: opacity .15s; flex-shrink: 0;
}
.run-btn:hover { opacity: .88; }

/* ── Page content ── */
.page-content {
  flex: 1; overflow-y: auto; padding: 16px 24px;
  display: flex; flex-direction: column; gap: 12px;
}

/* ── Table ── */
.table-wrap { border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead { background: var(--surface); }
.data-table th {
  padding: 9px 14px; text-align: left; font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .5px; color: var(--ink-muted);
  border-bottom: 1px solid var(--border); white-space: nowrap;
}
.data-table td { padding: 10px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.data-row:last-child td { border-bottom: none; }
.data-row:hover { background: #f8fafc; }

/* Timestamp */
.ts-cell { font-family: var(--font-mono); white-space: nowrap; }
.ts-date { display: block; font-size: 11px; font-weight: 600; color: var(--ink); }
.ts-time { display: block; font-size: 10px; color: var(--ink-muted); margin-top: 2px; }

/* Channel badge */
.channel-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700; padding: 3px 8px;
  border-radius: 99px; white-space: nowrap;
}
.ch-icon { font-size: 11px; }
.ch-sms    { background: #fef3c7; color: #92400e; }
.ch-email  { background: #dbeafe; color: #1e40af; }
.ch-system { background: #ede9fe; color: #6d28d9; }

/* Other cells */
.recipient-cell { font-size: 13px; font-weight: 600; color: var(--ink); }
.event-cell     { font-size: 12px; font-weight: 600; color: #1d4ed8; }
.msg-cell       { max-width: 260px; }
.msg-truncate {
  font-size: 12px; color: var(--ink-muted);
  overflow: hidden; display: -webkit-box;
  -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}

/* Status badge */
.status-badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 99px; }
.st-sent     { background: #dcfce7; color: #166534; }
.st-failed   { background: #fee2e2; color: #991b1b; }
.st-queued   { background: #fef3c7; color: #92400e; }
.st-disabled { background: #f1f5f9; color: #64748b; }

/* ── Pagination ── */
.pager {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 2px; flex-wrap: wrap; gap: 8px;
}
.pager-info { font-size: 12px; color: var(--ink-muted); }
.pager-controls { display: flex; align-items: center; gap: 4px; }
.pager-btn {
  width: 30px; height: 30px; border: 1px solid var(--border); background: white;
  border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500;
  display: inline-flex; align-items: center; justify-content: center;
  color: var(--ink); transition: background .15s, color .15s, border-color .15s;
}
.pager-btn:hover:not(:disabled):not(.pager-active) {
  background: var(--crs-red); color: white; border-color: var(--crs-red);
}
.pager-btn:disabled { opacity: .35; cursor: not-allowed; }
.pager-active { background: var(--crs-red) !important; color: white !important; border-color: var(--crs-red) !important; }
.pager-ellipsis { font-size: 13px; color: var(--ink-muted); padding: 0 4px; }

/* ── Empty state ── */
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 72px 24px; gap: 10px; }
.empty-icon-wrap {
  width: 72px; height: 72px; border-radius: 50%; background: #f1f5f9;
  display: flex; align-items: center; justify-content: center; margin-bottom: 4px;
}
.empty-svg { color: #94a3b8; }
.empty-title { font-size: 15px; font-weight: 700; color: var(--ink); }
.empty-sub   { font-size: 13px; color: var(--ink-muted); text-align: center; max-width: 340px; line-height: 1.5; }

/* ── Settings ── */
.settings-content {
  flex: 1; overflow-y: auto; padding: 20px 24px;
  display: flex; flex-direction: column; gap: 16px;
}

.settings-card {
  background: white; border: 1px solid var(--border);
  border-radius: 10px; overflow: hidden;
}
.sms-accent    { border-left: 3px solid #f59e0b; }
.events-accent { border-left: 3px solid #3b82f6; }

.card-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 18px; border-bottom: 1px solid var(--border);
  background: var(--surface);
}
.card-head-left { display: flex; align-items: center; gap: 8px; }
.card-head-icon {
  width: 26px; height: 26px; border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
}
.amber-icon { background: #fef3c7; color: #92400e; }
.blue-icon  { background: #dbeafe; color: #1e40af; }
.card-head-title { font-size: 13px; font-weight: 700; color: var(--ink); }

.provider-badge {
  font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 99px;
  background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;
}

.settings-body { padding: 0; }
.setting-row {
  display: flex; justify-content: space-between; align-items: center; gap: 16px;
  padding: 13px 18px; border-bottom: 1px solid var(--border);
}
.setting-row:last-child { border-bottom: none; }
.setting-row-alt { background: #fafbfc; }
.setting-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.setting-info strong { font-size: 13px; color: var(--ink); }
.setting-info span   { font-size: 12px; color: var(--ink-muted); }
.setting-input {
  border: 1px solid var(--border); border-radius: 8px;
  padding: 7px 10px; font-size: 12px; outline: none;
  width: 210px; flex-shrink: 0; transition: border-color .15s;
}
.setting-input:focus { border-color: var(--crs-red); }

/* Toggle */
.toggle { position: relative; display: inline-block; width: 40px; height: 22px; flex-shrink: 0; }
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle-track {
  position: absolute; inset: 0; background: #cbd5e1; border-radius: 99px;
  cursor: pointer; transition: background .2s;
}
.toggle input:checked + .toggle-track { background: var(--crs-red); }
.toggle-track::after {
  content: ''; position: absolute; top: 3px; left: 3px;
  width: 16px; height: 16px; background: white; border-radius: 50%;
  transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.15);
}
.toggle input:checked + .toggle-track::after { transform: translateX(18px); }

/* Settings action footer card */
.settings-actions-card {
  display: flex; justify-content: space-between; align-items: flex-end;
  background: white; border: 1px solid var(--border); border-radius: 10px;
  padding: 16px 18px; gap: 16px; flex-wrap: wrap;
}
.actions-left { display: flex; flex-direction: column; gap: 8px; }
.test-label { font-size: 12px; color: var(--ink-muted); font-weight: 500; }
.test-sms-row { display: flex; gap: 8px; align-items: center; }
.test-input {
  border: 1px solid var(--border); border-radius: 8px;
  padding: 7px 10px; font-size: 13px; outline: none;
  width: 160px; transition: border-color .15s;
}
.test-input:focus { border-color: var(--crs-red); }
.test-btn {
  display: flex; align-items: center; gap: 5px;
  border: 1px solid var(--border); background: white; border-radius: 8px;
  padding: 7px 14px; font-size: 12px; font-weight: 600; cursor: pointer;
  color: var(--ink); transition: border-color .15s, background .15s;
}
.test-btn:hover:not(:disabled) { border-color: var(--crs-red); color: var(--crs-red); }
.test-btn:disabled { opacity: .5; cursor: not-allowed; }
.save-btn {
  display: flex; align-items: center; gap: 6px;
  background: var(--crs-red); color: white; border: none;
  border-radius: 8px; padding: 9px 20px; font-size: 13px;
  font-weight: 600; cursor: pointer; transition: opacity .15s; flex-shrink: 0;
}
.save-btn:hover:not(:disabled) { opacity: .88; }
.save-btn:disabled { opacity: .5; cursor: not-allowed; }

/* ── Loading / spinner ── */
.loading-state { display: flex; justify-content: center; padding: 72px; }
.spinner {
  width: 28px; height: 28px; border: 3px solid var(--border);
  border-top-color: var(--crs-red); border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
