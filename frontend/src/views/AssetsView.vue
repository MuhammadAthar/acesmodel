<template>
  <div class="max-w-screen-2xl mx-auto px-6 py-10">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="heading-lg text-white">Asset Library</h1>
        <p class="text-muted mt-1">All your generated content in one place</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Type filter -->
        <select v-model="typeFilter" class="input-dark w-auto text-sm py-2">
          <option value="">All types</option>
          <option value="photo">Photos</option>
          <option value="video">Videos</option>
          <option value="copy">Copy</option>
          <option value="ad">Ads</option>
        </select>
        <!-- Delete selected -->
        <button v-if="selected.length" @click="deleteSelected"
          class="btn-ghost text-red-400 border-red-500/30 hover:border-red-500/50 text-sm py-2">
          Delete ({{ selected.length }})
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
      <div v-for="n in 15" :key="n" class="aspect-[3/4] rounded-2xl skeleton"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="!assets.length" class="text-center py-32">
      <p class="text-muted">No assets yet. Generate a campaign first.</p>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
      <div
        v-for="asset in assets" :key="asset.id"
        class="asset-card group cursor-pointer"
        :class="selected.includes(asset.id) ? 'border-accent/50' : ''"
        @click="toggleSelect(asset.id)"
      >
        <!-- Photo -->
        <div v-if="asset.asset_type === 'photo'" class="aspect-[3/4] relative overflow-hidden bg-surface">
          <img v-if="asset.file_path" :src="`/storage/${asset.file_path}`" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full skeleton"></div>
          <!-- Checkmark -->
          <div v-if="selected.includes(asset.id)"
            class="absolute top-2 right-2 w-6 h-6 rounded-full bg-accent flex items-center justify-center">
            <i class="fa-solid fa-check text-canvas text-xs"></i>
          </div>
          <!-- Hover overlay -->
          <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center p-3">
            <button @click.stop="downloadOne(asset)" class="btn-accent text-xs py-1.5 px-3">Download</button>
          </div>
        </div>

        <!-- Text asset -->
        <div v-else class="p-3 min-h-28 flex flex-col justify-between">
          <p class="text-xs text-muted uppercase tracking-widest mb-1">{{ asset.asset_subtype }}</p>
          <p class="text-xs text-white leading-relaxed line-clamp-5">{{ asset.content }}</p>
        </div>

        <div class="px-3 py-2 border-t border-border flex items-center justify-between">
          <span class="tag text-xs">{{ asset.asset_subtype || asset.asset_type }}</span>
          <span class="text-xs text-muted">{{ formatDate(asset.created_at) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'

const assets     = ref([])
const loading    = ref(false)
const selected   = ref([])
const typeFilter = ref('')

onMounted(fetchAssets)

async function fetchAssets() {
  loading.value = true
  try {
    const { data } = await api.get('/assets', { params: { asset_type: typeFilter.value || undefined } })
    assets.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

function toggleSelect(id) {
  const idx = selected.value.indexOf(id)
  if (idx >= 0) selected.value.splice(idx, 1)
  else selected.value.push(id)
}

async function deleteSelected() {
  await api.post('/assets/bulk-delete', { ids: selected.value })
  assets.value = assets.value.filter(a => !selected.value.includes(a.id))
  selected.value = []
}

function downloadOne(asset) {
  if (!asset.file_path) return
  const a = document.createElement('a')
  a.href = `/storage/${asset.file_path}`
  a.download = `asset-${asset.id}.jpg`
  a.click()
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
</script>
