<template>
  <div class="page-wrap">
    <header class="topbar">
      <span class="topbar-page">Share Capital</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">Member share capital ledger and transactions</span>
    </header>

    <div class="page-content">
      <!-- Left: Member list -->
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

      <!-- Right: Ledger + Post transaction -->
      <div class="ledger-area">
        <template v-if="selectedMember">
          <!-- Header row -->
          <div class="ledger-header">
            <div>
              <div class="lh-name">{{ selectedMember.first_name }} {{ selectedMember.last_name }}</div>
              <div class="lh-meta">{{ selectedMember.member_no }} · {{ selectedMember.company }}</div>
            </div>
            <div class="lh-right">
              <div class="balance-block">
                <span class="balance-label">Current Balance</span>
                <span class="balance-value">{{ peso(summary?.balance ?? 0) }}</span>
              </div>
              <a :href="pdfUrl" target="_blank" rel="noopener" class="pdf-btn">↓ PDF Ledger</a>
            </div>
          </div>

          <!-- Post transaction form -->
          <div class="post-form-card">
            <div class="card-head">Post Transaction</div>
            <div class="post-form">
              <div class="field">
                <label>Type</label>
                <select v-model="txForm.type">
                  <option value="Deposit">Deposit</option>
                  <option value="Withdrawal">Withdrawal</option>
                  <option value="Dividend">Dividend</option>
                  <option value="Adjustment">Adjustment</option>
                  <option value="Opening">Opening Balance</option>
                </select>
              </div>
              <div class="field">
                <label>Amount (₱)</label>
                <input v-model.number="txForm.amount" type="number" min="0.01" step="0.01" placeholder="0.00" />
              </div>
              <div class="field">
                <label>Date</label>
                <input v-model="txForm.transaction_date" type="date" />
              </div>
              <div class="field field-wide">
                <label>Remarks</label>
                <input v-model="txForm.remarks" type="text" placeholder="Optional remarks" />
              </div>
              <button class="post-btn" :disabled="!canPost || posting" @click="postTransaction">
                {{ posting ? 'Posting…' : 'Post' }}
              </button>
            </div>
          </div>

          <!-- Ledger table -->
          <div class="ledger-card">
            <div class="card-head">Transaction History</div>
            <div v-if="ledgerLoading" class="loading-state"><div class="spinner"></div></div>
            <div v-else-if="!ledger.length" class="empty-row">No transactions found for this member.</div>
            <template v-else>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Type</th>
                    <th class="text-right">Amount (₱)</th>
                    <th class="text-right">Balance (₱)</th>
                    <th>Remarks</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="tx in ledger" :key="tx.id" :class="tx.voided_at ? 'row-voided' : ''">
                    <td class="mono" style="font-size:12px">{{ formatDate(tx.transaction_date || tx.date) }}</td>
                    <td class="mono" style="font-size:11px">{{ tx.reference_no || tx.reference || '—' }}</td>
                    <td>
                      <span :class="['type-badge', typeClass(tx.type)]">{{ tx.type }}</span>
                    </td>
                    <td class="text-right mono" style="font-weight:600">{{ peso(tx.amount) }}</td>
                    <td class="text-right mono">{{ peso(tx.running_balance ?? tx.balance) }}</td>
                    <td style="font-size:12px;color:var(--ink-muted)">{{ tx.remarks || '—' }}</td>
                    <td>
                      <button
                        v-if="!tx.voided_at"
                        class="void-btn"
                        @click="voidTransaction(tx)"
                      >Void</button>
                      <span v-else class="voided-tag">VOIDED</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </template>
          </div>
        </template>

        <div v-else class="ledger-empty">
          <div class="empty-icon">◈</div>
          <div class="empty-title">Select a member</div>
          <div class="empty-sub">Choose a member from the left panel to view their share capital ledger.</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { memberService }        from '@/services/member.service'
import { shareCapitalService }  from '@/services/share-capital.service'
import { useCurrency }          from '@/composables/useCurrency'
import { useDate }              from '@/composables/useDate'
import { useToast }             from '@/composables/useToast'

const { formatCurrency } = useCurrency()
const { formatDate }     = useDate()
const { success, error } = useToast()

const members        = ref([])
const ledger         = ref([])
const summary        = ref(null)
const selectedMember = ref(null)
const memberSearch   = ref('')
const membersLoading = ref(false)
const ledgerLoading  = ref(false)
const posting        = ref(false)

const txForm = ref({ type: 'Deposit', amount: '', transaction_date: today(), remarks: '' })

function today() {
  return new Date().toISOString().split('T')[0]
}

const peso = (n) => formatCurrency(n)

const filteredMembers = computed(() => {
  const q = memberSearch.value.toLowerCase()
  if (!q) return members.value
  return members.value.filter(m =>
    (m.first_name + ' ' + m.last_name).toLowerCase().includes(q) ||
    (m.member_no || '').toLowerCase().includes(q)
  )
})

const canPost = computed(() =>
  txForm.value.amount > 0 && txForm.value.transaction_date
)

const pdfUrl = computed(() =>
  selectedMember.value
    ? shareCapitalService.pdfUrl(selectedMember.value.id)
    : '#'
)

function typeClass(t) {
  if (t === 'Deposit' || t === 'Dividend' || t === 'Opening') return 'type-credit'
  if (t === 'Withdrawal')  return 'type-debit'
  return 'type-adjust'
}

async function selectMember(m) {
  selectedMember.value = m
  txForm.value = { type: 'Deposit', amount: '', transaction_date: today(), remarks: '' }
  await loadLedger()
}

