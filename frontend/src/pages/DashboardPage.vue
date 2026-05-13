<template>
  <div class="dashboard">

    <!-- Loading -->
    <div v-if="store.loading" class="loading-state">
      <div class="spinner"></div>
      <span class="text-muted" style="font-size:13px">Loading dashboard…</span>
    </div>

    <div v-else-if="store.error" class="loading-state">
      <span class="text-muted" style="font-size:13px">⚠ {{ store.error }}</span>
      <button class="btn btn-secondary btn-sm" style="margin-left:12px" @click="store.fetch()">Retry</button>
    </div>

    <template v-else-if="d">

      <!-- ── Stat cards ────────────────────────────────── -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Active loans</div>
          <div class="stat-val">{{ d.stats.active_loans }}</div>
          <div class="stat-sub">+{{ d.stats.new_loans_this_month }} this month</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total outstanding</div>
          <div class="stat-val sm">{{ peso(d.stats.total_outstanding) }}</div>
          <div class="stat-sub">across all active loans</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Collection rate</div>
          <div class="stat-val" :style="{ color: rateColor(d.stats.collection_rate) }">
            {{ d.stats.collection_rate }}%
          </div>
          <div class="stat-sub">
            <span :style="{ color: d.stats.collection_rate_delta >= 0 ? 'var(--green)' : 'var(--red)' }">
              {{ d.stats.collection_rate_delta >= 0 ? '+' : '' }}{{ d.stats.collection_rate_delta }}%
            </span>
            vs last month
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Overdue accounts</div>
          <div class="stat-val" :style="{ color: d.stats.overdue_count > 0 ? 'var(--red)' : 'var(--green)' }">
            {{ d.stats.overdue_count }}
          </div>
          <div class="stat-sub mono">{{ peso(d.stats.overdue_balance) }} outstanding</div>
        </div>
      </div>

      <!-- ── Row 2: Collections + Status ──────────────── -->
      <div class="row row-21">

        <!-- Collections bar chart -->
        <div class="card">
          <div class="card-title">Monthly collections</div>
          <div class="legend">
            <span class="leg"><span class="leg-sq" style="background:#378ADD"></span>Expected</span>
            <span class="leg"><span class="leg-sq" style="background:#1D9E75"></span>Collected</span>
          </div>
          <div class="chart-wrap" style="height:190px">
            <canvas ref="collectionsChart"
              role="img" aria-label="Bar chart showing expected vs collected loan payments over the last 6 months">
              Monthly collection data available.
            </canvas>
          </div>
        </div>

        <!-- Status donut -->
        <div class="card">
          <div class="card-title">Loan status</div>
          <div class="chart-wrap" style="height:160px">
            <canvas ref="statusChart"
              role="img" aria-label="Donut chart showing distribution of loans by status">
              Loan status distribution.
            </canvas>
          </div>
          <div class="legend" style="flex-wrap:wrap;justify-content:center;margin-top:8px">
            <span v-for="(item,i) in statusItems" :key="i" class="leg">
              <span class="leg-sq" :style="{ background: statusColors[i] }"></span>
              {{ item.status }} {{ item.count }}
            </span>
          </div>
        </div>

      </div>

      <!-- ── Row 3: Disbursements + Loan types ─────────── -->
      <div class="row row-2">

        <!-- Disbursements combo chart -->
        <div class="card">
          <div class="card-title">New loans disbursed — last 6 months</div>
          <div class="legend">
            <span class="leg"><span class="leg-sq" style="background:#7F77DD"></span>Count</span>
            <span class="leg"><span class="leg-sq" style="background:#D4537E;border:1px dashed #D4537E"></span>Amount (₱000s)</span>
          </div>
          <div class="chart-wrap" style="height:170px">
            <canvas ref="disbursementsChart"
              role="img" aria-label="Combined bar and line chart showing monthly loan count and total disbursed amount">
              Monthly disbursements data.
            </canvas>
          </div>
        </div>

        <!-- Loan type breakdown -->
        <div class="card">
          <div class="card-title">Outstanding by loan type</div>
          <div class="chart-wrap" :style="{ height: typeChartHeight + 'px' }">
            <canvas ref="typesChart"
              role="img" aria-label="Horizontal bar chart showing outstanding balance per loan type">
              Loan type balance data.
            </canvas>
          </div>
        </div>

      </div>

      <!-- ── Row 4: Aging + Share capital ──────────────── -->
      <div class="row row-21">

        <!-- Aging buckets -->
        <div class="card">
          <div class="card-title">Overdue aging</div>
          <div class="legend">
            <span v-for="(b,i) in d.aging" :key="i" class="leg">
              <span class="leg-sq" :style="{ background: agingColors[i] }"></span>
              {{ b.label }}
            </span>
          </div>
          <div class="chart-wrap" style="height:130px">
            <canvas ref="agingChart"
              role="img" aria-label="Bar chart showing overdue balance by aging bucket">
              Aging bucket data.
            </canvas>
          </div>
        </div>

        <!-- Share capital summary -->
        <div class="card" v-if="isManagerOrAbove">
          <div class="card-title">Share capital</div>
          <div class="sc-balance">
            <div class="stat-label">Total balance</div>
            <div class="stat-val sm" style="color:var(--green)">
              {{ peso(d.share_capital.total_balance) }}
            </div>
            <div class="stat-sub">{{ d.share_capital.member_count }} active members</div>
          </div>
          <div class="sc-rows">
            <div class="sc-row">
              <span>Credits this month</span>
              <span class="mono" style="color:var(--green)">+{{ peso(d.share_capital.month_credits) }}</span>
            </div>
            <div class="sc-row">
              <span>Debits this month</span>
              <span class="mono" style="color:var(--red)">−{{ peso(d.share_capital.month_debits) }}</span>
            </div>
            <div class="sc-row sc-row-total">
              <span>Net movement</span>
              <span class="mono" :style="{ color: d.share_capital.month_net >= 0 ? 'var(--green)' : 'var(--red)' }">
                {{ d.share_capital.month_net >= 0 ? '+' : '' }}{{ peso(d.share_capital.month_net) }}
              </span>
            </div>
          </div>
        </div>

      </div>

      <!-- ── Row 5: Activity feed + Top overdue ────────── -->
      <div class="row row-2">

        <!-- Activity feed -->
        <div class="card">
          <div class="card-title">Recent activity</div>
          <div v-if="!d.recent_activity.length" class="empty-feed text-muted">No recent activity.</div>
          <div v-for="item in d.recent_activity" :key="item.id" class="feed-item">
            <div class="feed-dot" :style="{ background: eventColor(item.event) }"></div>
            <div class="feed-body">
              <div class="feed-text">
                {{ item.auditable_type }}
                <span class="mono" style="font-size:11px">{{ item.auditable_label }}</span>
                — {{ item.event }}
              </div>
              <div class="feed-meta">{{ item.user_name }}</div>
            </div>
            <span class="feed-time text-muted">{{ item.created_at_human }}</span>
          </div>
        </div>

        <!-- Top overdue -->
        <div class="card">
          <div class="card-title">Top overdue accounts</div>
          <div v-if="!d.top_overdue.length" class="empty-feed text-muted">No overdue accounts.</div>
          <table v-else class="overdue-table">
            <thead>
              <tr>
                <th>Member</th>
                <th>Loan #</th>
                <th>Days</th>
                <th class="right">Balance</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in d.top_overdue" :key="row.loan_no"
                class="overdue-row" @click="$router.push(`/loans/${row.loan_id}`)">
                <td>{{ row.member_name }}</td>
                <td class="mono">{{ row.loan_no }}</td>
                <td>
                  <span :class="['overdue-badge', row.days_overdue > 60 ? 'danger' : 'warn']">
                    {{ row.days_overdue }}
                  </span>
                </td>
                <td class="right mono">{{ peso(row.balance) }}</td>
              </tr>
            </tbody>
          </table>
          <button class="view-all-btn" @click="$router.push('/reports/aging')">
            View full aging report ↗
          </button>
        </div>

      </div>

    </template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useDashboardStore } from '@/stores/dashboard.store'
