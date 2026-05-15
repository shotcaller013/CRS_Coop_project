// src/services/setting.service.js
import { request } from '@/composables/useApi'

export const settingService = {
  getProfile:        ()        => request('/settings/profile'),
  updateProfile:     (data)    => request('/settings/profile', { method: 'PUT', body: data }),
  getLoanTypes:      ()        => request('/settings/loan-types'),
  getLoanType:       (id)      => request(`/settings/loan-types/${id}`),
  createLoanType:    (data)    => request('/settings/loan-types', { method: 'POST', body: data }),
  updateLoanType:    (id, data)=> request(`/settings/loan-types/${id}`, { method: 'PUT', body: data }),
  deleteLoanType:    (id)      => request(`/settings/loan-types/${id}`, { method: 'DELETE' }),
  getPreferences:    ()        => request('/settings/preferences'),
  updatePreferences: (data)    => request('/settings/preferences', { method: 'PUT', body: data }),
  checkEligibility:  (data)    => request('/loans/eligibility-check', { method: 'POST', body: data }),

  // Member Portal Account management (admin)
  getMemberPortalAccounts:      (params = {}) => {
    const q = new URLSearchParams(params).toString()
    return request(`/member-portal-accounts${q ? '?' + q : ''}`)
  },
  createMemberPortalAccount:    (data)        => request('/member-portal-accounts', { method: 'POST', body: data }),
  updateMemberPortalAccount:    (id, data)    => request(`/member-portal-accounts/${id}`, { method: 'PUT', body: data }),
  toggleMemberPortalAccount:    (id)          => request(`/member-portal-accounts/${id}/toggle-active`, { method: 'POST', body: {} }),
  resetMemberPortalPassword:    (id)          => request(`/member-portal-accounts/${id}/reset-password`, { method: 'POST', body: {} }),
}
