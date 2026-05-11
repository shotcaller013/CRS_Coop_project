<template>
  <div class="page-wrap">

    <!-- Header -->
    <div class="page-header">
      <div>
        <div class="page-title serif">Billing</div>
        <div class="page-sub">Generate and manage payroll deduction billing statements per company</div>
      </div>
      <Button icon="pi pi-plus" label="Generate bill"
        v-if="canCreate" @click="createVisible = true" />
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
      <div class="filter-group">
        <label class="filter-label">Company</label>
        <Dropdown v-model="store.filters.company_id" :options="companies"
          optionLabel="name" optionValue="id" placeholder="All companies"
          showClear style="width:200px" />
      </div>
      <div class="filter-group">
        <label class="filter-label">Status</label>
        <Dropdown v-model="store.filters.status" :options="statusOpts"
          optionLabel="label" optionValue="value" placeholder="All"
          showClear style="width:150px" />
      </div>
      <div class="filter-group">
        <label class="filter-label">From</label>
        <Calendar v-model="dateFrom" dateFormat="yy-mm-dd" :showIcon="true" style="width:145px" />
      </div>
      <div class="filter-group">
        <label class="filter-label">To</label>
        <Calendar v-model="dateTo" dateFormat="yy-mm-dd" :showIcon="true" style="width:145px" />
      </div>
      <Button label="Search" icon="pi pi-search" @click="load" :loading="store.loading" />
      <Button icon="pi pi-refresh" outlined severity="secondary" v-tooltip="'Reset'" @click="reset" />
    </div>

    <!-- Bills table -->
    <div class="page-content">
      <div v-if="store.loading && !store.bills.length" class="empty-state">
        <div class="spinner"></div>
      </div>

      <DataTable v-else :value="store.bills" class="crs-table" stripedRows
        :loading="store.loading" selectionMode="single" @row-click="openDetail">

        <template #empty>
          <div class="empty-state" style="padding:24px 0">
            <div class="empty-icon">◻</div>
            <div class="text-muted">No bills found. Generate the first one above.</div>
          </div>
        </template>

        <Column header="Bill no." style="width:150px">
          <template #body="{ data: b }">
            <span class="mono fw-500">{{ b.bill_no }}</span>
          </template>
        </Column>

        <Column field="company_name" header="Company" />

        <Column header="Period" style="width:200px">
          <template #body="{ data: b }">
            <span style="font-size:12px">{{ b.period_label }}</span>
          </template>
        </Column>

        <Column header="Items" style="width:70px">
          <template #body="{ data: b }">
            <span class="mono">{{ b.item_count }}</span>
          </template>
        </Column>

        <Column header="Total (₱)" style="width:130px">
          <template #body="{ data: b }">
            <span class="mono fw-500">{{ peso(b.total_amount) }}</span>
          </template>
        </Column>

        <Column header="Balance (₱)" style="width:130px">
          <template #body="{ data: b }">
            <span class="mono" :style="{ color: b.balance > 0 ? 'var(--red-500)' : 'var(--green-600)' }">
              {{ b.balance > 0 ? peso(b.balance) : '✓ Settled' }}
            </span>
          </template>
        </Column>

        <Column header="Status" style="width:130px">
          <template #body="{ data: b }">
            <Tag :value="b.status_label" :severity="statusSeverity(b.status)" />
          </template>
        </Column>

        <Column header="Created" style="width:120px">
          <template #body="{ data: b }">
            <span class="text-muted" style="font-size:11px">{{ b.created_at_human }}</span>
          </template>
        </Column>

      </DataTable>

      <Paginator v-if="store.pagination.lastPage > 1"
        :rows="store.pagination.perPage || 20"
        :totalRecords="store.pagination.total"
        :first="((store.pagination.currentPage||1) - 1) * (store.pagination.perPage||20)"
        @page="e => load(e.page + 1)" class="mt-2" />
    </div>

    <!-- Bill detail drawer -->
    <Sidebar v-model:visible="detailVisible" position="right"
      style="width:640px" :header="selected?.bill_no ?? 'Bill detail'">
      <div v-if="selected" class="detail-body">

        <!-- Status bar -->
        <div class="detail-status-bar">
          <Tag :value="selected.status_label" :severity="statusSeverity(selected.status)" style="font-size:13px" />
          <span class="text-muted" style="font-size:12px">{{ selected.period_label }}</span>
          <span class="text-muted" style="font-size:12px">{{ selected.company_name }}</span>
          <div style="margin-left:auto;display:flex;gap:6px">
            <!-- Print PDF -->
            <Button icon="pi pi-file-pdf" label="Print bill"
              outlined size="small" :loading="printing"
              @click="printPdf" />
            <!-- Issue -->
            <Button v-if="selected.status === 'DRAFT'"
              icon="pi pi-send" label="Issue to company"
              size="small" @click="doIssue" :loading="actioning" />
            <!-- Upload remittance -->
            <Button v-if="['ISSUED','PARTIAL'].includes(selected.status)"
              icon="pi pi-upload" label="Upload remittance"
              severity="success" size="small"
              @click="remittanceVisible = true" />
            <!-- Settle manually -->
            <Button v-if="['ISSUED','PARTIAL'].includes(selected.status)"
              icon="pi pi-check" label="Mark settled"
              severity="warning" outlined size="small"
              @click="doSettle" :loading="actioning" />
            <!-- Cancel -->
            <Button v-if="['DRAFT','ISSUED'].includes(selected.status) && canCancel"
              icon="pi pi-ban" label="Cancel"
              severity="danger" text size="small"
              @click="doCancel" />
          </div>
        </div>

        <!-- Financial summary -->
        <div class="detail-fin">
          <div class="fin-row">
            <span>Total billed</span>
            <span class="mono fw-500">{{ peso(selected.total_amount) }}</span>
          </div>
          <div class="fin-row">
            <span>Amount remitted</span>
            <span class="mono" style="color:var(--green-600)">{{ peso(selected.amount_remitted) }}</span>
          </div>
          <div class="fin-row fw-500" style="border-top:0.5px solid var(--surface-border);padding-top:6px;margin-top:4px">
            <span>Balance outstanding</span>
            <span class="mono" :style="{ color: selected.balance > 0 ? 'var(--red-500)' : 'var(--green-600)' }">
              {{ selected.balance > 0 ? peso(selected.balance) : '₱0.00 — Fully settled' }}
            </span>
          </div>
        </div>

        <!-- Line items -->
        <div class="detail-section-title">
          Line items ({{ selected.items?.length ?? 0 }} periods)
        </div>
        <DataTable :value="selected.items ?? []" class="crs-table" stripedRows style="font-size:12px">
          <Column field="member_name"  header="Member" />
          <Column field="loan_no"      header="Loan #" style="width:130px">
            <template #body="{ data: r }"><span class="mono">{{ r.loan_no }}</span></template>
          </Column>
          <Column field="period_no"    header="Period" style="width:65px">
            <template #body="{ data: r }"><span class="mono">{{ r.period_no }}</span></template>
          </Column>
          <Column field="due_date"     header="Due date" style="width:100px">
            <template #body="{ data: r }"><span class="mono" style="font-size:11px">{{ r.due_date }}</span></template>
          </Column>
          <Column header="Amount (₱)" style="width:110px">
            <template #body="{ data: r }">
              <span class="mono fw-500">{{ peso(r.amount_due) }}</span>
            </template>
          </Column>
          <Column header="Status" style="width:80px">
            <template #body="{ data: r }">
              <Tag :value="r.status" :severity="r.status === 'PAID' ? 'success' : 'info'" style="font-size:10px" />
            </template>
          </Column>
        </DataTable>

        <!-- Remittances -->
        <template v-if="selected.remittances?.length">
          <div class="detail-section-title" style="margin-top:14px">
            Remittance history
          </div>
          <div v-for="r in selected.remittances" :key="r.id" class="remit-row">
            <div>
              <div class="fw-500" style="font-size:12px">{{ peso(r.amount) }}</div>
              <div class="text-muted" style="font-size:11px">{{ r.remittance_date }} · O.R. {{ r.or_number || '—' }}</div>
            </div>
            <div class="text-muted" style="font-size:11px">{{ r.created_at_human }} · {{ r.posted_by_name }}</div>
            <Button v-if="r.has_file" icon="pi pi-download" text rounded size="small"
              v-tooltip.top="'Download remittance document'"
              @click="downloadRemittance(r)" />
          </div>
        </template>

        <div v-if="selected.notes" class="notes-box text-muted" style="margin-top:12px;font-size:12px">
          <strong>Notes:</strong> {{ selected.notes }}
        </div>

      </div>
    </Sidebar>

    <!-- Create dialog -->
    <BillForm v-model:visible="createVisible" @created="onCreated" />

    <!-- Remittance upload dialog -->
    <RemittanceForm v-model:visible="remittanceVisible"
      :bill="selected" @uploaded="onRemittanceUploaded" />

    <ConfirmDialog />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable    from 'primevue/datatable'
