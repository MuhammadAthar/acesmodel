<template>
  <div class="max-w-screen-2xl mx-auto px-6 py-10" v-if="campaign">
    <!-- Header -->
    <div class="flex items-start justify-between mb-8">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <RouterLink to="/studio/campaigns" class="text-muted hover:text-white transition-colors">
            <i class="fa-solid fa-chevron-left text-lg"></i>
          </RouterLink>
          <span
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
            :class="statusClass"
          >
            <span v-if="campaign.status === 'generating'" class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse-soft"></span>
            {{ campaign.status }}
          </span>
        </div>
        <h1 class="heading-lg text-white">{{ campaign.name }}</h1>
        <p v-if="campaign.theme" class="text-accent capitalize mt-1">{{ campaign.theme }} campaign</p>
      </div>
      <div class="flex items-center gap-2">
        <!-- Delete flow -->
        <template v-if="!showDeleteConfirm">
          <button @click="showDeleteConfirm = true" class="btn-ghost text-muted hover:text-red-400 hover:border-red-400/30">
            <i class="fa-solid fa-trash-can text-xs"></i>
            Delete
          </button>
        </template>
        <template v-else>
          <span class="text-xs text-muted">Delete this campaign?</span>
          <button @click="confirmDelete" :disabled="deleting"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/30 hover:bg-red-500/20 transition-all disabled:opacity-40 flex items-center gap-1.5">
            <i v-if="deleting" class="fa-solid fa-spinner fa-spin text-xs"></i>
            {{ deleting ? 'Deleting…' : 'Yes, delete' }}
          </button>
          <button @click="showDeleteConfirm = false" class="btn-ghost text-xs">Cancel</button>
        </template>

        <button @click="downloadAll" class="btn-ghost">
          <i class="fa-solid fa-download"></i>
          Download All
        </button>
      </div>
    </div>

    <!-- Asset type filter tabs -->
    <div class="flex items-center gap-1 mb-8 overflow-x-auto pb-1">
      <button
        v-for="tab in tabs" :key="tab.key"
        @click="activeTab = tab.key"
        class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all"
        :class="activeTab === tab.key
          ? 'bg-accent text-canvas'
          : 'text-muted hover:text-white glass border border-border'"
      >
        <i :class="tab.icon" class="text-xs"></i> {{ tab.label }}
        <span v-if="tab.count" class="text-xs opacity-70">({{ tab.count }})</span>
      </button>
    </div>

    <!-- Generating notice -->
    <div v-if="campaign.status === 'generating'"
      class="glass rounded-2xl p-5 border border-yellow-500/20 flex items-center gap-4 mb-8">
      <div class="w-8 h-8 rounded-full border-2 border-yellow-500/30 border-t-yellow-500 animate-spin flex-shrink-0"></div>
      <div>
        <p class="text-white font-medium">Generating your campaign assets...</p>
        <p class="text-muted text-sm">This may take a few minutes. We'll update automatically.</p>
      </div>
    </div>

    <!-- Assets grid -->
    <div v-if="filteredAssets.length" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
      <div v-for="asset in filteredAssets" :key="asset.id" class="asset-card group">

        <!-- Image asset -->
        <div v-if="asset.asset_type === 'photo'" class="aspect-[3/4] relative overflow-hidden bg-surface">
          <!-- Ready -->
          <img v-if="asset.status === 'ready' && asset.file_path"
            :src="`/storage/${asset.file_path}`"
            :alt="asset.asset_subtype"
            class="w-full h-full object-cover"
          />
          <!-- Failed -->
          <div v-else-if="asset.status === 'failed'" class="w-full h-full flex flex-col items-center justify-center gap-2 p-4 bg-red-500/5">
            <i class="fa-solid fa-triangle-exclamation text-red-400 text-xl"></i>
            <p class="text-[10px] text-red-400 font-medium text-center">Generation failed</p>
            <p v-if="asset.error_message" class="text-[9px] text-muted text-center line-clamp-3 leading-relaxed">{{ asset.error_message }}</p>
          </div>
          <!-- Generating / pending -->
          <div v-else class="w-full h-full skeleton flex flex-col items-center justify-center gap-2">
            <div class="w-6 h-6 rounded-full border-2 border-accent/30 border-t-accent animate-spin"></div>
            <span class="text-muted text-[10px]">Generating…</span>
          </div>

          <!-- Hover overlay (only when ready) -->
          <div v-if="asset.status === 'ready' && asset.file_path"
            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3 gap-2">
            <button @click="downloadAsset(asset)" class="flex-1 btn-accent text-xs py-2 justify-center">Download</button>
          </div>
          <div class="absolute top-2 left-2">
            <span class="tag capitalize">{{ asset.asset_subtype }}</span>
          </div>
        </div>

        <!-- Text asset -->
        <div v-else-if="asset.asset_type === 'copy'" class="p-4 min-h-32">
          <p class="text-xs text-muted uppercase tracking-widest mb-2">{{ asset.asset_subtype }}</p>
          <p class="text-sm text-white leading-relaxed line-clamp-6">{{ asset.content }}</p>
          <button @click="copyText(asset.content)" class="mt-3 text-xs text-accent hover:text-accent-dim transition-colors">
            Copy to clipboard
          </button>
        </div>

        <!-- Generating state -->
        <div v-else class="aspect-[3/4] skeleton flex items-center justify-center">
          <span class="text-muted text-xs">Generating...</span>
        </div>
      </div>
    </div>

    <!-- Empty assets -->
    <div v-else-if="campaign.status === 'ready'" class="text-center py-24">
      <p class="text-muted">No assets for this filter.</p>
    </div>
  </div>

  <!-- Loading state -->
  <div v-else class="flex items-center justify-center h-[60vh]">
    <div class="w-10 h-10 rounded-full border-2 border-border border-t-accent animate-spin"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useCampaignStore } from '@/stores/campaign'

