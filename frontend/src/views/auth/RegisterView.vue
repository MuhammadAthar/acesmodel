<template>
  <div class="min-h-screen bg-[#0a0a0a] flex">

    <!-- ── LEFT: Full-bleed hero image ─────────────────────── -->
    <div class="hidden lg:block lg:w-[58%] relative overflow-hidden">
      <img
        src="/images/hero-image.png"
        alt=""
        class="absolute inset-0 w-full h-full object-cover object-center"
      />
      <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-black/50"></div>

      <!-- Top: Logo -->
      <div class="absolute top-8 left-10 z-10 flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-gradient-accent flex items-center justify-center text-canvas font-bold text-sm">A</div>
        <span class="text-white font-semibold tracking-widest text-sm uppercase">Aces Model</span>
      </div>

      <!-- Bottom: Editorial text -->
      <div class="absolute bottom-10 left-10 right-10 z-10">
        <div class="w-8 h-0.5 bg-accent mb-5"></div>
        <h2 class="text-white font-bold text-5xl xl:text-6xl leading-tight tracking-tight mb-4">
          Your Brand.<br />Elevated.
        </h2>
        <p class="text-white/60 text-sm leading-relaxed max-w-xs">
          Join thousands of fashion brands generating studio-quality content with AI — in seconds.
        </p>
      </div>
    </div>

    <!-- ── RIGHT: Register form ──────────────────────────────── -->
    <div class="w-full lg:w-[42%] flex items-center justify-center px-8 py-12 bg-[#0a0a0a]">
      <div class="w-full max-w-sm">

        <!-- Mobile logo -->
        <div class="flex items-center gap-3 mb-10 lg:hidden">
          <div class="w-8 h-8 rounded-full bg-gradient-accent flex items-center justify-center text-canvas font-bold text-sm">A</div>
          <span class="text-white font-semibold tracking-widest text-sm uppercase">Aces Model</span>
        </div>

        <h1 class="text-3xl font-bold text-white mb-1">Create your studio</h1>
        <p class="text-[#666] text-sm mb-8">Start with 10 free generations. No credit card needed.</p>

        <!-- Error -->
        <div v-if="error" class="mb-5 p-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-400 text-sm">
          {{ error }}
        </div>

        <form @submit.prevent="handleRegister" class="space-y-5">

          <!-- Full Name -->
          <div>
            <label class="block text-xs font-medium text-[#888] mb-2">Full Name</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#555]">
                <i class="fa-solid fa-user text-sm"></i>
              </span>
              <input v-model="name" type="text" required autocomplete="name"
                class="w-full bg-[#111] border border-[#222] rounded-xl pl-10 pr-4 py-3 text-white text-sm placeholder-[#444] focus:outline-none focus:border-[#444] transition-colors"
                placeholder="Your full name" />
            </div>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-xs font-medium text-[#888] mb-2">Email Address</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#555]">
                <i class="fa-solid fa-envelope text-sm"></i>
              </span>
              <input v-model="email" type="email" required autocomplete="email"
                class="w-full bg-[#111] border border-[#222] rounded-xl pl-10 pr-4 py-3 text-white text-sm placeholder-[#444] focus:outline-none focus:border-[#444] transition-colors"
                placeholder="name@example.com" />
            </div>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-xs font-medium text-[#888] mb-2">Password</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#555]">
                <i class="fa-solid fa-lock text-sm"></i>
              </span>
              <input v-model="password" type="password" required autocomplete="new-password"
                class="w-full bg-[#111] border border-[#222] rounded-xl pl-10 pr-4 py-3 text-white text-sm placeholder-[#444] focus:outline-none focus:border-[#444] transition-colors"
                placeholder="Min. 8 characters" />
            </div>
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-xs font-medium text-[#888] mb-2">Confirm Password</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#555]">
                <i class="fa-solid fa-lock text-sm"></i>
              </span>
              <input v-model="passwordConfirm" type="password" required autocomplete="new-password"
                class="w-full bg-[#111] border border-[#222] rounded-xl pl-10 pr-4 py-3 text-white text-sm placeholder-[#444] focus:outline-none focus:border-[#444] transition-colors"
                placeholder="••••••••" />
            </div>
          </div>

          <!-- Submit -->
          <button type="submit" :disabled="loading"
            class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl font-semibold text-sm bg-accent text-canvas hover:bg-accent-dim transition-colors disabled:opacity-50 mt-2">
            <i v-if="loading" class="fa-solid fa-spinner fa-spin text-sm"></i>
            {{ loading ? 'Creating studio...' : 'Start for free' }}
            <i v-if="!loading" class="fa-solid fa-arrow-right text-sm"></i>
          </button>
        </form>

        <p class="text-center text-[#555] text-sm mt-6">
          Already have an account?
          <RouterLink to="/login" class="text-white font-semibold hover:text-accent transition-colors ml-1">Sign in</RouterLink>
        </p>

        <p class="text-center text-[#444] text-xs mt-8 leading-relaxed">
          By continuing, I acknowledge the
          <a href="#" class="text-[#666] underline hover:text-white transition-colors">Privacy Policy</a>
          and agree to the
          <a href="#" class="text-[#666] underline hover:text-white transition-colors">Terms of Use</a>
        </p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth           = useAuthStore()
const router         = useRouter()
const name           = ref('')
const email          = ref('')
const password       = ref('')
const passwordConfirm = ref('')
const loading        = ref(false)
const error          = ref('')

async function handleRegister() {
  if (password.value !== passwordConfirm.value) {
    error.value = 'Passwords do not match.'
    return
  }
  loading.value = true
  error.value   = ''
  try {
    await auth.register(name.value, email.value, password.value, passwordConfirm.value)
    router.push('/')
  } catch (err) {
    const errs = err.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(' ') : (err.response?.data?.message ?? 'Registration failed.')
  } finally {
    loading.value = false
  }
}
</script>
