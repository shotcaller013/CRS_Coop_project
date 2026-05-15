// src/services/portal.service.js
// Uses its own fetch (not the admin Axios/fetch) — no admin Sanctum token

const BASE = '/api/v1'

async function portalRequest(path, options = {}) {
  const token = sessionStorage.getItem('crs-member-portal-token')
  const res = await fetch(`${BASE}${path}`, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
    ...options,
    body: options.body ? JSON.stringify(options.body) : undefined,
  })

  const json = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`)
  return json.data !== undefined ? json.data : json
}

export const portalService = {
  login: (identifier, password) =>
    portalRequest('/member-portal/auth/login', {
      method: 'POST',
      body: { identifier, password },
    }),

  getDashboard: () => portalRequest('/member-portal/dashboard'),
}
