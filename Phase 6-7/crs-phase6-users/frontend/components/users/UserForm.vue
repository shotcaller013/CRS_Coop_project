<template>
  <Dialog
    :visible="visible"
    @update:visible="emit('update:visible', $event)"
    :header="isEdit ? 'Edit user' : 'Create user'"
    modal
    :style="{ width: '480px' }"
    :closable="!store.saving"
  >
    <div class="form-grid">

      <!-- Name -->
      <div class="field full">
        <label class="field-label">Full name <span class="req">*</span></label>
        <InputText v-model="form.name" :invalid="!!errors.name"
          placeholder="e.g. Angelica Rosales" style="width:100%" />
        <small class="error">{{ errors.name }}</small>
      </div>

      <!-- Email -->
      <div class="field full">
        <label class="field-label">Email address <span class="req">*</span></label>
        <InputText v-model="form.email" :invalid="!!errors.email"
          type="email" placeholder="user@example.com" style="width:100%" />
        <small class="error">{{ errors.email }}</small>
      </div>

      <!-- Role -->
      <div class="field full">
        <label class="field-label">Role <span class="req">*</span></label>
        <Dropdown
          v-model="form.role"
          :options="roleOptions"
          optionLabel="label"
          optionValue="value"
          :invalid="!!errors.role"
          style="width:100%"
        />
        <small class="error">{{ errors.role }}</small>
        <small class="hint" v-if="form.role">{{ roleDescriptions[form.role] }}</small>
      </div>

      <!-- Password (create only) -->
      <template v-if="!isEdit">
        <div class="field full">
          <label class="field-label">Temporary password <span class="req">*</span></label>
          <Password
            v-model="form.password"
            :invalid="!!errors.password"
            :feedback="true"
            toggleMask
            style="width:100%"
            inputStyle="width:100%"
            placeholder="Min 8 chars, mixed case + numbers"
          />
          <small class="error">{{ errors.password }}</small>
          <small class="hint">The user will be required to change this on first login.</small>
        </div>
      </template>

    </div>

    <template #footer>
      <Button label="Cancel" text @click="emit('update:visible', false)" :disabled="store.saving" />
      <Button
        :label="isEdit ? 'Save changes' : 'Create user'"
        icon="pi pi-check"
        @click="submit"
        :loading="store.saving"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import Dialog   from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Password from 'primevue/password'
import Button   from 'primevue/button'
import { useUserStore } from '@/stores/user.store'
import { useToast }     from '@/composables/useToast'

const props = defineProps({
  visible: { type: Boolean, required: true },
  user:    { type: Object,  default: null  }, // null = create mode
})
const emit = defineEmits(['update:visible', 'saved'])

const store = useUserStore()
const toast = useToast()
const isEdit = computed(() => !!props.user)

const roleOptions = [
  { label: 'Super Admin',   value: 'super-admin'  },
  { label: 'Manager',       value: 'manager'      },
  { label: 'Loan Officer',  value: 'loan-officer' },
  { label: 'Staff',         value: 'staff'        },
  { label: 'Board Member',  value: 'board'        },
]

const roleDescriptions = {
  'super-admin':  'Full access — all modules, settings, user management, and audit log.',
  'manager':      'Approve loans, view all reports, post payments, view audit log.',
  'loan-officer': 'Create and edit members and loans, record payments.',
  'staff':        'View-only access to members and loans.',
  'board':        'View loans and participate in high-value loan approvals.',
}

const form   = reactive({ name: '', email: '', role: 'loan-officer', password: '' })
const errors = reactive({})

watch(() => props.visible, (v) => {
  if (!v) return
  Object.keys(errors).forEach(k => delete errors[k])
  if (props.user) {
    Object.assign(form, {
      name:  props.user.name,
      email: props.user.email,
      role:  props.user.role ?? 'loan-officer',
      password: '',
    })
  } else {
    Object.assign(form, { name: '', email: '', role: 'loan-officer', password: '' })
  }
})

async function submit() {
  Object.keys(errors).forEach(k => delete errors[k])
  if (!form.name)  errors.name  = 'Name is required.'
  if (!form.email) errors.email = 'Email is required.'
  if (!form.role)  errors.role  = 'Role is required.'
  if (!isEdit.value && !form.password) errors.password = 'Password is required.'
  if (Object.keys(errors).length) return

  try {
    if (isEdit.value) {
      await store.updateUser(props.user.id, { name: form.name, email: form.email, role: form.role })
      toast.success(`${form.name} updated.`)
    } else {
      await store.createUser({ ...form })
      toast.success(`${form.name} created. Temporary password set — share it securely.`)
    }
    emit('update:visible', false)
    emit('saved')
  } catch (e) {
    const serverErrors = e?.response?.data?.errors ?? {}
    Object.assign(errors, serverErrors)
    if (e?.response?.data?.message) {
      toast.error(e.response.data.message)
    }
  }
}
</script>

<style scoped>
.form-grid  { display:flex; flex-direction:column; gap:14px; }
.field      { display:flex; flex-direction:column; gap:4px; }
.field.full { width:100%; }
.field-label{ font-size:12px; font-weight:500; color:var(--text-color-secondary); }
.req        { color:var(--red-500); }
.hint       { font-size:11px; color:var(--text-color-secondary); }
.error      { font-size:11px; color:var(--red-500); min-height:14px; }
</style>