import { useCurrency } from '@/composables/useCurrency'
import Chart from 'chart.js/auto'

const store = useDashboardStore()
const { formatCurrency } = useCurrency()
const d    = computed(() => store.data)
const peso = (n) => formatCurrency(n ?? 0)

// ── Role check ─────────────────────────────────────────────
// Adapt to your actual auth store
const isManagerOrAbove = computed(() => true) // replace with: hasAnyRole(['manager','super-admin'])

// ── Chart refs ──────────────────────────────────────────────
const collectionsChart  = ref(null)
const statusChart       = ref(null)
const disbursementsChart= ref(null)
const typesChart        = ref(null)
const agingChart        = ref(null)

let charts = {}

const statusColors = ['#1D9E75','#378ADD','#EF9F27','#888780','#E24B4A','#7F77DD']
const agingColors  = ['#1D9E75','#EF9F27','#E24B4A','#A32D2D']

const statusItems = computed(() => d.value?.loan_status ?? [])

const typeChartHeight = computed(() => {
  const n = d.value?.loan_types?.length ?? 4
  return Math.max(140, n * 36 + 60)
})

function rateColor(rate) {
  if (rate >= 90) return 'var(--green)'
  if (rate >= 75) return 'var(--amber)'
  return 'var(--red)'
}

