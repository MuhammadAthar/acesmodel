import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  withCredentials: true,
})

// Attach token from localStorage
api.interceptors.request.use(config => {
  const token = localStorage.getItem('aces_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Handle 401 globally — but not on the login/register endpoints themselves
api.interceptors.response.use(
  res => res,
  err => {
    const url = err.config?.url ?? ''
    const isAuthEndpoint = url.includes('/login') || url.includes('/register')
    if (err.response?.status === 401 && !isAuthEndpoint) {
      localStorage.removeItem('aces_token')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

export default api