import Column       from 'primevue/column'
import Button       from 'primevue/button'
import Dropdown     from 'primevue/dropdown'
import Calendar     from 'primevue/calendar'
import Tag          from 'primevue/tag'
import Paginator    from 'primevue/paginator'
import Sidebar      from 'primevue/sidebar'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import BillForm       from '@/components/billing/BillForm.vue'
import RemittanceForm from '@/components/billing/RemittanceForm.vue'
import { useBillStore }  from '@/stores/bill.store'
import { billService }   from '@/services/bill.service'
import { useCurrency }   from '@/composables/useCurrency'
import { useToast }      from '@/composables/useToast'
import api from '@/services/api'

const store   = useBillStore()
const confirm = useConfirm()
const { formatCurrency } = useCurrency()
const toast   = useToast()
const peso    = (n) => formatCurrency(n ?? 0)

// ── Auth placeholders (replace with actual auth store) ──────
const canCreate = true  // hasAnyRole(['manager','super-admin'])
const canCancel = true  // hasRole('super-admin')

// ── Filters ──────────────────────────────────────────────────
const companies   = ref([])
const dateFrom    = computed({ get: () => store.filters.date_from ? new Date(store.filters.date_from) : null, set: v => { store.filters.date_from = v ? new Date(v).toISOString().slice(0,10) : null }})
const dateTo      = computed({ get: () => store.filters.date_to   ? new Date(store.filters.date_to)   : null, set: v => { store.filters.date_to   = v ? new Date(v).toISOString().slice(0,10) : null }})

