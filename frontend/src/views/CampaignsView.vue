<template>
  <div class="max-w-screen-2xl mx-auto px-6 py-10">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="heading-lg text-white">Campaigns</h1>
        <p class="text-muted mt-1">Your fashion marketing campaigns</p>
      </div>
      <RouterLink to="/" class="btn-accent">
        <i class="fa-solid fa-plus"></i>
        New Campaign
      </RouterLink>
    </div>

    <!-- Loading skeletons -->
    <div v-if="store.loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div v-for="n in 6" :key="n" class="h-64 rounded-3xl skeleton"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!store.campaigns.length" class="text-center py-32">
      <div class="w-20 h-20 rounded-3xl glass border border-border flex items-center justify-center mx-auto mb-6">
        <i class="fa-solid fa-camera text-3xl text-muted"></i>
      </div>
      <h3 class="text-xl font-medium text-white mb-2">No campaigns yet</h3>
      <p class="text-muted mb-6">Upload a garment in Studio to generate your first campaign.</p>
      <RouterLink to="/" class="btn-accent">Go to Studio</RouterLink>
    </div>

    <!-- Campaign grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <RouterLink
        v-for="campaign in store.campaigns" :key="campaign.id"
        :to="`/studio/campaigns/${campaign.id}`"
        class="asset-card glass group"
      >
        <!-- Status bar -->
        <div class="absolute top-3 right-3 z-10">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
            :class="{
              'bg-green-500/20 text-green-400 border border-green-500/30': campaign.status === 'ready',
              'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': campaign.status === 'generating',
              'bg-white/5 text-muted border border-border': campaign.status === 'draft',
            }"
          >
            <span v-if="campaign.status === 'generating'" class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse-soft"></span>
            {{ campaign.status }}
          </span>
        </div>

        <!-- Thumbnail mosaic -->
        <div class="h-48 bg-surface relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-dark opacity-50"></div>
          <div class="absolute bottom-0 left-0 right-0 p-4">
            <div v-if="campaign.brand" class="text-xs text-muted mb-1">{{ campaign.brand.name }}</div>
            <h3 class="font-medium text-white text-lg leading-tight">{{ campaign.name }}</h3>
            <p v-if="campaign.theme" class="text-accent text-xs mt-1 capitalize">{{ campaign.theme }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-4 flex items-center justify-between border-t border-border">
          <span class="text-xs text-muted">{{ campaign.generated_assets_count ?? 0 }} assets</span>
          <span class="text-xs text-muted">{{ formatDate(campaign.created_at) }}</span>
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useCampaignStore } from '@/stores/campaign'

const store = useCampaignStore()
onMounted(() => store.fetchAll())

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
</script>
