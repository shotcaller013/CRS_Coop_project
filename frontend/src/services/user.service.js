// src/services/user.service.js
import api from '@/services/api'

export const userService = {
  index:         (params = {}) => api.get('/users', { params }),
  store:         (data)        => api.post('/users', data),
  show:          (id)          => api.get(`/users/${id}`),
  update:        (id, data)    => api.put(`/users/${id}`, data),
  toggleActive:  (id)          => api.post(`/users/${id}/toggle-active`),
  resetPassword: (id)          => api.post(`/users/${id}/reset-password`),
}
