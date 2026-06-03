import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api.service'

function parseJwt(token: string): { id?: string; email?: string; roles?: string[] } | null {
  try {
    const parts = token.split('.')
    if (parts.length < 2) return null
    const payloadPart = parts[1]!
    const base64 = payloadPart.replace(/-/g, '+').replace(/_/g, '/')
    const json = decodeURIComponent(
      atob(base64)
        .split('')
        .map((c) => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
        .join('')
    )
    return JSON.parse(json)
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('jwt_token'))
  const payload = token.value ? parseJwt(token.value) : null
  const user = ref<{ id: string; email: string; roles: string[] } | null>(
    payload ? { id: payload.id || '', email: payload.email || '', roles: payload.roles || [] } : null
  )
  const isAuthenticated = ref(!!token.value)

  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem('jwt_token', newToken)
    const p = parseJwt(newToken)
    user.value = p ? { id: p.id || '', email: p.email || '', roles: p.roles || [] } : null
    isAuthenticated.value = true
  }

  function clearAuth() {
    token.value = null
    user.value = null
    isAuthenticated.value = false
    localStorage.removeItem('jwt_token')
  }

  async function login(email: string, password: string) {
    const response = await api.post('/auth/login', { email, password })
    setToken(response.data.token)
    return response.data
  }

  function logout() {
    clearAuth()
  }

  function hasRole(role: string): boolean {
    return user.value?.roles.includes(role) ?? false
  }

  return { user, isAuthenticated, login, logout, hasRole, setToken, clearAuth }
})
