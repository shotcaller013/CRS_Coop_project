<template>
  <div class="page-wrap">
    <header class="topbar">
      <span class="topbar-page">Loan Packet</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Generate and download loan document packets (PDF)</span>
    </header>

    <div class="page-content">
      <div class="packet-layout">
        <!-- Loan list -->
        <div class="loan-list-panel">
          <div class="panel-head">
            <input v-model="search" type="text" placeholder="Search loan or member…" class="search-input" />
          </div>
          <div class="loan-items">
            <div v-if="loading" class="panel-loading">
              <div class="spinner"></div>
            </div>
            <div v-else-if="!filteredLoans.length" class="panel-empty">No loans found.</div>
            <button
              v-for="loan in filteredLoans"
              :key="loan.id"
              :class="['loan-item', selected?.id === loan.id && 'loan-item-active']"
              @click="select(loan)"
            >
              <div class="loan-item-top">
                <span class="loan-no">{{ loan.loan_no }}</span>
                <span :class="['status-pill', statusClass(loan.status)]">{{ loan.status }}</span>
              </div>
              <div class="loan-item-name">{{ loan.member_name || loan.member?.full_name }}</div>
              <div class="loan-item-meta">{{ loan.loan_type_label || loan.loan_type?.name }} · {{ peso(loan.principal) }}</div>
            </button>
          </div>
        </div>

        <!-- Packet viewer -->
        <div class="packet-panel">
          <template v-if="selected">
            <div class="packet-head">
              <div>
                <div class="packet-title">{{ selected.loan_no }}</div>
                <div class="packet-sub">{{ selected.member_name || selected.member?.full_name }} · {{ selected.loan_type_label || selected.loan_type?.name }}</div>
              </div>
              <button class="download-btn" :disabled="downloading" @click="downloadPacket">
                {{ downloading ? 'Generating…' : '↓ Download PDF Packet' }}
              </button>
            </div>

            <div class="packet-info-grid">
              <div class="info-item">
                <span>Principal</span>
                <strong>{{ peso(selected.principal) }}</strong>
              </div>
              <div class="info-item">
                <span>Interest Rate</span>
                <strong>{{ selected.interest_rate }}% / month</strong>
              </div>
              <div class="info-item">
                <span>Term</span>
                <strong>{{ selected.term_months }} months</strong>
              </div>
              <div class="info-item">
                <span>Monthly Amortization</span>
                <strong>{{ peso(selected.monthly_amortization) }}</strong>
              </div>
              <div class="info-item">
                <span>Release Date</span>
                <strong>{{ formatDate(selected.release_date) }}</strong>
              </div>
              <div class="info-item">
                <span>Status</span>
                <strong>{{ selected.status }}</strong>
              </div>
            </div>

            <div class="packet-pages">
              <div class="pages-label">Packet includes:</div>
              <div class="pages-list">
                <div v-for="pg in packetPages" :key="pg" class="page-chip">{{ pg }}</div>
              </div>
            </div>

            <div class="packet-cta">
              <button class="download-btn-lg" :disabled="downloading" @click="downloadPacket">
                {{ downloading ? 'Generating PDF…' : '↓ Download Complete Loan Packet (PDF)' }}
              </button>
            </div>
          </template>

          <div v-else class="packet-empty">
            <div class="empty-icon">◻</div>
            <div class="empty-title">Select a loan to generate the packet</div>
            <div class="empty-sub">The PDF packet includes the application, promissory note, amortization schedule, and disclosure statement.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { loanService }  from '@/services/loan.service'
import { useCurrency }  from '@/composables/useCurrency'
import { useDate }      from '@/composables/useDate'
import { useToast }     from '@/composables/useToast'

const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()
const { error }          = useToast()

const loans       = ref([])
const selected    = ref(null)
const search      = ref('')
const loading     = ref(false)
const downloading = ref(false)

const packetPages = [
  'Page 1 — Loan Application Form',
  'Page 2 — Authority to Deduct',
  'Page 3 — Promissory Note',
  'Page 4 — Amortization Schedule',
  'Page 5 — Disclosure Statement',
]

const peso = (n) => formatCurrency(n)

const filteredLoans = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return loans.value
  return loans.value.filter(l =>
    (l.loan_no || '').toLowerCase().includes(q) ||
    (l.member_name || l.member?.full_name || '').toLowerCase().includes(q) ||
    (l.loan_type_label || l.loan_type?.name || '').toLowerCase().includes(q)
  )
})

function statusClass(s) {
  if (s === 'ACTIVE')   return 'pill-active'
  if (s === 'RELEASED') return 'pill-released'
  if (s === 'CLOSED')   return 'pill-closed'
  return 'pill-muted'
}

function select(loan) {
  selected.value = loan
}

