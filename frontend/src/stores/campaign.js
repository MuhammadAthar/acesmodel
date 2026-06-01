import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/lib/api'

export const useCampaignStore = defineStore('campaign', () => {
  const campaigns = ref([])
  const current   = ref(null)
  const assets    = ref([])
  const loading   = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const { data } = await api.get('/campaigns')
      campaigns.value = data.data ?? data
    } finally {
      loading.value = false
    }
  }

  async function create(payload) {
    const { data } = await api.post('/campaigns', payload)
    campaigns.value.unshift(data)
    current.value = data
    return data
  }

  async function fetchOne(id) {
    const { data } = await api.get(`/campaigns/${id}`)
    current.value = data
    return data
  }

  async function generate(id, options) {
    const { data } = await api.post(`/campaigns/${id}/generate`, options)
    return data
  }

  async function fetchAssets(id, params = {}) {
    const { data } = await api.get(`/campaigns/${id}/assets`, { params })
    assets.value = data.data ?? data
    return assets.value
  }

  async function remove(id) {
    await api.delete(`/campaigns/${id}`)
    campaigns.value = campaigns.value.filter(c => c.id !== id)
  }

  return { campaigns, current, assets, loading, fetchAll, create, fetchOne, generate, fetchAssets, remove }
})
