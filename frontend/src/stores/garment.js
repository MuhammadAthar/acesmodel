import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/lib/api'

export const useGarmentStore = defineStore('garment', () => {
  const garments = ref([])
  const current  = ref(null)
  const loading  = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const { data } = await api.get('/garments')
      garments.value = data.data ?? data
    } finally {
      loading.value = false
    }
  }

  async function upload(file, name = null, brandId = null) {
    const form = new FormData()
    form.append('image', file)
    if (name)    form.append('name', name)
    if (brandId) form.append('brand_id', brandId)

    const { data } = await api.post('/garments', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    garments.value.unshift(data)
    current.value = data
    return data
  }

  async function fetchOne(id) {
    const { data } = await api.get(`/garments/${id}`)
    current.value = data
    return data
  }

  async function remove(id) {
    await api.delete(`/garments/${id}`)
    garments.value = garments.value.filter(g => g.id !== id)
  }

  return { garments, current, loading, fetchAll, upload, fetchOne, remove }
})
