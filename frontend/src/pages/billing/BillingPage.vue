<template>
  <div class="page-wrap">

    <!-- Header -->
    <div class="page-header">
      <div>
        <div class="page-title serif">Billing</div>
        <div class="page-sub">Generate and manage payroll deduction billing statements per company</div>
      </div>
      <button v-if="canCreate" class="btn btn-primary" @click="createVisible = true">
        + Generate bill
      </button>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
      <div class="filter-group">
        <label class="filter-label">Company</label>
        <select v-model="store.filters.company_id" class="form-select" style="width:200px">
          <option value="">All companies</option>
          <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Status</label>
        <select v-model="store.filters.status" class="form-select" style="width:150px">
          <option value="">All</option>
          <option v-for="o in statusOpts" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">From</label>
        <input v-model="store.filters.date_from" type="date" class="form-input" style="width:145px" />
      </div>
      <div class="filter-group">
        <label class="filter-label">To</label>
        <input v-model="store.filters.date_to" type="date" class="form-input" style="width:145px" />
      </div>
      <button class="btn btn-primary" :disabled="store.loading" @click="load">
        <span v-if="store.loading" class="spinner-sm"></span>
        Search
      </button>
      <button class="btn btn-secondary" title="Reset filters" @click="reset">↺</button>
    </div>

    <!-- Bills table -->
    <div class="page-content">
      <div v-if="store.loading && !store.bills.length" class="empty-state">
        <div class="spinner"></div>
      </div>

      <template v-else>
        <div v-if="!store.bills.length" class="empty-state">
          <div class="empty-icon">◻</div>
          <div class="text-muted">No bills found. Generate the first one above.</div>
        </div>

        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Bill no.</th>
              <th>Company</th>
              <th>Period</th>
              <th>Items</th>
              <th>Total (₱)</th>
              <th>Balance (₱)</th>
              <th>Status</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in store.bills" :key="b.id" @click="openDetail(b)">
              <td><span class="mono fw-500">{{ b.bill_no }}</span></td>
              <td>{{ b.company_name }}</td>
              <td><span style="font-size:12px">{{ b.period_label }}</span></td>
              <td><span class="mono">{{ b.item_count }}</span></td>
              <td><span class="mono fw-500">{{ peso(b.total_amount) }}</span></td>
              <td>
                <span class="mono" :style="{ color: b.balance > 0 ? 'var(--red)' : 'var(--green)' }">
                  {{ b.balance > 0 ? peso(b.balance) : '✓ Settled' }}
                </span>
              </td>
              <td><span :class="['badge', statusBadge(b.status)]">{{ b.status_label }}</span></td>
              <td><span class="text-muted" style="font-size:11px">{{ b.created_at_human }}</span></td>
            </tr>
          </tbody>
        </table>

        <div v-if="store.pagination.lastPage > 1" class="pagination-bar">
          <button class="btn btn-secondary btn-sm"
            :disabled="store.pagination.currentPage <= 1"
            @click="load(store.pagination.currentPage - 1)">← Prev</button>
          <span class="pag-info">Page {{ store.pagination.currentPage }} of {{ store.pagination.lastPage }}</span>
          <button class="btn btn-secondary btn-sm"
            :disabled="store.pagination.currentPage >= store.pagination.lastPage"
            @click="load(store.pagination.currentPage + 1)">Next →</button>
        </div>
      </template>
    </div>

    <!-- Bill detail drawer -->
    <Teleport to="body">
      <div v-if="detailVisible" class="drawer-overlay" @click="detailVisible = false"></div>
      <div class="drawer" :class="{ 'drawer-open': detailVisible }">
        <div class="drawer-header">
          <span class="drawer-title mono">{{ selected?.bill_no ?? 'Bill detail' }}</span>
          <button class="close-btn" @click="detailVisible = false">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div v-if="selected" class="drawer-body">

          <!-- Status bar -->
          <div class="detail-status-bar">
            <span :class="['badge', statusBadge(selected.status)]">{{ selected.status_label }}</span>
            <span class="text-muted" style="font-size:12px">{{ selected.period_label }}</span>
            <span class="text-muted" style="font-size:12px">{{ selected.company_name }}</span>
            <div class="detail-actions">
              <button class="btn btn-secondary btn-sm" :disabled="printing" @click="printPdf">
                <span v-if="printing" class="spinner-sm spinner-dark"></span>
                PDF
              </button>
              <button v-if="selected.status === 'DRAFT'" class="btn btn-primary btn-sm"
                :disabled="actioning" @click="doIssue">
                <span v-if="actioning" class="spinner-sm"></span>
                Issue to company
              </button>
              <button v-if="['ISSUED','PARTIAL'].includes(selected.status)"
                class="btn btn-sm btn-green" @click="remittanceVisible = true">
                Upload remittance
              </button>
              <button v-if="['ISSUED','PARTIAL'].includes(selected.status)"
                class="btn btn-secondary btn-sm" :disabled="actioning" @click="doSettle">
                <span v-if="actioning" class="spinner-sm spinner-dark"></span>
                Mark settled
              </button>
              <button v-if="['DRAFT','ISSUED'].includes(selected.status) && canCancel"
                class="btn btn-sm btn-danger-outline" @click="doCancel">
                Cancel
              </button>
            </div>
          </div>

          <!-- Financial summary -->
          <div class="detail-fin">
            <div class="fin-row"><span>Total billed</span><span class="mono fw-500">{{ peso(selected.total_amount) }}</span></div>
            <div class="fin-row"><span>Amount remitted</span><span class="mono" style="color:var(--green)">{{ peso(selected.amount_remitted) }}</span></div>
            <div class="fin-row fw-500" style="border-top:0.5px solid var(--border);padding-top:6px;margin-top:4px">
              <span>Balance outstanding</span>
              <span class="mono" :style="{ color: selected.balance > 0 ? 'var(--red)' : 'var(--green)' }">
                {{ selected.balance > 0 ? peso(selected.balance) : '₱0.00 — Fully settled' }}
              </span>
            </div>
          </div>

          <!-- Line items -->
          <div class="detail-section-title">Line items ({{ selected.items?.length ?? 0 }} periods)</div>
          <table class="data-table" style="font-size:12px">
            <thead>
              <tr>
                <th>Member</th>
                <th>Loan #</th>
                <th>Period</th>
                <th>Due date</th>
                <th>Amount (₱)</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in (selected.items ?? [])" :key="r.id" style="cursor:default">
                <td>{{ r.member_name }}</td>
                <td><span class="mono">{{ r.loan_no }}</span></td>
                <td><span class="mono">{{ r.period_no }}</span></td>
                <td><span class="mono" style="font-size:11px">{{ r.due_date }}</span></td>
                <td><span class="mono fw-500">{{ peso(r.amount_due) }}</span></td>
                <td>
                  <span :class="['badge', r.status === 'PAID' ? 'badge-approved' : 'badge-active']"
                    style="font-size:10px">{{ r.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Remittances -->
          <template v-if="selected.remittances?.length">
            <div class="detail-section-title" style="margin-top:14px">Remittance history</div>
            <div v-for="r in selected.remittances" :key="r.id" class="remit-row">
              <div>
                <div class="fw-500" style="font-size:12px">{{ peso(r.amount) }}</div>
                <div class="text-muted" style="font-size:11px">{{ r.remittance_date }} · O.R. {{ r.or_number || '—' }}</div>
              </div>
              <div class="text-muted" style="font-size:11px">{{ r.created_at_human }} · {{ r.posted_by_name }}</div>
              <button v-if="r.has_file" class="btn btn-secondary btn-sm"
                title="Download remittance document" @click="downloadRemittance(r)">↓</button>
            </div>
          </template>

          <div v-if="selected.notes" class="notes-box text-muted" style="margin-top:12px;font-size:12px">
            <strong>Notes:</strong> {{ selected.notes }}
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Confirm modal -->
    <Teleport to="body">
      <div v-if="confirmState.visible" class="modal-overlay">
        <div class="modal" style="max-width:420px">
          <div class="modal-header">
            <span class="modal-title">{{ confirmState.header }}</span>
          </div>
          <div class="modal-body">
            <p style="font-size:13px;line-height:1.6;margin:0">{{ confirmState.message }}</p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="confirmState.visible = false">{{ confirmState.rejectLabel }}</button>
            <button :class="['btn', confirmState.acceptClass]" @click="confirmState.onAccept()">{{ confirmState.acceptLabel }}</button>
          </div>
        </div>
      </div>
    </Teleport>

    <BillForm v-model:visible="createVisible" @created="onCreated" />
    <RemittanceForm v-model:visible="remittanceVisible" :bill="selected" @uploaded="onRemittanceUploaded" />

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import BillForm       from '@/components/billing/BillForm.vue'
import RemittanceForm from '@/components/billing/RemittanceForm.vue'
import { useBillStore }  from '@/stores/bill.store'
import { billService }   from '@/services/bill.service'
import { useCurrency }   from '@/composables/useCurrency'
import { useToast }      from '@/composables/useToast'
import api from '@/services/api'

const store   = useBillStore()
const { formatCurrency } = useCurrency()
const toast   = useToast()
const peso    = (n) => formatCurrency(n ?? 0)

const canCreate = true
const canCancel = true

const companies = ref([])

const statusOpts = [
  { label: 'Draft',     value: 'DRAFT'     },
  { label: 'Issued',    value: 'ISSUED'    },
  { label: 'Partial',   value: 'PARTIAL'   },
  { label: 'Settled',   value: 'SETTLED'   },
  { label: 'Cancelled', value: 'CANCELLED' },
]

function statusBadge(s) {
  return { DRAFT: 'badge-draft', ISSUED: 'badge-active', PARTIAL: 'badge-pending',
           SETTLED: 'badge-approved', CANCELLED: 'badge-rejected' }[s] ?? 'badge-draft'
}

const createVisible     = ref(false)
const detailVisible     = ref(false)
const remittanceVisible = ref(false)
const selected          = ref(null)
const printing          = ref(false)
const actioning         = ref(false)

const confirmState = reactive({
  visible: false, header: '', message: '',
  acceptLabel: 'Confirm', rejectLabel: 'Cancel',
  acceptClass: 'btn-primary', onAccept: () => {},
})

function requireConfirm({ header, message, acceptLabel = 'Confirm', rejectLabel = 'Cancel', acceptClass = 'btn-primary', accept }) {
  Object.assign(confirmState, {
    visible: true, header, message, acceptLabel, rejectLabel, acceptClass,
    onAccept: () => { confirmState.visible = false; accept() },
  })
}

async function load(page = 1) { await store.fetchBills(page) }
async function reset() { store.resetFilters(); await load() }

async function openDetail(bill) {
  selected.value = await store.fetchOne(bill.id)
  detailVisible.value = true
}

async function onCreated(bill) {
  selected.value = await store.fetchOne(bill.id)
  detailVisible.value = true
}

async function doIssue() {
  actioning.value = true
  try {
    const res = await store.issue(selected.value.id)
    selected.value = res.data
    toast.success(res.message)
  } catch (e) { toast.error(e?.response?.data?.message || 'Could not issue bill.') }
  finally { actioning.value = false }
}

function doSettle() {
  requireConfirm({
    header: 'Settle bill',
    message: `Mark bill ${selected.value.bill_no} as fully settled? Payment records will be created for all pending line items.`,
    acceptLabel: 'Settle',
    accept: async () => {
      actioning.value = true
      try {
        const res = await store.settle(selected.value.id)
        selected.value = res.data
        toast.success(res.message)
      } catch (e) { toast.error(e?.response?.data?.message || 'Could not settle.') }
      finally { actioning.value = false }
    },
  })
}

function doCancel() {
  requireConfirm({
    header: 'Cancel bill',
    message: `Cancel bill ${selected.value.bill_no}? BILLED periods will revert to PENDING.`,
    acceptLabel: 'Cancel bill',
    rejectLabel: 'Keep',
    acceptClass: 'btn-danger',
    accept: async () => {
      try {
        await store.cancel(selected.value.id)
        detailVisible.value = false
        toast.success('Bill cancelled.')
      } catch (e) { toast.error(e?.response?.data?.message || 'Could not cancel.') }
    },
  })
}

async function onRemittanceUploaded(bill) {
  selected.value = await store.fetchOne(bill.id)
}

async function printPdf() {
  printing.value = true
  try { await billService.downloadPdf(selected.value.id, selected.value.bill_no) }
  catch { toast.error('Could not generate PDF.') }
  finally { printing.value = false }
}

async function downloadRemittance(r) {
  try {
    const res = await billService.downloadRemittanceFile(r.id)
    const url = URL.createObjectURL(new Blob([res.data]))
    const a   = document.createElement('a'); a.href = url; a.download = `remittance_${r.id}`; a.click()
    URL.revokeObjectURL(url)
  } catch { toast.error('File not found.') }
}

onMounted(async () => {
  const res = await api.get('/companies').catch(() => ({ data: { data: [] } }))
  companies.value = res.data?.data ?? []
  await load()
})
</script>

<style scoped>
.page-wrap   { display:flex; flex-direction:column; height:100%; overflow:hidden; }
.page-header { display:flex; align-items:center; justify-content:space-between;
               padding:18px 24px; border-bottom:1px solid var(--border); flex-shrink:0; }
.page-title  { font-size:22px; }
.page-sub    { font-size:12px; color:var(--ink-muted); margin-top:2px; }
.filter-bar  { display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap;
               padding:12px 20px; border-bottom:0.5px solid var(--border);
               background:var(--surface-2); flex-shrink:0; }
.filter-group { display:flex; flex-direction:column; gap:4px; }
.filter-label { font-size:10px; font-weight:600; text-transform:uppercase;
                letter-spacing:.5px; color:var(--ink-muted); }
.page-content { flex:1; overflow-y:auto; padding:12px 20px; }

.pagination-bar { display:flex; align-items:center; gap:8px; justify-content:center; padding:12px 0; }
.pag-info       { font-size:12px; color:var(--ink-muted); }

/* Drawer */
.drawer-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:900;
}
.drawer {
  position:fixed; top:0; right:0; bottom:0; width:640px; max-width:100vw;
  background:white; border-left:1px solid var(--border); z-index:901;
  display:flex; flex-direction:column;
  transform:translateX(100%); transition:transform .25s ease; overflow:hidden;
}
.drawer-open { transform:translateX(0); }
.drawer-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 18px; border-bottom:1px solid var(--border); flex-shrink:0;
}
.drawer-title { font-size:15px; font-weight:600; }
.drawer-body  { flex:1; overflow-y:auto; padding:16px 18px;
                display:flex; flex-direction:column; gap:12px; }

