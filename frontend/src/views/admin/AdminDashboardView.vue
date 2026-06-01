<template>
  <div>
    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
      <div v-for="stat in stats" :key="stat.label"
        class="bg-[#111] border border-[#1e1e1e] rounded-xl p-5">
        <i :class="stat.icon" class="text-[#e8d5b7] mb-3 text-lg"></i>
        <p class="text-2xl font-semibold text-white">{{ stat.value }}</p>
        <p class="text-[#555] text-xs mt-1">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Plan breakdown + Recent users -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Plan breakdown -->
      <div class="bg-[#111] border border-[#1e1e1e] rounded-xl p-6">
        <h3 class="text-white text-sm font-medium mb-4">Plan Breakdown</h3>
        <div class="space-y-3">
          <div v-for="(count, plan) in data?.plan_breakdown" :key="plan"
            class="flex items-center justify-between">
            <span class="text-[#888] text-sm capitalize">{{ plan }}</span>
            <span class="text-white text-sm font-medium">{{ count }}</span>
          </div>
          <p v-if="!data?.plan_breakdown" class="text-[#555] text-sm">No data</p>
        </div>
      </div>

      <!-- Recent users -->
      <div class="lg:col-span-2 bg-[#111] border border-[#1e1e1e] rounded-xl p-6">
        <h3 class="text-white text-sm font-medium mb-4">Recent Signups</h3>
        <div class="space-y-3">
          <div v-for="user in data?.recent_users" :key="user.id"
            class="flex items-center justify-between py-2 border-b border-[#1e1e1e] last:border-0">
            <div>
              <p class="text-white text-sm">{{ user.name }}</p>
              <p class="text-[#555] text-xs">{{ user.email }}</p>
            </div>
            <div class="text-right">
              <span class="text-xs px-2 py-1 rounded-full border border-[#2a2a2a] text-[#888] capitalize">{{ user.plan }}</span>
            </div>
          </div>
          <p v-if="!data?.recent_users?.length" class="text-[#555] text-sm">No users yet.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/api'

const data = ref(null)

const stats = computed(() => [
  { label: 'Total Clients',      value: data.value?.total_users ?? '—',          icon: 'fa-solid fa-users' },
  { label: 'New This Month',     value: data.value?.new_users_this_month ?? '—', icon: 'fa-solid fa-user-plus' },
  { label: 'Active Subs',        value: data.value?.active_subscriptions ?? '—', icon: 'fa-solid fa-credit-card' },
  { label: 'AI Models',          value: data.value?.total_ai_models ?? '—',      icon: 'fa-solid fa-wand-magic-sparkles' },
  { label: 'Assets Generated',   value: data.value?.total_assets ?? '—',         icon: 'fa-solid fa-images' },
  { label: 'Campaigns',          value: data.value?.total_campaigns ?? '—',      icon: 'fa-solid fa-camera' },
])

onMounted(async () => {
  const res = await api.get('/admin/stats')
  data.value = res.data
})
</script>
