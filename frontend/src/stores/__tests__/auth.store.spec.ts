import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth.store'

describe('auth.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('starts unauthenticated', () => {
    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
  })

  it('sets token and parses JWT payload', () => {
    const store = useAuthStore()
    const payload = btoa(JSON.stringify({ email: 'test@test.com', roles: ['ROLE_ADMIN'] }))
    const token = `header.${payload}.signature`

    store.setToken(token)

    expect(store.isAuthenticated).toBe(true)
    expect(store.user?.email).toBe('test@test.com')
    expect(store.user?.roles).toContain('ROLE_ADMIN')
  })

  it('hasRole returns correct value', () => {
    const store = useAuthStore()
    const payload = btoa(JSON.stringify({ email: 'test@test.com', roles: ['ROLE_ADMIN'] }))
    store.setToken(`header.${payload}.signature`)

    expect(store.hasRole('ROLE_ADMIN')).toBe(true)
    expect(store.hasRole('ROLE_EDITOR')).toBe(false)
  })

  it('logout clears state', () => {
    const store = useAuthStore()
    store.setToken('header.eyJlbWFpbCI6InRlc3QifQ.signature')
    store.logout()

    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
    expect(localStorage.getItem('jwt_token')).toBeNull()
  })
})
