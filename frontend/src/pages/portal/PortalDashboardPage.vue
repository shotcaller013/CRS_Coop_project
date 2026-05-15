<template>
  <div class="portal-shell">
    <!-- Sidebar -->
    <aside class="portal-sidebar">
      <div class="portal-brand">
        <div class="brand-logo">CRS</div>
        <div>
          <div class="brand-name">CRS Holdings Employees Credit Coop</div>
          <div class="brand-sub">Member Portal</div>
        </div>
      </div>

      <nav class="portal-nav">
        <button
          v-for="item in navItems"
          :key="item.view"
          :class="['pnav-item', activeView === item.view && 'active']"
          @click="activeView = item.view"
        >
          <svg class="pnav-icon" viewBox="0 0 24 24" v-html="item.icon"></svg>
          <span>{{ item.label }}</span>
        </button>
      </nav>

      <div class="portal-footer">
        <div class="member-pill">
          <div class="member-av">{{ initials }}</div>
          <div class="member-info">
            <div class="member-name">{{ fullName }}</div>
            <div class="member-no">{{ member?.member_no }}</div>
          </div>
        </div>
        <button class="logout-btn" @click="handleLogout">Sign Out</button>
      </div>
    </aside>

    <!-- Main -->
    <main class="portal-main">
      <!-- Header -->
      <header class="portal-header">
        <div>
          <h1>{{ currentViewLabel }}</h1>
          <p class="header-sub">{{ fullName }} · {{ member?.member_no }} · {{ member?.company }}</p>
        </div>
        <span :class="['status-badge', member?.member_status === 'ACTIVE' ? 'badge-active' : 'badge-muted']">
          {{ member?.member_status || 'ACTIVE' }}
        </span>
      </header>

      <div v-if="portalStore.loading" class="portal-loading">
        <div class="spinner"></div>
      </div>

      <div v-else-if="!portalData" class="portal-loading">
        <p>No data available.</p>
      </div>

      <div v-else class="portal-content">

        <!-- ── Overview ───────────────────────────────── -->
        <template v-if="activeView === 'overview'">

          <!-- Stat cards -->
          <div class="summary-grid">
            <div class="summary-card" style="--accent:#1D9E75">
              <div class="sc-top">
                <span class="sc-label">Share Capital</span>
                <div class="sc-ico" style="background:#dcfce7;color:#166534">₱</div>
              </div>
              <strong class="sc-value">{{ peso(member?.share_capital) }}</strong>
              <small>Current balance</small>
            </div>

            <div class="summary-card" style="--accent:#378ADD">
              <div class="sc-top">
                <span class="sc-label">Active Loans</span>
                <div class="sc-ico" style="background:#dbeafe;color:#1e40af">#</div>
              </div>
              <strong class="sc-value">{{ activeLoans.length }}</strong>
              <small>{{ activeLoans.length === 1 ? 'Outstanding account' : 'Outstanding accounts' }}</small>
            </div>

            <div class="summary-card" style="--accent:#EF9F27">
              <div class="sc-top">
                <span class="sc-label">Next Due</span>
                <div class="sc-ico" style="background:#fef3c7;color:#92400e">!</div>
              </div>
              <strong class="sc-value" style="font-size:15px">{{ nextDueDate || '—' }}</strong>
              <small v-if="nextDueAmount" style="color:#92400e;font-weight:600">{{ peso(nextDueAmount) }}</small>
              <small v-else>No upcoming due</small>
            </div>

            <div class="summary-card" style="--accent:#7F77DD">
              <div class="sc-top">
                <span class="sc-label">Payments Made</span>
                <div class="sc-ico" style="background:#f3e8ff;color:#6b21a8">✓</div>
              </div>
              <strong class="sc-value">{{ portalData.payments?.length || 0 }}</strong>
              <small>Total recorded payments</small>
            </div>
          </div>

          <!-- Charts row -->
          <div class="charts-row">
            <!-- Share Capital Growth -->
            <div class="chart-card">
              <div class="chart-card-head">
                <div>
                  <div class="chart-title">Share Capital Growth</div>
                  <div class="chart-sub">Cumulative balance over time</div>
                </div>
                <span class="chart-badge pill-active">{{ peso(member?.share_capital) }}</span>
              </div>
              <div v-if="scChartData.labels.length" class="chart-wrap" style="height:180px">
                <canvas ref="scChart"></canvas>
              </div>
              <div v-else class="chart-empty">No share capital transactions yet.</div>
            </div>

            <!-- Loan Repayment Donut -->
            <div class="chart-card">
              <div class="chart-card-head">
                <div>
                  <div class="chart-title">Loan Repayment</div>
                  <div class="chart-sub">{{ activeLoans[0]?.loan_no || 'No active loan' }}</div>
                </div>
              </div>
              <template v-if="loanProgress">
                <div class="donut-wrapper">
                  <canvas ref="loanChart"></canvas>
                  <div class="donut-center">
                    <span class="donut-pct">{{ loanProgress.percentage }}%</span>
                    <span class="donut-pct-label">Paid</span>
                  </div>
                </div>
                <div class="donut-legend">
                  <span class="leg"><span class="leg-sq" style="background:#1D9E75"></span>Paid {{ peso(loanProgress.paid) }}</span>
                  <span class="leg"><span class="leg-sq" style="background:#e5e7eb"></span>Remaining {{ peso(loanProgress.remaining) }}</span>
                </div>
              </template>
              <div v-else class="chart-empty">No active loan to display.</div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="panel">
            <div class="panel-head">
              <h2>Recent Activity</h2>
              <span class="panel-count">{{ recentActivity.length }} events</span>
            </div>
            <div class="activity-scroll">
              <div v-if="!recentActivity.length" class="empty-row">No recent activity.</div>
              <div v-for="item in recentActivity" :key="item.detail + item.sortKey" class="activity-row">
                <div class="activity-dot" :style="{ background: item.color }"></div>
                <div class="activity-body">
                  <strong>{{ item.title }}</strong>
                  <span>{{ item.detail }}</span>
                </div>
                <span :class="['pill', item.pillClass]">{{ item.status }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- ── My Loans ─────────────────────────────── -->
        <template v-if="activeView === 'loans'">
          <div class="panel">
            <div class="panel-head">
              <h2>My Loans</h2>
              <span class="panel-count">{{ pagedLoans.total }} records</span>
            </div>
            <div v-if="!pagedLoans.total" class="empty-row">No loan records found.</div>
            <div v-for="loan in pagedLoans.items" :key="loan.loan_no" class="loan-row">
              <div class="loan-type-pill" :style="loanTypePillStyle(loan.status)">
                {{ loan.type.charAt(0) }}
              </div>
              <div class="loan-body">
                <div class="loan-title">
                  <strong>{{ loan.type }}</strong>
                  <span :class="['pill', loanStatusClass(loan.status)]">{{ loan.status }}</span>
                </div>
                <div class="loan-meta">
                  <span class="mono">{{ loan.loan_no }}</span>
                  <span v-if="loan.next_due_date">· Next due {{ formatDate(loan.next_due_date) }}</span>
                </div>
                <div v-if="loan.status === 'ACTIVE'" class="loan-progress-row">
                  <div class="loan-progress-bar">
                    <div class="loan-progress-fill"
                      :style="{ width: loanPaidPct(loan) + '%', background: '#1D9E75' }"></div>
                  </div>
                  <span class="loan-progress-pct">{{ loanPaidPct(loan) }}% repaid</span>
                </div>
              </div>
              <div class="loan-amounts">
                <div>
                  <span class="amount-label">Outstanding</span>
                  <span class="amount-value">{{ peso(loan.outstanding) }}</span>
                </div>
                <div v-if="loan.next_due_amount">
                  <span class="amount-label">Next payment</span>
                  <span class="amount-value" style="color:#92400e">{{ peso(loan.next_due_amount) }}</span>
                </div>
              </div>
            </div>
            <div v-if="pagedLoans.pages > 1" class="pager">
              <span class="pager-info">{{ pagedLoans.from }}–{{ pagedLoans.to }} of {{ pagedLoans.total }}</span>
              <div class="pager-controls">
                <button class="pager-btn" :disabled="loanPage === 1" @click="loanPage--">‹</button>
                <button
                  v-for="p in pagedLoans.pages" :key="p"
                  :class="['pager-btn', p === loanPage && 'active']"
                  @click="loanPage = p"
                >{{ p }}</button>
                <button class="pager-btn" :disabled="loanPage >= pagedLoans.pages" @click="loanPage++">›</button>
              </div>
            </div>
          </div>
        </template>

        <!-- ── Payments ──────────────────────────────── -->
        <template v-if="activeView === 'payments'">
          <div class="panel">
            <div class="panel-head">
              <h2>Payment History</h2>
              <span class="panel-count">{{ pagedPayments.total }} records</span>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Loan No.</th>
                    <th class="right">Amount</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!pagedPayments.total">
                    <td colspan="5" class="empty-row">No payments found.</td>
                  </tr>
                  <tr v-for="row in pagedPayments.items" :key="row.reference + row.date">
                    <td>{{ formatDate(row.date) }}</td>
                    <td class="mono">{{ row.reference }}</td>
                    <td class="mono">{{ row.loan_no }}</td>
                    <td class="right mono bold">{{ peso(row.amount) }}</td>
                    <td><span class="pill pill-active">{{ row.status }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="pagedPayments.pages > 1" class="pager">
              <span class="pager-info">{{ pagedPayments.from }}–{{ pagedPayments.to }} of {{ pagedPayments.total }}</span>
              <div class="pager-controls">
                <button class="pager-btn" :disabled="paymentPage === 1" @click="paymentPage--">‹</button>
                <button
                  v-for="p in pagedPayments.pages" :key="p"
                  :class="['pager-btn', p === paymentPage && 'active']"
                  @click="paymentPage = p"
                >{{ p }}</button>
                <button class="pager-btn" :disabled="paymentPage >= pagedPayments.pages" @click="paymentPage++">›</button>
              </div>
            </div>
          </div>
        </template>

        <!-- ── Share Capital ─────────────────────────── -->
        <template v-if="activeView === 'shareCapital'">
          <div class="sc-summary-bar">
            <div class="sc-sum-item">
              <span>Current Balance</span>
              <strong style="color:#166534;font-size:22px">{{ peso(member?.share_capital) }}</strong>
            </div>
            <div class="sc-sum-divider"></div>
            <div class="sc-sum-item">
              <span>Total Transactions</span>
              <strong>{{ portalData.shareCapital?.length || 0 }}</strong>
            </div>
            <div class="sc-sum-divider"></div>
            <div class="sc-sum-item">
              <span>Total Deposited</span>
              <strong style="color:#166534">{{ peso(totalDeposited) }}</strong>
            </div>
            <div class="sc-sum-divider"></div>
            <div class="sc-sum-item">
              <span>Dividends Earned</span>
              <strong style="color:#92400e">{{ peso(totalDividends) }}</strong>
            </div>
          </div>

          <div class="panel">
            <div class="panel-head">
              <h2>Transaction Ledger</h2>
              <span class="panel-count">{{ pagedSC.total }} records</span>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Type</th>
                    <th class="right">Amount</th>
                    <th class="right">Balance</th>
                    <th>Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!pagedSC.total">
                    <td colspan="6" class="empty-row">No share capital transactions found.</td>
                  </tr>
                  <tr v-for="row in pagedSC.items" :key="row.reference + row.date">
                    <td>{{ formatDate(row.date) }}</td>
                    <td class="mono">{{ row.reference }}</td>
                    <td><span :class="['pill', scTypeClass(row.type)]">{{ row.type }}</span></td>
                    <td class="right mono">{{ peso(row.amount) }}</td>
                    <td class="right mono bold">{{ peso(row.balance) }}</td>
                    <td class="muted-text">{{ row.remarks || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="pagedSC.pages > 1" class="pager">
              <span class="pager-info">{{ pagedSC.from }}–{{ pagedSC.to }} of {{ pagedSC.total }}</span>
              <div class="pager-controls">
                <button class="pager-btn" :disabled="scPage === 1" @click="scPage--">‹</button>
                <button
                  v-for="p in pagedSC.pages" :key="p"
                  :class="['pager-btn', p === scPage && 'active']"
                  @click="scPage = p"
                >{{ p }}</button>
                <button class="pager-btn" :disabled="scPage >= pagedSC.pages" @click="scPage++">›</button>
              </div>
            </div>
          </div>
        </template>

        <!-- ── Beneficiaries ─────────────────────────── -->
        <template v-if="activeView === 'beneficiaries'">
          <div class="panel">
            <div class="panel-head">
              <h2>Beneficiaries</h2>
              <span class="panel-count">{{ portalData.beneficiaries?.length || 0 }} records</span>
            </div>
            <div v-if="!portalData.beneficiaries?.length" class="empty-row">No beneficiaries encoded.</div>
            <div v-for="row in portalData.beneficiaries" :key="row.name" class="beneficiary-row">
              <div class="ben-avatar"
                :style="row.type === 'Primary'
                  ? 'background:#dbeafe;color:#1e40af'
                  : 'background:#fef3c7;color:#92400e'">
                {{ row.name.split(' ').filter(Boolean).map(w => w[0]).join('').slice(0, 2).toUpperCase() }}
              </div>
              <div class="ben-body">
                <div class="ben-name">{{ row.name }}</div>
                <div class="ben-meta">{{ row.relationship }}{{ row.contact ? ' · ' + row.contact : '' }}</div>
              </div>
              <div class="ben-right">
                <span :class="['pill', row.type === 'Primary' ? 'pill-info' : 'pill-amber']">{{ row.type }}</span>
                <div class="allocation-badge">
                  <span class="allocation-pct">{{ row.allocation }}%</span>
                  <span class="allocation-label">allocation</span>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ── Profile ───────────────────────────────── -->
        <template v-if="activeView === 'profile'">
          <div class="panel">
            <div class="profile-hero">
              <div class="profile-avatar-lg">{{ initials }}</div>
              <div class="profile-hero-info">
                <div class="profile-name">{{ fullName }}</div>
                <div class="profile-sub">{{ member?.position }} · {{ member?.department }}</div>
                <div class="profile-tags">
                  <span class="pill pill-active">{{ member?.member_status }}</span>
                  <span class="pill pill-info">{{ member?.status }}</span>
                  <span class="pill pill-muted">{{ member?.branch }}</span>
                </div>
              </div>
            </div>
            <div class="profile-grid">
              <div v-for="[label, value] in profileFields" :key="label" class="profile-item">
                <span>{{ label }}</span>
                <strong>{{ value || '—' }}</strong>
              </div>
            </div>
          </div>
        </template>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { usePortalStore } from '@/stores/portal.store'
import Chart from 'chart.js/auto'

const router      = useRouter()
const portalStore = usePortalStore()

const activeView = ref('overview')
const scChart    = ref(null)
const loanChart  = ref(null)
let charts = {}

// ── Pagination ────────────────────────────────────────────
const PAGE_SIZE   = 10
const loanPage    = ref(1)
const paymentPage = ref(1)
const scPage      = ref(1)

// Reset pages when switching views so you always start at page 1
watch(activeView, () => {
  loanPage.value    = 1
  paymentPage.value = 1
  scPage.value      = 1
})

function paginate(items, page) {
  const total = items.length
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE))
  const p     = Math.min(page, pages)
  return {
    items: items.slice((p - 1) * PAGE_SIZE, p * PAGE_SIZE),
    page:  p,
    pages,
    total,
    from:  total === 0 ? 0 : (p - 1) * PAGE_SIZE + 1,
    to:    Math.min(p * PAGE_SIZE, total),
  }
}

const pagedLoans = computed(() => paginate(portalData.value?.loans ?? [], loanPage.value))
const pagedPayments = computed(() => paginate(portalData.value?.payments ?? [], paymentPage.value))
const pagedSC       = computed(() => paginate(portalData.value?.shareCapital ?? [], scPage.value))

const navItems = [
  {
    view: 'overview',
    label: 'Overview',
    // dashboard grid — same as admin Dashboard
    icon: '<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>',
  },
  {
    view: 'loans',
    label: 'My Loans',
    // document with lines — same as admin Applications
    icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
  },
  {
    view: 'payments',
    label: 'Payments',
    // credit card — same as admin Payments
    icon: '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>',
  },
  {
    view: 'shareCapital',
    label: 'Share Capital',
    // trending-up arrow — finance growth
    icon: '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
  },
  {
    view: 'beneficiaries',
    label: 'Beneficiaries',
    // heart — same as admin Beneficiaries
    icon: '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
  },
  {
    view: 'profile',
    label: 'Profile',
    // single user — same as admin Users
    icon: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  },
]

const currentViewLabel = computed(() =>
  navItems.find(n => n.view === activeView.value)?.label ?? 'Dashboard'
)

const member    = computed(() => portalStore.member)
const portalData = computed(() => portalStore.data)

const fullName = computed(() => {
  const m = member.value
  if (!m) return ''
  return [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ').trim()
})

const initials = computed(() => {
  return fullName.value.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) || '?'
})

const activeLoans = computed(() =>
  (portalData.value?.loans ?? []).filter(l => l.status === 'ACTIVE')
)

const nextDueDate = computed(() => {
  const loan = activeLoans.value[0]
  return loan?.next_due_date ? formatDate(loan.next_due_date) : null
})

const nextDueAmount = computed(() => {
  const loan = activeLoans.value[0]
  return loan?.next_due_amount || null
})

const loanProgress = computed(() => {
  const loan = activeLoans.value[0]
  if (!loan) return null
  const total = loan.total_payable || loan.amount
  const paid  = Math.max(0, total - loan.outstanding)
  return {
    paid,
    remaining:  loan.outstanding,
    percentage: total > 0 ? Math.round(paid / total * 100) : 0,
  }
})

const scChartData = computed(() => {
  const rows = [...(portalData.value?.shareCapital ?? [])].reverse()
  return {
    labels:   rows.map(r => formatDate(r.date)),
    balances: rows.map(r => r.balance),
  }
})

const totalDeposited = computed(() =>
  (portalData.value?.shareCapital ?? [])
    .filter(r => r.type === 'Deposit' || r.type === 'Opening')
    .reduce((s, r) => s + (r.amount || 0), 0)
)
const totalDividends = computed(() =>
  (portalData.value?.shareCapital ?? [])
    .filter(r => r.type === 'Dividend')
    .reduce((s, r) => s + (r.amount || 0), 0)
)

const recentActivity = computed(() => {
  const payments     = (portalData.value?.payments ?? []).slice(0, 10)
  const shareCapital = (portalData.value?.shareCapital ?? []).slice(0, 5)
  const items = [
    ...payments.map(r => ({
      title:     `${peso(r.amount)} loan payment posted`,
      detail:    `${r.reference} · ${formatDate(r.date)} · ${r.loan_no}`,
      status:    'POSTED',
      color:     '#1D9E75',
      pillClass: 'pill-active',
      sortKey:   r.date,
    })),
    ...shareCapital.map(r => ({
      title:     `${peso(r.amount)} share capital ${r.type.toLowerCase()}`,
      detail:    `${r.reference} · ${formatDate(r.date)}`,
      status:    r.type.toUpperCase(),
      color:     '#378ADD',
      pillClass: scTypeClass(r.type),
      sortKey:   r.date,
    })),
  ]
  // Sort newest-first then cap at 15
  return items.sort((a, b) => (b.sortKey ?? '').localeCompare(a.sortKey ?? '')).slice(0, 15)
})

const profileFields = computed(() => {
  const m = member.value
  if (!m) return []
  return [
    ['Member No.',        m.member_no],
    ['Email',             m.email],
    ['Contact',           m.contact],
    ['Address',           m.address],
    ['Company',           m.company],
    ['Branch',            m.branch],
    ['Department',        m.department],
    ['Position',          m.position],
    ['Employment Status', m.status],
    ['Monthly Salary',    peso(m.monthly_salary)],
    ['Share Capital',     peso(m.share_capital)],
    ['Member Status',     m.member_status],
  ]
})

// ── Helpers ───────────────────────────────────────────────
const pesoFmt = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 })
function peso(v) { return pesoFmt.format(Number(v || 0)) }
function formatDate(v) {
  if (!v) return '—'
  // Slice to YYYY-MM-DD — handles both plain dates and Laravel ISO timestamps ("…T00:00:00.000000Z")
  const d = new Date(`${String(v).slice(0, 10)}T12:00:00`)
  return isNaN(d.getTime()) ? '—' : d.toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' })
}

