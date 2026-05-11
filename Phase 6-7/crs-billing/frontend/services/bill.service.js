// src/services/bill.service.js
import api from '@/services/api'

export const billService = {
  index:            (params = {})        => api.get('/bills', { params }),
  store:            (data)               => api.post('/bills', data),
  show:             (id)                 => api.get(`/bills/${id}`),
  issue:            (id)                 => api.post(`/bills/${id}/issue`),
  settle:           (id)                 => api.post(`/bills/${id}/settle`),
  cancel:           (id)                 => api.post(`/bills/${id}/cancel`),

  uploadRemittance: (id, data, file) => {
    const form = new FormData()
    Object.entries(data).forEach(([k, v]) => { if (v != null) form.append(k, v) })
    if (file) form.append('file', file)
    return api.post(`/bills/${id}/remittance`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  downloadPdf: (id, billNo) =>
    api.get(`/bills/${id}/pdf`, { responseType: 'blob' }).then(res => {
      const url = URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
      const a   = document.createElement('a')
      a.href    = url; a.download = `Bill_${billNo}.pdf`; a.click()
      URL.revokeObjectURL(url)
    }),

  downloadRemittanceFile: (remittanceId) =>
    api.get(`/bills/remittance/${remittanceId}/file`, { responseType: 'blob' }),
}
