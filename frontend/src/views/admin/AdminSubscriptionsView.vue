<template>
  <div>
    <div class="flex items-center gap-3 mb-6">
      <select v-model="statusFilter" @change="fetchSubs"
        class="bg-[#111] border border-[#1e1e1e] rounded-lg px-3 py-2 text-sm text-[#888] focus:outline-none">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="cancelled">Cancelled</option>
        <option value="expired">Expired</option>
        <option value="pending">Pending</option>
      </select>
      <select v-model="planFilter" @change="fetchSubs"
        class="bg-[#111] border border-[#1e1e1e] rounded-lg px-3 py-2 text-sm text-[#888] focus:outline-none">
        <option value="">All Plans</option>
        <option v-for="p in plans" :key="p" :value="p" class="capitalize">{{ p }}</option>
      </select>
    </div>

    <div class="bg-[#111] border border-[#1e1e1e] rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[#1e1e1e]">
            <th class="text-left px-5 py-3 text-[#555] font-medium">Client</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Plan</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Gateway</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Status</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Period End</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="6" class="px-5 py-10 text-center text-[#555]"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading…</td></tr>
          <tr v-else-if="!subs.length"><td colspan="6" class="px-5 py-10 text-center text-[#555]">No subscriptions found.</td></tr>
          <tr v-for="s in subs" :key="s.id" class="border-b border-[#1a1a1a] hover:bg-[#141414] transition-colors">
            <td class="px-5 py-3">
              <p class="text-white">{{ s.user?.name }}</p>
              <p class="text-[#555] text-xs">{{ s.user?.email }}</p>
            </td>
            <td class="px-5 py-3 text-[#e8d5b7] capitalize">{{ s.plan }}</td>
            <td class="px-5 py-3 text-[#888] capitalize">{{ s.gateway }}</td>
            <td class="px-5 py-3">
              <span :class="statusClass(s.status)" class="text-xs px-2 py-1 rounded-full">{{ s.status }}</span>
            </td>
            <td class="px-5 py-3 text-[#555] text-xs">{{ s.current_period_end ? formatDate(s.current_period_end) : '—' }}</td>
            <td class="px-5 py-3">
              <select @change="updateStatus(s, $event.target.value)" :value="s.status"
                class="bg-[#0a0a0a] border border-[#1e1e1e] rounded px-2 py-1 text-xs text-[#888] focus:outline-none">
                <option value="active">active</option>
                <option value="cancelled">cancelled</option>
                <option value="expired">expired</option>
                <option value="pending">pending</option>
              </select>
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

const subs        = ref([])
const meta        = ref(null)
const loading     = ref(false)
const statusFilter= ref('')
const planFilter  = ref('')
const page        = ref(1)

const plans = ['free', 'starter', 'growth', 'agency', 'enterprise']

async function fetchSubs() {
  loading.value = true
  try {
    const res = await api.get('/admin/subscriptions', {
      params: { status: statusFilter.value, plan: planFilter.value, page: page.value }
    })
    subs.value = res.data.data
    meta.value = res.data.meta
  } finally {
    loading.value = false
  }
}

function goPage(p) { page.value = p; fetchSubs() }

async function updateStatus(sub, status) {
  await api.put(`/admin/subscriptions/${sub.id}`, { status })
  sub.status = status
}

function statusClass(s) {
  return {
    active:    'bg-[#1a2a1a] text-green-400',
    cancelled: 'bg-[#2a1a1a] text-red-400',
    expired:   'bg-[#2a2a1a] text-yellow-400',
    pending:   'bg-[#1a1a2a] text-blue-400',
  }[s] ?? 'bg-[#1e1e1e] text-[#888]'
}

function formatDate(dt) {
  return new Date(dt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(fetchSubs)
</script>