function loanPaidPct(loan) {
  const total = loan.total_payable || loan.amount
  if (!total) return 0
  return Math.round(Math.max(0, (total - loan.outstanding) / total * 100))
}

function loanTypePillStyle(status) {
  const map = {
    ACTIVE:   'background:#dbeafe;color:#1e40af',
    PENDING:  'background:#fef3c7;color:#92400e',
    APPROVED: 'background:#dcfce7;color:#166534',
    CLOSED:   'background:#f3e8ff;color:#6b21a8',
    DRAFT:    'background:#f1f5f9;color:#475569',
    OVERDUE:  'background:#fee2e2;color:#991b1b',
  }
  return map[status] ?? 'background:#f1f5f9;color:#475569'
}

function loanStatusClass(status) {
  return {
    ACTIVE:   'pill-info',
    PENDING:  'pill-amber',
    APPROVED: 'pill-active',
    CLOSED:   'pill-purple',
    DRAFT:    'pill-muted',
    OVERDUE:  'pill-danger',
  }[status] ?? 'pill-muted'
}

function scTypeClass(type) {
  return {
    Opening:    'pill-info',
    Deposit:    'pill-active',
    Withdrawal: 'pill-danger',
    Dividend:   'pill-amber',
    Adjustment: 'pill-purple',
  }[type] ?? 'pill-muted'
}

