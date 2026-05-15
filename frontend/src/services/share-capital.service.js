import api from '@/services/api'

export const shareCapitalService = {
  list:    (memberId, params = {}) => api.get(`/members/${memberId}/share-capital`, { params }),
  store:   (memberId, data)        => api.post(`/members/${memberId}/share-capital`, data),
  summary: (memberId)              => api.get(`/members/${memberId}/share-capital/summary`),
  report:  (params = {})           => api.get('/share-capital/report', { params }),
  destroy: (id)                    => api.delete(`/share-capital/${id}`),
  pdfUrl:  (memberId)              => `/api/v1/members/${memberId}/share-capital/ledger.pdf`,
}