const route  = useRoute()
const router = useRouter()
const store  = useCampaignStore()
const activeTab = ref('all')
const showDeleteConfirm = ref(false)
const deleting          = ref(false)

const campaign = computed(() => store.current)

const tabs = computed(() => [
  { key: 'all',   icon: 'fa-solid fa-border-all',      label: 'All',     count: store.assets.length },
  { key: 'photo', icon: 'fa-solid fa-camera',           label: 'Photos',  count: store.assets.filter(a => a.asset_type === 'photo').length },
  { key: 'video', icon: 'fa-solid fa-film',             label: 'Videos',  count: store.assets.filter(a => a.asset_type === 'video').length },
  { key: 'copy',  icon: 'fa-solid fa-pen-nib',          label: 'Copy',    count: store.assets.filter(a => a.asset_type === 'copy').length },
  { key: 'ad',    icon: 'fa-solid fa-bullhorn',         label: 'Ads',     count: store.assets.filter(a => a.asset_type === 'ad').length },
])

const filteredAssets = computed(() =>
  activeTab.value === 'all' ? store.assets : store.assets.filter(a => a.asset_type === activeTab.value)
)

const statusClass = computed(() => ({
  'bg-green-500/20 text-green-400 border border-green-500/30':   campaign.value?.status === 'ready',
  'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': campaign.value?.status === 'generating',
  'bg-white/5 text-muted border border-border':                  campaign.value?.status === 'draft',
}))

let pollInterval = null

onMounted(async () => {
  await store.fetchOne(route.params.id)
  await store.fetchAssets(route.params.id)

  if (campaign.value?.status === 'generating') {
    pollInterval = setInterval(async () => {
      await store.fetchOne(route.params.id)
      await store.fetchAssets(route.params.id)
      if (campaign.value?.status !== 'generating') clearInterval(pollInterval)
    }, 5000)
  }
})

onUnmounted(() => clearInterval(pollInterval))

function downloadAsset(asset) {
  if (asset.file_path) {
    const a = document.createElement('a')
    a.href = `/storage/${asset.file_path}`
    a.download = `thread-ai-${asset.id}.jpg`
    a.click()
  }
}

function downloadAll() {
  store.assets.filter(a => a.file_path).forEach(downloadAsset)
}

function copyText(text) {
  navigator.clipboard.writeText(text)
}

async function confirmDelete() {
  deleting.value = true
  try {
    await store.remove(campaign.value.id)
    router.push('/studio/campaigns')
  } catch (e) {
    console.error(e)
    deleting.value = false
    showDeleteConfirm.value = false
  }
}
</script>