// ── Charts ────────────────────────────────────────────────
function buildCharts() {
  Object.values(charts).forEach(c => c?.destroy())
  charts = {}

  const data = scChartData.value
  if (scChart.value && data.labels.length) {
    charts.sc = new Chart(scChart.value, {
      type: 'line',
      data: {
        labels:   data.labels,
        datasets: [{
          data:                data.balances,
          borderColor:         '#1D9E75',
          backgroundColor:     'rgba(29,158,117,0.10)',
          fill:                true,
          tension:             0.4,
          pointRadius:         5,
          pointBackgroundColor:'#1D9E75',
          pointBorderColor:    '#fff',
          pointBorderWidth:    2,
        }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend:  { display: false },
          tooltip: { callbacks: { label: ctx => peso(ctx.raw) } },
        },
        scales: {
          x: { ticks: { font: { size: 10 } }, grid: { display: false } },
          y: {
            ticks:    { callback: v => '₱' + (v / 1000).toFixed(0) + 'k', font: { size: 10 } },
            grid:     { color: 'rgba(128,128,128,.1)' },
          },
        },
      },
    })
  }

  if (loanChart.value && loanProgress.value) {
    const lp = loanProgress.value
    charts.loan = new Chart(loanChart.value, {
      type: 'doughnut',
      data: {
        labels:   ['Paid', 'Remaining'],
        datasets: [{ data: [lp.paid, lp.remaining], backgroundColor: ['#1D9E75', '#e5e7eb'], borderWidth: 0 }],
      },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '72%',
        plugins: {
          legend:  { display: false },
          tooltip: { callbacks: { label: ctx => peso(ctx.raw) } },
        },
      },
    })
  }
}

