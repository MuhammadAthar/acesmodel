import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/lib/api'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(null)
  const token = ref(localStorage.getItem('aces_token') || null)

  const isLoggedIn    = computed(() => !!token.value && !!user.value)
  const credits       = computed(() => user.value?.credits ?? 0)
  const plan          = computed(() => user.value?.plan ?? 'free')
  const isSuperAdmin  = computed(() => user.value?.role === 'superadmin')

  async function fetchMe() {
    try {
      const { data } = await api.get('/me')
      user.value = data
    } catch {
      token.value = null
      user.value  = null
      localStorage.removeItem('aces_token')
    }
  }

  async function login(email, password) {
    const { data } = await api.post('/login', { email, password })
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('aces_token', data.token)
  }

  async function register(name, email, password, password_confirmation) {
    const { data } = await api.post('/register', { name, email, password, password_confirmation })
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('aces_token', data.token)
  }

  async function logout() {
    try { await api.post('/logout') } catch {}
    token.value = null
    user.value  = null
    localStorage.removeItem('aces_token')
  }

  return { user, token, isLoggedIn, isSuperAdmin, credits, plan, fetchMe, login, register, logout }
})
