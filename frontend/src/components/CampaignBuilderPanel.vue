<template>
  <div class="space-y-6">
    <!-- Output type grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <button
        v-for="type in outputTypes" :key="type.key"
        @click="toggleType(type.key)"
        class="group relative p-4 rounded-2xl border transition-all duration-300 text-left"
        :class="selectedTypes.includes(type.key)
          ? 'border-accent/50 bg-accent-glow'
          : 'border-border glass-hover'"
      >
        <div class="flex items-start justify-between mb-3">
          <i :class="type.icon" class="text-xl text-accent"></i>
          <div v-if="selectedTypes.includes(type.key)"
            class="w-5 h-5 rounded-full bg-accent flex items-center justify-center">
            <i class="fa-solid fa-check text-canvas text-xs"></i>
          </div>
        </div>
        <p class="text-sm font-medium text-white">{{ type.label }}</p>
        <p class="text-xs text-muted mt-0.5">{{ type.desc }}</p>
      </button>
    </div>

    <!-- Subtypes (conditional) -->
    <div v-if="selectedTypes.includes('photo')" class="glass rounded-2xl p-4 border border-border">
      <p class="text-xs font-medium text-muted uppercase tracking-widest mb-3">Photo styles</p>
      <div class="flex flex-wrap gap-2">
        <button v-for="s in photoSubtypes" :key="s.key"
          @click="toggleSubtype(s.key)"
          class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
          :class="selectedSubtypes.includes(s.key)
            ? 'border-accent/50 text-accent bg-accent-glow'
            : 'border-border text-muted hover:border-accent/30 hover:text-white'"
        >{{ s.label }}</button>
      </div>
    </div>

    <!-- Campaign theme -->
    <div class="glass rounded-2xl p-4 border border-border">
      <p class="text-xs font-medium text-muted uppercase tracking-widest mb-3">Campaign theme</p>
      <div class="flex flex-wrap gap-2">
        <button v-for="theme in themes" :key="theme"
          @click="selectedTheme = theme"
          class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all capitalize"
          :class="selectedTheme === theme
            ? 'border-accent/50 text-accent bg-accent-glow'
            : 'border-border text-muted hover:border-accent/30 hover:text-white'"
        >{{ theme }}</button>
      </div>
    </div>

    <!-- Generate button -->
    <div class="flex items-center justify-between pt-2">
      <div class="text-sm text-muted">
        <span class="text-white font-medium">{{ totalCost }}</span> credits required
      </div>
      <button
        @click="launchCampaign"
        :disabled="!selectedTypes.length || generating"
        class="btn-accent disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <i v-if="generating" class="fa-solid fa-spinner fa-spin"></i>
        <span>{{ generating ? 'Generating...' : 'Generate Campaign' }}</span>
      </button>
    </div>

    <!-- Success state -->
    <div v-if="campaignId" class="glass rounded-2xl p-5 border border-accent/30 text-center">
      <p class="text-accent font-medium mb-2">Campaign created! Assets are generating...</p>
      <RouterLink :to="`/studio/campaigns/${campaignId}`" class="btn-ghost text-sm">
        View Campaign <i class="fa-solid fa-arrow-right text-xs ml-1"></i>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useCampaignStore } from '@/stores/campaign'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({ garment: { type: Object, required: true } })

const campaignStore = useCampaignStore()
const auth = useAuthStore()

const selectedTypes    = ref(['photo'])
const selectedSubtypes = ref(['studio', 'lifestyle'])
const selectedTheme    = ref('default')
const generating       = ref(false)
const campaignId       = ref(null)

const outputTypes = [
  { key: 'photo',  icon: 'fa-solid fa-camera',           label: 'Model Photos',   desc: 'Studio, lifestyle, editorial' },
  { key: 'video',  icon: 'fa-solid fa-film',              label: 'Videos',         desc: 'Reels, TikTok, Shorts' },
  { key: 'ad',     icon: 'fa-solid fa-bullhorn',          label: 'Ad Creatives',   desc: 'Meta, Google Display' },
  { key: 'copy',   icon: 'fa-solid fa-pen-nib',           label: 'Copy & Captions',desc: 'Descriptions, hashtags' },
  { key: 'social', icon: 'fa-solid fa-mobile-screen',     label: 'Social Posts',   desc: 'Ready-to-publish content' },
  { key: 'seo',    icon: 'fa-solid fa-magnifying-glass',  label: 'SEO Content',    desc: 'Product page copy' },
]

const photoSubtypes = [
  { key: 'studio',    label: 'Studio' },
  { key: 'lifestyle', label: 'Lifestyle' },
  { key: 'editorial', label: 'Editorial' },
  { key: 'reels',     label: 'Reels' },
]

const themes = ['default', 'summer', 'winter', 'luxury', 'streetwear', 'eid', 'black friday', 'minimal', 'beach']

const totalCost = computed(() => selectedTypes.value.length)

function toggleType(key) {
  const idx = selectedTypes.value.indexOf(key)
  if (idx >= 0) selectedTypes.value.splice(idx, 1)
  else selectedTypes.value.push(key)
}

function toggleSubtype(key) {
  const idx = selectedSubtypes.value.indexOf(key)
  if (idx >= 0) selectedSubtypes.value.splice(idx, 1)
  else selectedSubtypes.value.push(key)
}

async function launchCampaign() {
  generating.value = true
  try {
    const campaign = await campaignStore.create({
      name:        `${props.garment.category ?? 'Campaign'} — ${selectedTheme.value}`,
      theme:       selectedTheme.value === 'default' ? null : selectedTheme.value,
      garment_ids: [props.garment.id],
    })
    await campaignStore.generate(campaign.id, {
      types:    selectedTypes.value,
      subtypes: selectedSubtypes.value,
    })
    campaignId.value = campaign.id
  } catch (err) {
    console.error(err)
  } finally {
    generating.value = false
  }
}
</script>