async function loadLedger() {
  ledgerLoading.value = true
  try {
    const [ledgerRes, summaryRes] = await Promise.all([
      shareCapitalService.list(selectedMember.value.id),
      shareCapitalService.summary(selectedMember.value.id).catch(() => null),
    ])
    ledger.value  = ledgerRes.data?.data ?? ledgerRes.data ?? []
    summary.value = summaryRes?.data?.data ?? summaryRes?.data ?? null
  } catch {
    error('Failed to load share capital ledger.')
  } finally {
    ledgerLoading.value = false
  }
}

async function postTransaction() {
  posting.value = true
  try {
    await shareCapitalService.store(selectedMember.value.id, {
      type:             txForm.value.type,
      amount:           txForm.value.amount,
      transaction_date: txForm.value.transaction_date,
      remarks:          txForm.value.remarks,
    })
    success('Transaction posted.')
    txForm.value = { type: 'Deposit', amount: '', transaction_date: today(), remarks: '' }
    await loadLedger()
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to post transaction.')
  } finally {
    posting.value = false
  }
}

async function voidTransaction(tx) {
  if (!confirm(`Void transaction of ${peso(tx.amount)} (${tx.type})? This cannot be undone.`)) return
  try {
    await shareCapitalService.destroy(tx.id)
    success('Transaction voided.')
    await loadLedger()
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to void transaction.')
  }
}

onMounted(async () => {
  membersLoading.value = true
  try {
    const res    = await memberService.index({ per_page: 500, status: 'ACTIVE' })
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
.mi-name { font-size: 13px; font-weight: 600; color: var(--ink); }
.mi-meta { font-size: 11px; color: var(--ink-muted); margin-top: 2px; font-family: var(--font-mono); }

/* Ledger area */
.ledger-area { overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }

.ledger-header {
  display: flex; justify-content: space-between; align-items: flex-start;
  background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px 18px;
}
.lh-name  { font-size: 16px; font-weight: 700; }
.lh-meta  { font-size: 12px; color: var(--ink-muted); margin-top: 3px; }
.lh-right { display: flex; align-items: center; gap: 16px; }
.balance-block { display: flex; flex-direction: column; align-items: flex-end; }
.balance-label { font-size: 11px; color: var(--ink-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.balance-value { font-size: 20px; font-weight: 700; font-family: var(--font-mono); }
.pdf-btn {
  background: var(--crs-red); color: white; text-decoration: none;
  border-radius: 6px; padding: 7px 14px; font-size: 12px; font-weight: 600;
  white-space: nowrap;
}

/* Post form */
.post-form-card { background: white; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.card-head { padding: 12px 18px; border-bottom: 1px solid var(--border); font-size: 13px; font-weight: 700; }
.post-form {
  padding: 14px 18px;
  display: grid; grid-template-columns: 1fr 1fr 1fr 1.5fr auto;
  gap: 10px; align-items: end;
}
.field { display: flex; flex-direction: column; gap: 4px; }
.field-wide { grid-column: span 1; }
.field label { font-size: 11px; font-weight: 600; color: var(--ink-muted); text-transform: uppercase; letter-spacing: .4px; }
.field select, .field input {
  border: 1px solid var(--border); border-radius: 6px;
  padding: 7px 10px; font-size: 13px; outline: none; background: white;
}
.field select:focus, .field input:focus { border-color: var(--crs-red); }
.post-btn {
  background: var(--crs-red); color: white; border: none;
  border-radius: 7px; padding: 9px 18px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: opacity .15s; white-space: nowrap; height: 38px;
}
.post-btn:disabled { opacity: .5; cursor: not-allowed; }
.post-btn:not(:disabled):hover { opacity: .88; }

/* Ledger card */
.ledger-card { background: white; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th {
  padding: 9px 12px; text-align: left; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .4px; color: var(--ink-muted);
  background: var(--surface); border-bottom: 1px solid var(--border);
}
.data-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
.data-table tr:last-child td { border-bottom: none; }
.row-voided td { opacity: .45; text-decoration: line-through; }
.text-right { text-align: right; }

.type-badge { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 99px; }
.type-credit  { background: #dcfce7; color: #166534; }
.type-debit   { background: #fee2e2; color: #991b1b; }
.type-adjust  { background: #fef3c7; color: #92400e; }

.void-btn {
  background: none; border: 1px solid #fca5a5; border-radius: 5px;
  padding: 3px 9px; font-size: 11px; font-weight: 600; color: #991b1b;
  cursor: pointer; transition: background .1s;
}
.void-btn:hover { background: #fee2e2; }
.voided-tag { font-size: 10px; font-weight: 700; color: #991b1b; }

.empty-row { padding: 24px 18px; font-size: 13px; color: var(--ink-muted); text-align: center; }
.loading-state { display: flex; justify-content: center; padding: 40px; }
.spinner { width: 24px; height: 24px; border: 3px solid var(--border); border-top-color: var(--crs-red); border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.ledger-empty {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 8px;
}
.empty-icon  { font-size: 36px; color: var(--ink-muted); }
.empty-title { font-size: 15px; font-weight: 600; }
.empty-sub   { font-size: 13px; color: var(--ink-muted); text-align: center; }

.mono { font-family: var(--font-mono); }

@media (max-width: 900px) {
  .page-content { grid-template-columns: 1fr; }
  .post-form { grid-template-columns: 1fr 1fr; }
}
</style>