async function downloadPacket() {
  if (!selected.value) return
  downloading.value = true
  try {
    const token = localStorage.getItem('crs_token')
    const res   = await fetch(`/api/v1/loans/${selected.value.id}/packet.pdf`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/pdf' },
    })
    if (!res.ok) throw new Error(`Server error ${res.status}`)
    const blob = await res.blob()
    const url  = URL.createObjectURL(blob)
    const a    = document.createElement('a')
    a.href     = url
    a.download = `${selected.value.loan_no}-packet.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    error(e?.message || 'Failed to download packet.')
  } finally {
    downloading.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const res     = await loanService.index({ per_page: 500, status: 'ACTIVE,RELEASED,CLOSED' })
    loans.value   = res.data?.data ?? res.data ?? []
  } catch (e) {
    error('Failed to load loans.')
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.page-wrap    { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.topbar {
  display: flex; align-items: center; gap: 8px;
  padding: 14px 24px; border-bottom: 1px solid var(--border);
  background: white; flex-shrink: 0;
}
.topbar-page { font-weight: 600; font-size: 14px; }
.topbar-sep  { color: var(--ink-muted); }
.topbar-sub  { font-size: 12px; color: var(--ink-muted); }

.page-content { flex: 1; overflow: hidden; display: flex; }

.packet-layout {
  display: grid;
  grid-template-columns: 300px 1fr;
  width: 100%;
  overflow: hidden;
}

/* Loan list */
.loan-list-panel {
  border-right: 1px solid var(--border);
  display: flex; flex-direction: column;
  overflow: hidden;
}
.panel-head { padding: 12px; border-bottom: 1px solid var(--border); }
.search-input {
  width: 100%; border: 1px solid var(--border); border-radius: 6px;
  padding: 7px 10px; font-size: 12px; outline: none;
  transition: border-color .15s;
}
.search-input:focus { border-color: var(--crs-red); }

.loan-items { flex: 1; overflow-y: auto; }
.panel-loading { display: flex; justify-content: center; padding: 40px; }
.panel-empty   { padding: 24px; font-size: 13px; color: var(--ink-muted); text-align: center; }

.loan-item {
  width: 100%; text-align: left; padding: 12px 14px;
  border: none; border-bottom: 1px solid var(--border);
  background: white; cursor: pointer;
  transition: background .1s;
}
.loan-item:hover      { background: var(--surface); }
.loan-item-active     { background: #fef2f2; border-left: 3px solid var(--crs-red); }
.loan-item-top        { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; }
.loan-no              { font-size: 12px; font-weight: 700; font-family: var(--font-mono); color: var(--ink); }
.loan-item-name       { font-size: 12px; font-weight: 600; color: var(--ink); }
.loan-item-meta       { font-size: 11px; color: var(--ink-muted); margin-top: 2px; }

.status-pill          { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px; }
.pill-active          { background: #dcfce7; color: #166534; }
.pill-released        { background: #dbeafe; color: #1e40af; }
.pill-closed          { background: #f1f5f9; color: #64748b; }
.pill-muted           { background: #f1f5f9; color: #64748b; }

/* Packet panel */
.packet-panel { overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 20px; }

.packet-head {
  display: flex; justify-content: space-between; align-items: flex-start;
  background: white; border: 1px solid var(--border); border-radius: 10px; padding: 18px;
}
.packet-title { font-size: 16px; font-weight: 700; }
.packet-sub   { font-size: 12px; color: var(--ink-muted); margin-top: 3px; }

.download-btn {
  background: var(--crs-red); color: white; border: none;
  border-radius: 7px; padding: 9px 16px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: opacity .15s; white-space: nowrap;
}
.download-btn:disabled { opacity: .5; cursor: not-allowed; }
.download-btn:not(:disabled):hover { opacity: .88; }

.packet-info-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
}
.info-item {
  background: white; border: 1px solid var(--border); border-radius: 8px;
  padding: 12px 14px; display: flex; flex-direction: column; gap: 3px;
}
.info-item span   { font-size: 11px; color: var(--ink-muted); text-transform: uppercase; letter-spacing: .4px; font-weight: 600; }
.info-item strong { font-size: 14px; font-weight: 700; }

.packet-pages {
  background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px 18px;
}
.pages-label { font-size: 12px; font-weight: 600; color: var(--ink-muted); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 10px; }
.pages-list  { display: flex; flex-direction: column; gap: 6px; }
.page-chip   { font-size: 13px; padding: 7px 12px; background: var(--surface); border-radius: 6px; color: var(--ink); }

.packet-cta { text-align: center; }
.download-btn-lg {
  background: var(--crs-red); color: white; border: none;
  border-radius: 8px; padding: 12px 28px; font-size: 14px; font-weight: 700;
  cursor: pointer; transition: opacity .15s;
}
.download-btn-lg:disabled { opacity: .5; cursor: not-allowed; }
.download-btn-lg:not(:disabled):hover { opacity: .88; }

.packet-empty {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 8px; padding: 60px;
}
.empty-icon  { font-size: 36px; color: var(--ink-muted); }
.empty-title { font-size: 15px; font-weight: 600; }
.empty-sub   { font-size: 13px; color: var(--ink-muted); text-align: center; max-width: 360px; }

.spinner { width: 24px; height: 24px; border: 3px solid var(--border); border-top-color: var(--crs-red); border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .packet-layout { grid-template-columns: 1fr; }
  .packet-info-grid { grid-template-columns: 1fr 1fr; }
}
</style>
