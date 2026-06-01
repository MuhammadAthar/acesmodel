<template>
  <div>
    <!-- Toolbar -->
    <div class="flex items-center gap-3 mb-6">
      <div class="relative flex-1 max-w-xs">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[#555] text-sm"></i>
        <input v-model="search" @input="debouncedFetch" placeholder="Search name or email…"
          class="w-full bg-[#111] border border-[#1e1e1e] rounded-lg pl-9 pr-4 py-2 text-sm text-white placeholder-[#555] focus:outline-none focus:border-[#333]" />
      </div>
      <select v-model="planFilter" @change="fetchUsers"
        class="bg-[#111] border border-[#1e1e1e] rounded-lg px-3 py-2 text-sm text-[#888] focus:outline-none">
        <option value="">All Plans</option>
        <option v-for="p in plans" :key="p" :value="p" class="capitalize">{{ p }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-[#111] border border-[#1e1e1e] rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[#1e1e1e]">
            <th class="text-left px-5 py-3 text-[#555] font-medium">Name</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Email</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Plan</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Credits</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Campaigns</th>
            <th class="text-left px-5 py-3 text-[#555] font-medium">Joined</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="7" class="px-5 py-10 text-center text-[#555]"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading…</td></tr>
          <tr v-else-if="!users.length"><td colspan="7" class="px-5 py-10 text-center text-[#555]">No users found.</td></tr>
          <tr v-for="user in users" :key="user.id" class="border-b border-[#1a1a1a] hover:bg-[#141414] transition-colors">
            <td class="px-5 py-3 text-white font-medium">{{ user.name }}</td>
            <td class="px-5 py-3 text-[#888]">{{ user.email }}</td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-1 rounded-full border border-[#2a2a2a] text-[#e8d5b7] capitalize">{{ user.plan }}</span>
            </td>
            <td class="px-5 py-3 text-[#888]">{{ user.credits }}</td>
            <td class="px-5 py-3 text-[#888]">{{ user.campaigns_count }}</td>
            <td class="px-5 py-3 text-[#555] text-xs">{{ formatDate(user.created_at) }}</td>
            <td class="px-5 py-3">
              <button @click="openEdit(user)" class="text-[#555] hover:text-white transition-colors text-sm px-2">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button @click="deleteUser(user)" class="text-[#555] hover:text-red-400 transition-colors text-sm px-2 ml-1">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
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

    <!-- Edit Modal -->
    <Transition name="fade">
      <div v-if="editing" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
        <div class="bg-[#111] border border-[#1e1e1e] rounded-2xl w-full max-w-md p-6">
          <h3 class="text-white font-medium mb-5">Edit Client — {{ editing.name }}</h3>
          <div class="space-y-4">
            <div>
              <label class="text-[#555] text-xs mb-1 block">Name</label>
              <input v-model="form.name" class="admin-input" />
            </div>
            <div>
              <label class="text-[#555] text-xs mb-1 block">Email</label>
              <input v-model="form.email" type="email" class="admin-input" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-[#555] text-xs mb-1 block">Plan</label>
                <select v-model="form.plan" class="admin-input">
                  <option v-for="p in plans" :key="p" :value="p" class="capitalize">{{ p }}</option>
                </select>
              </div>
              <div>
                <label class="text-[#555] text-xs mb-1 block">Credits</label>
                <input v-model.number="form.credits" type="number" min="0" class="admin-input" />
              </div>
            </div>
            <div>
              <label class="text-[#555] text-xs mb-1 block">New Password <span class="text-[#444]">(leave blank to keep)</span></label>
              <input v-model="form.password" type="password" placeholder="••••••••" class="admin-input" />
            </div>
          </div>
          <div class="flex gap-3 mt-6">
            <button @click="saveEdit" :disabled="saving"
              class="flex-1 bg-[#e8d5b7] text-[#0a0a0a] rounded-lg py-2.5 text-sm font-semibold hover:bg-[#d4be99] transition-colors disabled:opacity-50">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin mr-2"></i>Save
            </button>
            <button @click="editing = null" class="flex-1 border border-[#2a2a2a] text-[#888] rounded-lg py-2.5 text-sm hover:text-white transition-colors">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'

const users   = ref([])
const meta    = ref(null)
const loading = ref(false)
const search  = ref('')
const planFilter = ref('')
const editing = ref(null)
const form    = ref({})
const saving  = ref(false)
const page    = ref(1)

const plans = ['free', 'starter', 'growth', 'agency', 'enterprise']

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; fetchUsers() }, 400)
}

async function fetchUsers() {
  loading.value = true
  try {
    const res = await api.get('/admin/users', {
      params: { search: search.value, plan: planFilter.value, page: page.value }
    })
    users.value = res.data.data
    meta.value  = res.data.meta
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  page.value = p
  fetchUsers()
}

function openEdit(user) {
  editing.value = user
  form.value = { name: user.name, email: user.email, plan: user.plan, credits: user.credits, password: '' }
}

async function saveEdit() {
  saving.value = true
  try {
    const payload = { name: form.value.name, email: form.value.email, plan: form.value.plan, credits: form.value.credits }
    await api.put(`/admin/users/${editing.value.id}`, payload)
    if (form.value.password) {
      await api.put(`/admin/users/${editing.value.id}/password`, { password: form.value.password })
    }
    editing.value = null
    fetchUsers()
  } finally {
    saving.value = false
  }
}

async function deleteUser(user) {
  if (!confirm(`Delete ${user.name}? This cannot be undone.`)) return
  await api.delete(`/admin/users/${user.id}`)
  fetchUsers()
}

function formatDate(dt) {
  return new Date(dt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(fetchUsers)
</script>

<style scoped>
.admin-input {
  @apply w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-[#333];
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
