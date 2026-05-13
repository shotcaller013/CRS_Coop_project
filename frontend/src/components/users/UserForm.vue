<template>
  <Teleport to="body">
    <div v-if="visible" class="modal-overlay" @click.self="close">
      <div class="modal" style="max-width:460px">
        <div class="modal-header">
          <span class="modal-title">{{ isEdit ? 'Edit user' : 'Create user' }}</span>
          <button class="close-btn" :disabled="store.saving" @click="close">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-fields">

            <div class="form-group">
              <label class="form-label">Full name <span class="req">*</span></label>
              <input v-model="form.name" class="form-input" :class="{ invalid: errors.name }"
                placeholder="e.g. Angelica Rosales" />
              <span v-if="errors.name" class="err-msg">{{ errors.name }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Email address <span class="req">*</span></label>
              <input v-model="form.email" type="email" class="form-input" :class="{ invalid: errors.email }"
                placeholder="user@example.com" />
              <span v-if="errors.email" class="err-msg">{{ errors.email }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Role <span class="req">*</span></label>
              <select v-model="form.role" class="form-select" :class="{ invalid: errors.role }">
                <option value="" disabled>Select role…</option>
                <option v-for="r in roleOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
              </select>
              <span v-if="errors.role" class="err-msg">{{ errors.role }}</span>
              <span v-if="form.role" class="hint-msg">{{ roleDescriptions[form.role] }}</span>
            </div>

            <div v-if="!isEdit" class="form-group">
              <label class="form-label">Temporary password <span class="req">*</span></label>
              <div class="pw-wrap">
                <input v-model="form.password" :type="showPw ? 'text' : 'password'"
                  class="form-input" :class="{ invalid: errors.password }"
                  placeholder="Min 8 chars, mixed case + numbers" />
                <button type="button" class="pw-toggle" @click="showPw = !showPw">
                  <svg v-if="showPw" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  <svg v-else viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <span v-if="errors.password" class="err-msg">{{ errors.password }}</span>
              <span class="hint-msg">The user will be required to change this on first login.</span>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" :disabled="store.saving" @click="close">Cancel</button>
          <button class="btn btn-primary" :disabled="store.saving" @click="submit">
            <span v-if="store.saving" class="spinner-sm"></span>
            {{ isEdit ? 'Save changes' : 'Create user' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { useUserStore } from '@/stores/user.store'
import { useToast }     from '@/composables/useToast'

const props = defineProps({
  visible: { type: Boolean, required: true },
  user:    { type: Object,  default: null },
})
const emit = defineEmits(['update:visible', 'saved'])

const store  = useUserStore()
const toast  = useToast()
const showPw = ref(false)
const isEdit = computed(() => !!props.user)

const roleOptions = [
  { label: 'Super Admin',  value: 'super-admin'  },
  { label: 'Manager',      value: 'manager'      },
  { label: 'Loan Officer', value: 'loan-officer' },
  { label: 'Staff',        value: 'staff'        },
  { label: 'Board Member', value: 'board'        },
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

function close() { if (!store.saving) emit('update:visible', false) }

watch(() => props.visible, (v) => {
  if (!v) return
  showPw.value = false
  Object.keys(errors).forEach(k => delete errors[k])
  Object.assign(form, props.user
    ? { name: props.user.name, email: props.user.email, role: props.user.role ?? 'loan-officer', password: '' }
    : { name: '', email: '', role: 'loan-officer', password: '' }
  )
})

async function submit() {
  Object.keys(errors).forEach(k => delete errors[k])
  if (!form.name)                     errors.name     = 'Name is required.'
  if (!form.email)                    errors.email    = 'Email is required.'
  if (!form.role)                     errors.role     = 'Role is required.'
  if (!isEdit.value && !form.password) errors.password = 'Password is required.'
  if (Object.keys(errors).length) return

  try {
    if (isEdit.value) {
      await store.updateUser(props.user.id, { name: form.name, email: form.email, role: form.role })
      toast.success(`${form.name} updated.`)
    } else {
      await store.createUser({ ...form })
      toast.success(`${form.name} created.`)
    }
    emit('update:visible', false)
    emit('saved')
  } catch (e) {
    Object.assign(errors, e?.response?.data?.errors ?? {})
    if (e?.response?.data?.message) toast.error(e.response.data.message)
  }
}
</script>

<style scoped>
.form-fields { display: flex; flex-direction: column; gap: 14px; }
.req  { color: var(--red); }
.err-msg  { font-size: 11px; color: var(--red); margin-top: 2px; }
.hint-msg { font-size: 11px; color: var(--ink-muted); margin-top: 2px; }
.invalid  { border-color: var(--red) !important; }

.pw-wrap { position: relative; }
.pw-wrap .form-input { padding-right: 38px; }
.pw-toggle {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; color: var(--ink-muted);
  display: flex; align-items: center;
}
.pw-toggle svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

.close-btn {
  background: none; border: none; cursor: pointer; color: var(--ink-muted);
  padding: 4px; border-radius: 4px; display: flex; align-items: center;
}
.close-btn:hover { background: var(--surface-2); color: var(--ink); }
.close-btn svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; }

.spinner-sm {
  width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
