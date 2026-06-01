<template>
  <div class="max-w-screen-xl mx-auto px-6 py-10">
    <div class="mb-10">
      <h1 class="heading-lg text-white">Analytics</h1>
      <p class="text-muted mt-1">Your content performance insights</p>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
      <div v-for="stat in stats" :key="stat.label" class="glass rounded-2xl p-5 border border-border">
        <p class="text-xs font-medium text-muted uppercase tracking-widest mb-2">{{ stat.label }}</p>
        <p class="text-3xl font-light text-white">{{ stat.value }}</p>
        <p v-if="stat.sub" class="text-xs text-muted mt-1">{{ stat.sub }}</p>
      </div>
    </div>

    <!-- Credits usage chart (simple bar) -->
    <div class="glass rounded-3xl p-6 border border-border mb-6">
      <h3 class="font-medium text-white mb-4">Credits Used This Month</h3>
      <div class="flex items-end gap-2 h-32">
        <div v-for="(bar, i) in creditBars" :key="i"
          class="flex-1 rounded-t-lg bg-gradient-to-t from-accent/30 to-accent/60 transition-all duration-700"
          :style="`height: ${bar.pct}%`"
          :title="`Day ${i+1}: ${bar.used} credits`">
        </div>
      </div>
      <div class="flex justify-between text-xs text-muted mt-2">
        <span>1</span><span>7</span><span>14</span><span>21</span><span>Today</span>
      </div>
    </div>

    <!-- Coming soon placeholder for deeper analytics -->
    <div class="glass rounded-3xl p-10 border border-border text-center">
      <i class="fa-solid fa-chart-bar text-4xl mb-4 text-muted"></i>
      <h3 class="text-xl font-medium text-white mb-2">Full Analytics Coming Soon</h3>
      <p class="text-muted max-w-md mx-auto">
        Track model performance, best backgrounds, CTR predictions, and AI-powered content recommendations.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

const auth  = useAuthStore()
const stats = ref([
  { label: 'Total Assets', value: '—', sub: 'All time' },
  { label: 'Campaigns',    value: '—', sub: 'Created' },
  { label: 'Credits Used', value: '—', sub: 'This month' },
  { label: 'Credits Left', value: auth.credits, sub: `${auth.plan} plan` },
])

// Mock daily credit bars for visualization
const creditBars = Array.from({ length: 28 }, () => ({
  pct:  Math.random() * 80 + 5,
  used: Math.floor(Math.random() * 20),
}))

onMounted(async () => {
  try {
    const [{ data: campaigns }, { data: assets }] = await Promise.all([
      api.get('/campaigns'),
      api.get('/assets'),
    ])
    stats.value[0].value = (assets.data?.total ?? assets.length) || '0'
    stats.value[1].value = (campaigns.data?.total ?? campaigns.length) || '0'
  } catch {}
})
</script>
