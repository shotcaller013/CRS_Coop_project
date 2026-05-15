import api from '@/services/api'

export const notificationService = {
  index:          (params = {}) => api.get('/notification-logs', { params }),
  settings:       ()            => api.get('/notification-logs/settings'),
  updateSettings: (data)        => api.put('/notification-logs/settings', data),
  testSms:        (data)        => api.post('/notification-logs/test-sms', data),
}
