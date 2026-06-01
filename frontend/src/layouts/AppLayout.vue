<template>
  <div class="min-h-screen flex flex-col bg-canvas">
    <!-- Top Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 border-b border-border/50" style="background: rgba(10,10,10,0.85); backdrop-filter: blur(20px);">
      <div class="max-w-screen-2xl mx-auto px-6 h-16 flex items-center justify-between">
        <!-- Logo -->
        <RouterLink to="/studio" class="flex items-center gap-3 group">
          <div class="w-8 h-8 rounded-full bg-gradient-accent flex items-center justify-center text-canvas font-bold text-sm">A</div>
          <span class="font-display text-lg font-light tracking-widest text-white group-hover:text-accent transition-colors">ACES MODEL</span>
        </RouterLink>

        <!-- Nav Links -->
        <nav class="hidden md:flex items-center gap-8">
          <RouterLink v-for="link in navLinks" :key="link.to"
            :to="link.to"
            class="nav-link"
            :class="{ active: isActive(link.to) }"
          >
            {{ link.label }}
          </RouterLink>
        </nav>

        <!-- Right side: credits + user -->
        <div class="flex items-center gap-4">
          <!-- Credits indicator -->
          <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full glass border border-border">
            <div class="w-2 h-2 rounded-full bg-accent animate-pulse-soft"></div>
            <span class="text-xs text-soft font-medium">{{ auth.credits }} credits</span>
          </div>

          <!-- Upgrade button -->
          <RouterLink v-if="auth.plan === 'free'" to="/studio/billing" class="btn-accent text-xs py-2 px-4">
            Upgrade
          </RouterLink>

          <!-- User avatar -->
          <div class="relative" ref="userMenuRef">
            <button @click="userMenuOpen = !userMenuOpen"
              class="w-9 h-9 rounded-full glass border border-border flex items-center justify-center text-sm font-medium text-accent hover:border-accent/40 transition-colors">
              {{ userInitials }}
            </button>
            <Transition name="fade-up">
              <div v-if="userMenuOpen"
                class="absolute right-0 top-12 w-48 glass border border-border rounded-2xl overflow-hidden shadow-2xl">
                <div class="px-4 py-3 border-b border-border">
                  <p class="text-sm font-medium text-white truncate">{{ auth.user?.name }}</p>
                  <p class="text-xs text-muted truncate">{{ auth.user?.email }}</p>
                </div>
                <nav class="py-1">
                  <RouterLink to="/studio/settings" @click="userMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-soft hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fa-solid fa-gear w-4 text-center"></i> Settings
                  </RouterLink>
                  <RouterLink to="/studio/billing" @click="userMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-soft hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fa-solid fa-credit-card w-4 text-center"></i> Billing
                  </RouterLink>
                  <button @click="handleLogout"
                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-soft hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Sign out
                  </button>
                </nav>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="flex-1 pt-16">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { onClickOutside } from '@vueuse/core'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()

const userMenuOpen = ref(false)
const userMenuRef  = ref(null)

onClickOutside(userMenuRef, () => { userMenuOpen.value = false })

const navLinks = [
  { label: 'Studio',    to: '/studio' },
  { label: 'Campaigns', to: '/studio/campaigns' },
  { label: 'Assets',    to: '/studio/assets' },
  { label: 'Brand DNA', to: '/studio/brand-dna' },
  { label: 'Analytics', to: '/studio/analytics' },
]

const userInitials = computed(() => {
  const name = auth.user?.name ?? ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'U'
})

function isActive(to) {
  if (to === '/studio') return route.path === '/studio'
  return route.path.startsWith(to)
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.fade-up-enter-active, .fade-up-leave-active { transition: all 0.2s ease; }
.fade-up-enter-from { opacity: 0; transform: translateY(-8px); }
.fade-up-leave-to   { opacity: 0; transform: translateY(-8px); }
</style>
