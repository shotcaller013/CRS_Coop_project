<template>
  <!--
    MobileDataCard — renders a key-value card instead of a table row on mobile.

    Usage in any list page:
    <template v-if="isMobile">
      <MobileDataCard
        v-for="item in items" :key="item.id"
        :fields="[
          { label: 'Loan no.',  value: item.loan_no,   mono: true },
          { label: 'Member',    value: item.member_name },
          { label: 'Amount',    value: peso(item.amount), bold: true },
          { label: 'Status',    slot: 'status' },
        ]"
        @click="$router.push(`/loans/${item.id}`)"
      >
        <template #status>
          <StatusBadge :status="item.status" />
        </template>
      </MobileDataCard>
    </template>
    <DataTable v-else .../>
  -->
  <div class="mobile-card" @click="emit('click')" :class="{ clickable: hasClickListener }">
    <div
      v-for="field in fields"
      :key="field.label"
      class="card-row"
      :class="{ 'card-row-full': field.full }"
    >
      <span class="row-label">{{ field.label }}</span>
      <span v-if="!$slots[field.slot]"
        class="row-value"
        :class="{ mono: field.mono, bold: field.bold }">
        {{ field.value ?? '—' }}
      </span>
      <span v-else class="row-value">
        <slot :name="field.slot" />
      </span>
    </div>

    <!-- Optional action slot (buttons) -->
    <div v-if="$slots.actions" class="card-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue'

const props = defineProps({
  fields: { type: Array, required: true },
  // Each field: { label, value?, slot?, mono?, bold?, full? }
})
const emit   = defineEmits(['click'])
const slots  = useSlots()
const hasClickListener = computed(() => !!emit)
</script>

<style scoped>
.mobile-card {
  background: var(--surface-card);
  border: 0.5px solid var(--surface-border);
  border-radius: var(--border-radius-lg);
  padding: 12px 14px;
  margin-bottom: 8px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px 12px;
}
.mobile-card.clickable { cursor: pointer; transition: background .1s; }
.mobile-card.clickable:hover { background: var(--surface-hover); }

.card-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.card-row-full { grid-column: 1 / -1; }

.row-label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: .4px;
  color: var(--text-color-secondary);
}
.row-value {
  font-size: 13px;
  color: var(--text-color);
}
.row-value.mono { font-family: var(--font-mono); font-size: 12px; }
.row-value.bold { font-weight: 500; }

.card-actions {
  grid-column: 1 / -1;
  display: flex;
  gap: 6px;
  justify-content: flex-end;
  margin-top: 4px;
  padding-top: 8px;
  border-top: 0.5px solid var(--surface-border);
}
</style>
