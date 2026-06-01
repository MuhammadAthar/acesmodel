<template>
  <div>
    <div class="bg-[#111] border border-[#1e1e1e] rounded-xl overflow-hidden">
      <div class="px-5 py-4 border-b border-[#1e1e1e] flex items-center gap-3">
        <div class="relative flex-1 max-w-xs">
          <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[#555] text-sm"></i>
          <input v-model="search" @input="debouncedFetch" placeholder="Search models…"
            class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg pl-9 pr-4 py-2 text-sm text-white placeholder-[#555] focus:outline-none focus:border-[#333]" />
        </div>
      </div>

      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[#1e1e1e]">
            <th class="text-left px-5 py-3 text-[#555] font-medium">Model Name</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Owner</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Gender</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Ethnicity</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Default</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Created</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="7" class="px-5 py-10 text-center text-[#555]"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading…</td></tr>
          <tr v-else-if="!models.length"><td colspan="7" class="px-5 py-10 text-center text-[#555]">No models found.</td></tr>
          <tr v-for="m in models" :key="m.id" class="border-b border-[#1a1a1a] hover:bg-[#141414] transition-colors">
            <td class="px-5 py-3 text-white font-medium">{{ m.name }}</td>
            <td class="px-5 py-3 text-[#888]">{{ m.user?.name }}</td>
            <td class="px-5 py-3 text-[#888] capitalize">{{ m.gender ?? '—' }}</td>
            <td class="px-5 py-3 text-[#888] capitalize">{{ m.ethnicity ?? '—' }}</td>
            <td class="px-5 py-3">
              <span v-if="m.is_default" class="text-xs px-2 py-1 rounded-full bg-[#1a2a1a] text-green-400">Yes</span>
              <span v-else class="text-[#555] text-xs">—</span>
            </td>
            <td class="px-5 py-3 text-[#555] text-xs">{{ formatDate(m.created_at) }}</td>
            <td class="px-5 py-3">
              <button @click="deleteModel(m)" class="text-[#555] hover:text-red-400 transition-colors text-sm px-2">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="meta" class="flex items-center justify-between px-5 py-3 border-t border-[#1e1e1e]">
        <span class="text-[#555] text-xs">{{ meta.from }}–{{ meta.to }} of {{ meta.total }}</span>
        <div class="flex gap-2">
          <button @click="goPage(meta.current_page - 1)" :disabled="meta.current_page === 1"
            class="px-3 py-1 rounded text-xs text-[#888] hover:text-white disabled:opacity-30">Prev</button>
          <button @click="goPage(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page"
            class="px-3 py-1 rounded text-xs text-[#888] hover:text-white disabled:opacity-30">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'

const models  = ref([])
const meta    = ref(null)
const loading = ref(false)
const search  = ref('')
const page    = ref(1)

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; fetchModels() }, 400)
}

async function fetchModels() {
  loading.value = true
  try {
    const res = await api.get('/admin/models', { params: { search: search.value, page: page.value } })
    models.value = res.data.data
    meta.value   = res.data.meta
  } finally {
    loading.value = false
  }
}

function goPage(p) { page.value = p; fetchModels() }

async function deleteModel(m) {
  if (!confirm(`Delete model "${m.name}"?`)) return
  await api.delete(`/admin/models/${m.id}`)
  fetchModels()
}

function formatDate(dt) {
  return new Date(dt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(fetchModels)
</script>
