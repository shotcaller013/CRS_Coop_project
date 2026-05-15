import api from '@/services/api'

export const auditLogService = {
  index:     (params = {}) => api.get('/audit-logs', { params }),
  show:      (id)          => api.get(`/audit-logs/${id}`),
  forRecord: (type, id)    => api.get(`/audit-logs/for/${type}/${id}`),
}
