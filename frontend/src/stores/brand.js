import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/lib/api'

export const useBrandStore = defineStore('brand', () => {
  const brands  = ref([])
  const current = ref(null)

  async function fetchAll() {
    const { data } = await api.get('/brands')
    brands.value = data
    if (!current.value && data.length) current.value = data[0]
  }

  async function create(form) {
    const fd = new FormData()
    for (const [k, v] of Object.entries(form)) {
      if (v !== null && v !== undefined) fd.append(k, v)
    }
    const { data } = await api.post('/brands', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    brands.value.push(data)
    current.value = data
    return data
  }

  async function analyzeDna(brandId, files) {
    const fd = new FormData()
    files.forEach(f => fd.append('assets[]', f))
    await api.post(`/brands/${brandId}/analyze-dna`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  }

  async function remove(id) {
    await api.delete(`/brands/${id}`)
    brands.value = brands.value.filter(b => b.id !== id)
  }

  return { brands, current, fetchAll, create, analyzeDna, remove }
})