.detail-status-bar { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.detail-actions    { margin-left:auto; display:flex; gap:6px; flex-wrap:wrap; }
.detail-fin  { display:flex; flex-direction:column; gap:4px; background:var(--surface-2);
               border-radius:8px; padding:12px 14px; font-size:13px; }
.fin-row     { display:flex; justify-content:space-between; }
.detail-section-title { font-size:11px; font-weight:600; text-transform:uppercase;
                        letter-spacing:.4px; color:var(--ink-muted); }
.remit-row   { display:flex; align-items:center; gap:10px; justify-content:space-between;
               padding:8px 10px; background:var(--surface-2); border-radius:6px; margin-bottom:4px; }
.notes-box   { padding:8px 10px; background:var(--surface-2); border-radius:6px; }

.btn-green         { background:var(--green); border-color:var(--green); color:#fff; }
.btn-green:hover   { opacity:.9; }
.btn-danger        { background:var(--red); border-color:var(--red); color:#fff; }
.btn-danger:hover  { opacity:.9; }
.btn-danger-outline        { color:var(--red); border-color:var(--red); background:transparent; }
.btn-danger-outline:hover  { background:var(--red-pale); }

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
.spinner-sm.spinner-dark { border-color:rgba(0,0,0,.15); border-top-color:var(--ink-soft); }
@keyframes spin { to { transform:rotate(360deg); } }

@media (max-width: 768px) {
  .filter-bar { gap: 8px; }
  .filter-bar .form-select,
  .filter-bar .form-input { width: 100% !important; }
  .filter-bar .filter-group { flex: 1 1 calc(50% - 4px); }

  .detail-status-bar { flex-direction: column; align-items: flex-start; }
  .detail-actions    { margin-left: 0; width: 100%; flex-wrap: wrap; }
  .drawer { width: 100vw; }
}
</style>
