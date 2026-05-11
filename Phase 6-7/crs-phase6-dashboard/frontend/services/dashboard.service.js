// src/services/dashboard.service.js
import api from '@/services/api'

export const dashboardService = {
  get: () => api.get('/dashboard'),
}