watch([activeView, () => portalStore.data], async ([view]) => {
  if (view === 'overview') {
    await nextTick()
    buildCharts()
  }
})

function handleLogout() {
  portalStore.clearSession()
  router.replace('/portal')
}

onMounted(async () => {
  if (!portalStore.isLoggedIn) {
    router.replace('/portal')
    return
  }
  await portalStore.fetchDashboard()
  await nextTick()
  buildCharts()
})
</script>

<style scoped>
.portal-shell {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: #f3f5f8;
}

/* ── Sidebar ───────────────────────────────────────────── */
.portal-sidebar {
  width: 228px;
  min-width: 228px;
  background: var(--crs-red, #8b1a1a);
  display: flex;
  flex-direction: column;
}

.portal-brand {
  padding: 20px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  display: flex;
  align-items: center;
  gap: 10px;
}
.brand-logo {
  width: 38px; height: 38px;
  background: white;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 800;
  color: var(--crs-red, #8b1a1a);
  flex-shrink: 0;
  letter-spacing: -0.5px;
}
.brand-name { font-size: 10px; font-weight: 700; color: white; line-height: 1.4; text-transform: uppercase; letter-spacing: 0.04em; }
.brand-sub  { font-size: 9px; color: rgba(255,255,255,0.45); margin-top: 2px; }

.portal-nav { flex: 1; padding: 10px 10px; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }

.pnav-item {
  width: 100%;
  display: flex; align-items: center; gap: 10px;
  padding: 9px 10px;
  background: none; border: none;
  color: rgba(255,255,255,0.60);
  font-size: 13px; font-weight: 500;
  text-align: left; cursor: pointer;
  border-radius: 8px;
  transition: all 0.15s;
}
.pnav-item:hover { color: white; background: rgba(255,255,255,0.08); }
.pnav-item.active {
  color: white;
  background: rgba(255,255,255,0.13);
  font-weight: 600;
}
.pnav-icon {
  width: 17px; height: 17px;
  flex-shrink: 0;
  opacity: 0.85;
  stroke: currentColor;
  fill: none;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.portal-footer {
  padding: 14px 12px;
  border-top: 1px solid rgba(255,255,255,0.1);
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.member-pill { display: flex; align-items: center; gap: 9px; }
.member-av {
  width: 34px; height: 34px; border-radius: 10px;
  background: var(--omni-orange, #f97316);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: white;
  flex-shrink: 0;
}
.member-info { flex: 1; min-width: 0; }
.member-name { font-size: 12px; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.member-no   { font-size: 10px; color: rgba(255,255,255,0.4); margin-top: 1px; }
.logout-btn {
  width: 100%; padding: 8px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 8px;
  color: rgba(255,255,255,0.65);
  font-size: 12px; font-weight: 600;
  cursor: pointer; transition: all 0.15s;
}
.logout-btn:hover { background: rgba(255,255,255,0.14); color: white; }

/* ── Main ─────────────────────────────────────────────── */
.portal-main { flex: 1; min-width: 0; display: flex; flex-direction: column; overflow: hidden; }

.portal-header {
  padding: 16px 24px;
  background: white;
  border-bottom: 1px solid var(--border, #e5e7eb);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}
.portal-header h1 { font-size: 19px; font-weight: 700; margin: 0; }
.header-sub { font-size: 12px; color: var(--ink-muted, #6b7280); margin: 3px 0 0; }

.status-badge {
  border-radius: 999px;
  padding: 4px 14px;
  font-size: 11px; font-weight: 700;
  letter-spacing: 0.05em;
}
.badge-active { background: #dcfce7; color: #166534; }
.badge-muted  { background: #f1f5f9; color: #64748b; }

.portal-loading {
  flex: 1; display: flex;
  align-items: center; justify-content: center;
}
.spinner {
  width: 28px; height: 28px;
  border: 3px solid var(--border, #e5e7eb);
  border-top-color: var(--crs-red, #8b1a1a);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.portal-content { flex: 1; min-height: 0; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }

/* ── Stat Cards ───────────────────────────────────────── */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}
.summary-card {
  background: white;
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 12px;
  padding: 16px 18px 14px;
  display: flex; flex-direction: column; gap: 4px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.summary-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--accent);
  border-radius: 12px 12px 0 0;
}
.sc-top {
  display: flex; justify-content: space-between; align-items: flex-start;
  margin-bottom: 6px;
}
.sc-ico {
  width: 30px; height: 30px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 800;
  flex-shrink: 0;
}
.sc-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--ink-muted, #6b7280); margin-top: 2px; }
.sc-value { font-size: 22px; font-weight: 700; color: var(--ink, #111); line-height: 1; }
.summary-card small { font-size: 11px; color: var(--ink-muted, #6b7280); margin-top: 2px; }

/* ── Charts ───────────────────────────────────────────── */
.charts-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 14px;
}
.chart-card {
  background: white;
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 12px;
  padding: 16px 18px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.chart-card-head {
  display: flex; justify-content: space-between; align-items: flex-start;
  margin-bottom: 12px;
}
.chart-title { font-size: 13px; font-weight: 700; color: var(--ink, #111); }
.chart-sub   { font-size: 11px; color: var(--ink-muted, #6b7280); margin-top: 2px; }
.chart-badge {
  font-size: 12px; font-weight: 700;
  padding: 3px 10px;
  border-radius: 999px;
}
.chart-wrap { position: relative; width: 100%; }
.chart-empty {
  display: flex; align-items: center; justify-content: center;
  height: 140px;
  font-size: 12px; color: var(--ink-muted, #6b7280);
}

.donut-wrapper {
  position: relative;
  height: 150px;
}
.donut-center {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
}
.donut-pct       { display: block; font-size: 22px; font-weight: 800; color: #1D9E75; line-height: 1; }
.donut-pct-label { display: block; font-size: 10px; color: var(--ink-muted, #6b7280); margin-top: 3px; }

.donut-legend {
  display: flex; gap: 14px; flex-wrap: wrap;
  margin-top: 10px;
  font-size: 11px; color: var(--ink-muted, #6b7280);
}
.leg     { display: flex; align-items: center; gap: 5px; }
.leg-sq  { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

/* ── Activity ─────────────────────────────────────────── */
.activity-scroll {
  max-height: 280px;
  overflow-y: auto;
  overscroll-behavior: contain;
}
.activity-scroll::-webkit-scrollbar       { width: 4px; }
.activity-scroll::-webkit-scrollbar-track { background: transparent; }
.activity-scroll::-webkit-scrollbar-thumb { background: var(--border, #e5e7eb); border-radius: 99px; }
.activity-scroll::-webkit-scrollbar-thumb:hover { background: #c8ccd2; }

.activity-row {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 18px;
  border-bottom: 1px solid var(--border, #e5e7eb);
}
.activity-row:last-child { border-bottom: none; }
.activity-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.activity-body { flex: 1; min-width: 0; }
.activity-body strong { display: block; font-size: 13px; font-weight: 600; }
.activity-body span   { display: block; font-size: 11px; color: var(--ink-muted, #6b7280); margin-top: 2px; }

/* ── Panel ────────────────────────────────────────────── */
.panel { background: white; border: 1px solid var(--border, #e5e7eb); border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
.panel-head {
  padding: 14px 18px;
  border-bottom: 1px solid var(--border, #e5e7eb);
  display: flex; align-items: center; justify-content: space-between;
}
.panel-head h2 { font-size: 14px; font-weight: 700; margin: 0; }
.panel-count { font-size: 11px; color: var(--ink-muted, #6b7280); background: var(--surface, #f3f5f8); padding: 2px 8px; border-radius: 999px; }

/* ── Loan rows ────────────────────────────────────────── */
.loan-row {
  display: flex; align-items: center; gap: 14px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--border, #e5e7eb);
}
.loan-row:last-child { border-bottom: none; }
.loan-type-pill {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 800;
  flex-shrink: 0;
}
.loan-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.loan-title { display: flex; align-items: center; gap: 8px; }
.loan-title strong { font-size: 13px; font-weight: 700; }
.loan-meta { display: flex; gap: 8px; font-size: 12px; color: var(--ink-muted, #6b7280); }
.loan-meta .mono { font-family: var(--font-mono, monospace); font-size: 11px; }
.loan-progress-row { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
.loan-progress-bar { flex: 1; height: 5px; background: #e5e7eb; border-radius: 99px; overflow: hidden; max-width: 180px; }
.loan-progress-fill { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
.loan-progress-pct { font-size: 10px; color: var(--ink-muted, #6b7280); white-space: nowrap; }
.loan-amounts { display: flex; flex-direction: column; gap: 6px; text-align: right; flex-shrink: 0; }
.amount-label { display: block; font-size: 10px; color: var(--ink-muted, #6b7280); text-transform: uppercase; letter-spacing: 0.3px; }
.amount-value { display: block; font-size: 13px; font-weight: 700; font-family: var(--font-mono, monospace); }

/* ── Table ────────────────────────────────────────────── */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead th {
  padding: 10px 16px;
  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
  color: var(--ink-muted, #6b7280);
  background: #f9fafb;
  text-align: left;
  border-bottom: 1px solid var(--border, #e5e7eb);
}
thead th.right { text-align: right; }
tbody td { padding: 11px 16px; border-bottom: 1px solid var(--border, #e5e7eb); }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #fafafa; }
.right     { text-align: right; }
.mono      { font-family: var(--font-mono, monospace); font-size: 12px; }
.bold      { font-weight: 700; }
.muted-text{ font-size: 11px; color: var(--ink-muted, #6b7280); max-width: 200px; }

/* ── Share Capital summary ────────────────────────────── */
.sc-summary-bar {
  background: white;
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 12px;
  padding: 16px 24px;
  display: flex; align-items: center; gap: 0;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.sc-sum-item { flex: 1; text-align: center; }
.sc-sum-item span   { display: block; font-size: 11px; color: var(--ink-muted, #6b7280); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; }
.sc-sum-item strong { font-size: 18px; font-weight: 700; }
.sc-sum-divider { width: 1px; background: var(--border, #e5e7eb); height: 40px; margin: 0 8px; }

/* ── Beneficiaries ────────────────────────────────────── */
.beneficiary-row {
  display: flex; align-items: center; gap: 14px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--border, #e5e7eb);
}
.beneficiary-row:last-child { border-bottom: none; }
.ben-avatar {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 800;
  flex-shrink: 0;
}
.ben-body { flex: 1; min-width: 0; }
.ben-name { font-size: 13px; font-weight: 700; }
.ben-meta { font-size: 12px; color: var(--ink-muted, #6b7280); margin-top: 2px; }
.ben-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
.allocation-badge { text-align: right; }
.allocation-pct   { display: block; font-size: 18px; font-weight: 800; color: var(--ink, #111); line-height: 1; }
.allocation-label { display: block; font-size: 10px; color: var(--ink-muted, #6b7280); text-transform: uppercase; letter-spacing: 0.3px; }

/* ── Profile ──────────────────────────────────────────── */
.profile-hero {
  padding: 24px 24px 20px;
  display: flex; align-items: center; gap: 18px;
  border-bottom: 1px solid var(--border, #e5e7eb);
  background: linear-gradient(135deg, #faf9ff 0%, #f0f4ff 100%);
}
.profile-avatar-lg {
  width: 64px; height: 64px; border-radius: 16px;
  background: var(--crs-red, #8b1a1a);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; font-weight: 800; color: white;
  flex-shrink: 0;
}
.profile-hero-info { flex: 1; }
.profile-name { font-size: 20px; font-weight: 800; color: var(--ink, #111); }
.profile-sub  { font-size: 13px; color: var(--ink-muted, #6b7280); margin: 4px 0 8px; }
.profile-tags { display: flex; gap: 6px; flex-wrap: wrap; }

.profile-grid { display: grid; grid-template-columns: repeat(2, 1fr); }
.profile-item {
  padding: 13px 18px;
  border-bottom: 1px solid var(--border, #e5e7eb);
  display: flex; flex-direction: column; gap: 3px;
}
.profile-item:nth-child(odd)  { border-right: 1px solid var(--border, #e5e7eb); }
.profile-item:nth-last-child(-n+2) { border-bottom: none; }
.profile-item span   { font-size: 10px; color: var(--ink-muted, #6b7280); font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
.profile-item strong { font-size: 13px; font-weight: 600; }

/* ── Pills ────────────────────────────────────────────── */
.pill {
  display: inline-block;
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 11px; font-weight: 700;
  white-space: nowrap;
}
.pill-active  { background: #dcfce7; color: #166534; }
.pill-amber   { background: #fef3c7; color: #92400e; }
.pill-info    { background: #dbeafe; color: #1e40af; }
.pill-purple  { background: #f3e8ff; color: #6b21a8; }
.pill-danger  { background: #fee2e2; color: #991b1b; }
.pill-muted   { background: #f1f5f9; color: #475569; }

.empty-row { padding: 24px 18px; color: var(--ink-muted, #6b7280); font-size: 13px; text-align: center; }

/* ── Pagination ───────────────────────────────────────────── */
.pager {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  border-top: 1px solid var(--border, #e5e7eb);
  background: #f9fafb;
}
.pager-info { font-size: 12px; color: var(--ink-muted, #6b7280); }
.pager-controls { display: flex; gap: 4px; }
.pager-btn {
  min-width: 30px; height: 30px;
  padding: 0 6px;
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 6px;
  background: white;
  color: var(--ink, #111);
  font-size: 12px; font-weight: 500;
  cursor: pointer;
  transition: all 0.12s;
  display: flex; align-items: center; justify-content: center;
}
.pager-btn:hover:not(:disabled) { background: var(--crs-red, #8b1a1a); color: white; border-color: var(--crs-red, #8b1a1a); }
.pager-btn.active { background: var(--crs-red, #8b1a1a); color: white; border-color: var(--crs-red, #8b1a1a); }
.pager-btn:disabled { opacity: 0.35; cursor: not-allowed; }

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 1100px) {
  .charts-row { grid-template-columns: 1fr; }
}
@media (max-width: 900px) {
  .summary-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .portal-shell { flex-direction: column; }
  .portal-sidebar { width: 100%; min-width: unset; height: auto; }
  .portal-nav { flex-direction: row; overflow-x: auto; padding: 6px 8px; }
  .pnav-item { white-space: nowrap; border-radius: 6px; }
  .summary-grid { grid-template-columns: 1fr 1fr; }
  .profile-grid { grid-template-columns: 1fr; }
  .profile-item:nth-child(odd) { border-right: none; }
  .sc-summary-bar { flex-wrap: wrap; gap: 12px; }
}
</style>