const statusOpts = [
  { label: 'Draft',    value: 'DRAFT'     },
  { label: 'Issued',   value: 'ISSUED'    },
  { label: 'Partial',  value: 'PARTIAL'   },
  { label: 'Settled',  value: 'SETTLED'   },
  { label: 'Cancelled',value: 'CANCELLED' },
]

function statusSeverity(s) {
  return { DRAFT:'secondary', ISSUED:'info', PARTIAL:'warning', SETTLED:'success', CANCELLED:'danger' }[s] ?? 'secondary'
}

// ── State ─────────────────────────────────────────────────────
const createVisible    = ref(false)
const detailVisible    = ref(false)
const remittanceVisible= ref(false)
const selected         = ref(null)
const printing         = ref(false)
const actioning        = ref(false)

// ── Methods ───────────────────────────────────────────────────
async function load(page = 1) { await store.fetchBills(page) }
async function reset() { store.resetFilters(); await load() }

async function openDetail({ data: bill }) {
  selected.value   = await store.fetchOne(bill.id)
  detailVisible.value = true
}

async function onCreated(bill) {
  selected.value    = await store.fetchOne(bill.id)
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

async function doSettle() {
  confirm.require({
    message: `Mark bill ${selected.value.bill_no} as fully settled? Payment records will be created for all pending line items.`,
    header: 'Settle bill',
    icon: 'pi pi-check-circle',
    acceptLabel: 'Settle',
    rejectLabel: 'Cancel',
    accept: async () => {
      actioning.value = true
      try {
        const res = await store.settle(selected.value.id)
        selected.value = res.data
        toast.success(res.message)
      } catch (e) { toast.error(e?.response?.data?.message || 'Could not settle.') }
      finally { actioning.value = false }
    }
  })
}

async function doCancel() {
  confirm.require({
    message: `Cancel bill ${selected.value.bill_no}? BILLED periods will revert to PENDING.`,
    header: 'Cancel bill',
    icon: 'pi pi-ban',
    acceptLabel: 'Cancel bill',
    acceptClass: 'p-button-danger',
    rejectLabel: 'Keep',
    accept: async () => {
      try {
        await store.cancel(selected.value.id)
        detailVisible.value = false
        toast.success('Bill cancelled.')
      } catch (e) { toast.error(e?.response?.data?.message || 'Could not cancel.') }
    }
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
  companies.value = res.data.data
  await load()
})
</script>

<style scoped>
.page-wrap    { display:flex;flex-direction:column;height:100%;overflow:hidden; }
.page-header  { display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--surface-border);flex-shrink:0; }
.page-title   { font-size:22px; }
.page-sub     { font-size:12px;color:var(--text-color-secondary);margin-top:2px; }
.filter-bar   { display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;padding:12px 20px;border-bottom:0.5px solid var(--surface-border);background:var(--surface-ground);flex-shrink:0; }
.filter-group { display:flex;flex-direction:column;gap:4px; }
.filter-label { font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-color-secondary); }
.page-content { flex:1;overflow-y:auto;padding:12px 20px; }

/* Detail drawer */
.detail-body  { display:flex;flex-direction:column;gap:12px;padding:4px 0; }
.detail-status-bar { display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.detail-fin   { display:flex;flex-direction:column;gap:4px;background:var(--surface-ground);border-radius:8px;padding:12px 14px;font-size:13px; }
.fin-row      { display:flex;justify-content:space-between; }
.detail-section-title { font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-color-secondary); }
.remit-row    { display:flex;align-items:center;gap:10px;justify-content:space-between;padding:8px 10px;background:var(--surface-ground);border-radius:6px;margin-bottom:4px; }
.notes-box    { padding:8px 10px;background:var(--surface-ground);border-radius:6px; }
</style>
