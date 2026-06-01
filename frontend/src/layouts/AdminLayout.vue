<template>
  <div class="min-h-screen bg-[#0a0a0a] flex">

    <!-- Sidebar -->
    <aside class="w-64 border-r border-[#1e1e1e] flex flex-col flex-shrink-0">
      <div class="h-16 flex items-center px-6 border-b border-[#1e1e1e]">
        <span class="font-display text-lg font-semibold text-white tracking-tight">Aces <span class="text-[#e8d5b7]">Admin</span></span>
      </div>

      <nav class="flex-1 py-6 px-3 space-y-1">
        <RouterLink v-for="item in nav" :key="item.to" :to="item.to"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors"
          :class="$route.path.startsWith(item.to) ? 'bg-[#1a1a1a] text-white' : 'text-[#888] hover:text-white hover:bg-[#141414]'">
          <i :class="item.icon" class="w-4 text-center"></i>
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="p-4 border-t border-[#1e1e1e]">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full bg-[#e8d5b7] flex items-center justify-center text-[#0a0a0a] text-xs font-bold">
            {{ auth.user?.name?.[0]?.toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="text-white text-xs font-medium truncate">{{ auth.user?.name }}</p>
            <p class="text-[#555] text-[11px] truncate">Super Admin</p>
          </div>
        </div>
        <button @click="handleLogout"
          class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-[#888] hover:text-white hover:bg-[#141414] transition-colors">
          <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
          Sign out
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
      <header class="h-16 border-b border-[#1e1e1e] flex items-center px-8 flex-shrink-0">
        <h1 class="text-white text-sm font-medium">{{ pageTitle }}</h1>
      </header>
      <main class="flex-1 overflow-auto p-8">
        <RouterView />
      </main>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const nav = [
  { to: '/admin/dashboard',      label: 'Dashboard',      icon: 'fa-solid fa-chart-pie' },
  { to: '/admin/users',          label: 'Clients',         icon: 'fa-solid fa-users' },
  { to: '/admin/models',         label: 'Client Models',   icon: 'fa-solid fa-person-dress' },
  { to: '/admin/model-personas', label: 'Model Personas',  icon: 'fa-solid fa-people-group' },
  { to: '/admin/subscriptions',  label: 'Subscriptions',   icon: 'fa-solid fa-credit-card' },
  { to: '/admin/ai-config',      label: 'AI Providers',    icon: 'fa-solid fa-sliders' },
]

const titles = {
  '/admin/dashboard':      'Dashboard',
  '/admin/users':          'Clients',
  '/admin/models':         'Client Models',
  '/admin/model-personas': 'Model Personas',
  '/admin/subscriptions':  'Subscriptions',
  '/admin/ai-config':      'AI Providers',
}

const pageTitle = computed(() => titles[route.path] ?? 'Admin')

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