function eventColor(event) {
  return {
    'created':  '#1D9E75',
    'updated':  '#378ADD',
    'deleted':  '#E24B4A',
    'restored': '#EF9F27',
  }[event] ?? '#888780'
}

// ── Build charts ────────────────────────────────────────────
function buildCharts() {
  if (!d.value) return
  Object.values(charts).forEach(c => c.destroy())
  charts = {}

  // 1. Collections
  if (collectionsChart.value) {
    const mc   = d.value.monthly_collections
    charts.col = new Chart(collectionsChart.value, {
      type: 'bar',
      data: {
        labels:   mc.map(r => r.month),
        datasets: [
          { label:'Expected', data: mc.map(r=>r.expected),  backgroundColor:'#378ADD' },
          { label:'Collected',data: mc.map(r=>r.collected), backgroundColor:'#1D9E75' },
        ]
      },
      options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{display:false}, tooltip:{callbacks:{label:ctx=>'₱'+ctx.raw.toLocaleString()}} },
        scales:{
          x:{ticks:{font:{size:11}},grid:{display:false}},
          y:{ticks:{callback:v=>'₱'+(v/1000).toFixed(0)+'k',font:{size:10}},grid:{color:'rgba(128,128,128,.1)'}},
        }
      }
    })
  }

  // 2. Status donut
  if (statusChart.value && statusItems.value.length) {
    charts.status = new Chart(statusChart.value, {
      type: 'doughnut',
      data: {
        labels:   statusItems.value.map(r=>r.status),
        datasets: [{ data: statusItems.value.map(r=>r.count), backgroundColor: statusColors, borderWidth:0 }]
      },
      options:{
        responsive:true, maintainAspectRatio:false, cutout:'68%',
        plugins:{ legend:{display:false} }
      }
    })
  }

  // 3. Disbursements
  if (disbursementsChart.value) {
    const dis   = d.value.disbursements
    charts.dis  = new Chart(disbursementsChart.value, {
      type: 'bar',
      data:{
        labels: dis.map(r=>r.month),
        datasets:[
          { type:'bar',  label:'Count',  data:dis.map(r=>r.count),            backgroundColor:'#7F77DD', yAxisID:'y' },
          { type:'line', label:'Amount', data:dis.map(r=>r.amount/1000),      borderColor:'#D4537E', borderDash:[4,3], pointRadius:3, backgroundColor:'transparent', yAxisID:'y2' }
        ]
      },
      options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{display:false} },
        scales:{
          x:{ticks:{font:{size:11}},grid:{display:false}},
          y:{position:'left', ticks:{font:{size:10},stepSize:1}, grid:{color:'rgba(128,128,128,.1)'}},
          y2:{position:'right', ticks:{callback:v=>'₱'+v+'k',font:{size:10}}, grid:{display:false}},
        }
      }
    })
  }

  // 4. Loan types (horizontal bar)
  if (typesChart.value && d.value.loan_types.length) {
    const lt   = d.value.loan_types
    charts.lt  = new Chart(typesChart.value, {
      type:'bar',
      data:{
        labels: lt.map(r=>r.label),
        datasets:[{ data: lt.map(r=>r.amount), backgroundColor: statusColors }]
      },
      options:{
        indexAxis:'y', responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}, tooltip:{callbacks:{label:ctx=>'₱'+ctx.raw.toLocaleString()}}},
        scales:{
          x:{ticks:{callback:v=>'₱'+(v/1000).toFixed(0)+'k',font:{size:10}},grid:{color:'rgba(128,128,128,.1)'}},
          y:{ticks:{font:{size:11}},grid:{display:false}},
        }
      }
    })
  }

  // 5. Aging
  if (agingChart.value && d.value.aging.length) {
    charts.aging = new Chart(agingChart.value, {
      type:'bar',
      data:{
        labels:   d.value.aging.map(b=>b.label),
        datasets: [{ data: d.value.aging.map(b=>b.balance), backgroundColor: agingColors }]
      },
      options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}, tooltip:{callbacks:{label:ctx=>'₱'+ctx.raw.toLocaleString()}}},
        scales:{
          x:{ticks:{font:{size:11}},grid:{display:false}},
          y:{ticks:{callback:v=>'₱'+(v/1000).toFixed(0)+'k',font:{size:10}},grid:{color:'rgba(128,128,128,.1)'}},
        }
      }
    })
  }
}

onMounted(async () => {
  await store.fetch()
  await nextTick()
  buildCharts()
})

watch(() => store.data, async () => {
  await nextTick()
  buildCharts()
})
</script>

<style scoped>
.dashboard   { padding:20px 24px; display:flex; flex-direction:column; gap:12px; overflow-y:auto; height:100%; }
.loading-state { display:flex; align-items:center; gap:10px; padding:40px 0; }

/* Stats */
.stats-row   { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
.stat-card   { background:var(--surface-2); border:0.5px solid var(--border); border-radius:8px; padding:12px 14px; }
.stat-label  { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--ink-muted); margin-bottom:4px; }
.stat-val    { font-size:24px; font-weight:500; }
.stat-val.sm { font-size:18px; }
.stat-sub    { font-size:11px; color:var(--ink-muted); margin-top:2px; }

/* Rows */
.row         { display:grid; gap:12px; }
.row-21      { grid-template-columns:2fr 1fr; }
.row-2       { grid-template-columns:1fr 1fr; }

/* Cards */
.card        { background:white; border:0.5px solid var(--border); border-radius:10px; padding:14px 16px; }
.card-title  { font-size:11px; font-weight:500; text-transform:uppercase; letter-spacing:.5px; color:var(--ink-muted); margin-bottom:10px; }

/* Legend */
.legend      { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:8px; font-size:12px; color:var(--ink-muted); }
.leg         { display:flex; align-items:center; gap:4px; }
.leg-sq      { width:10px; height:10px; border-radius:2px; flex-shrink:0; }

/* Chart */
.chart-wrap  { position:relative; width:100%; }

/* Share capital */
.sc-balance  { margin-bottom:10px; }
.sc-rows     { display:flex; flex-direction:column; gap:4px; font-size:12px; }
.sc-row      { display:flex; justify-content:space-between; }
.sc-row-total{ font-weight:500; border-top:0.5px solid var(--border); padding-top:5px; margin-top:2px; }

/* Feed */
.feed-item   { display:flex; gap:8px; align-items:flex-start; padding:6px 0; border-bottom:0.5px solid var(--border); font-size:12px; }
.feed-item:last-child { border-bottom:none; }
.feed-dot    { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:3px; }
.feed-body   { flex:1; min-width:0; }
.feed-text   { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.feed-meta   { font-size:11px; color:var(--ink-muted); }
.feed-time   { font-size:10px; white-space:nowrap; margin-left:4px; }
.empty-feed  { font-size:12px; padding:8px 0; }

/* Overdue table */
.overdue-table { width:100%; border-collapse:collapse; font-size:12px; }
.overdue-table th { text-align:left; padding:5px 8px; font-size:10px; font-weight:500; text-transform:uppercase; letter-spacing:.4px; color:var(--ink-muted); border-bottom:0.5px solid var(--border); }
.overdue-table td { padding:6px 8px; border-bottom:0.5px solid var(--border); }
.overdue-table tr:last-child td { border-bottom:none; }
.overdue-row  { cursor:pointer; transition:background .1s; }
.overdue-row:hover td { background:var(--surface-2); }
.overdue-badge { font-size:10px; font-weight:500; padding:2px 7px; border-radius:4px; }
.overdue-badge.danger { background:var(--red-pale); color:var(--red); }
.overdue-badge.warn   { background:var(--amber-pale); color:var(--amber); }
.right        { text-align:right; }
.view-all-btn { width:100%; margin-top:10px; font-size:12px; cursor:pointer; }

@media (max-width: 768px) {
  .dashboard  { padding: 12px; gap: 10px; }
  .stats-row  { grid-template-columns: 1fr 1fr; }
  .row-21, .row-2 { grid-template-columns: 1fr; }
  .overdue-table { min-width: 400px; }
}
@media (max-width: 480px) {
  .stats-row { grid-template-columns: 1fr; }
}
</style>
