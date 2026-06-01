<template>
  <div class="max-w-2xl mx-auto px-6 py-10">
    <div class="mb-10">
      <h1 class="heading-lg text-white">Settings</h1>
      <p class="text-muted mt-1">Manage your account and preferences</p>
    </div>

    <!-- Profile section -->
    <div class="glass rounded-3xl p-7 border border-border mb-5">
      <h3 class="font-medium text-white mb-5">Profile</h3>
      <div class="flex items-start gap-5 mb-5">
        <!-- Avatar -->
        <div class="relative">
          <div class="w-16 h-16 rounded-full bg-accent-glow border border-accent/30 flex items-center justify-center overflow-hidden">
            <img v-if="auth.user?.avatar" :src="`/storage/${auth.user.avatar}`" class="w-full h-full object-cover" />
            <span v-else class="text-accent text-xl font-medium">{{ initials }}</span>
          </div>
          <button @click="avatarInput.click()" class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-surface border border-border flex items-center justify-center text-xs text-muted hover:text-white">
            <i class="fa-solid fa-pen text-xs"></i>
          </button>
          <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="uploadAvatar" />
        </div>
        <div class="flex-1">
          <p class="font-medium text-white">{{ auth.user?.name }}</p>
          <p class="text-muted text-sm">{{ auth.user?.email }}</p>
          <span class="tag mt-1.5 capitalize">{{ auth.plan }} plan · {{ auth.credits }} credits</span>
        </div>
      </div>

      <div v-if="profileMsg" class="mb-4 text-sm text-green-400">{{ profileMsg }}</div>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Name</label>
          <input v-model="profileForm.name" type="text" class="input-dark" />
        </div>
        <div>
          <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Email</label>
          <input v-model="profileForm.email" type="email" class="input-dark" />
        </div>
        <button @click="saveProfile" :disabled="savingProfile" class="btn-accent disabled:opacity-50">
          {{ savingProfile ? 'Saving...' : 'Save changes' }}
        </button>
      </div>
    </div>

    <!-- Password section -->
    <div class="glass rounded-3xl p-7 border border-border">
      <h3 class="font-medium text-white mb-5">Change Password</h3>

      <div v-if="pwMsg" class="mb-4 text-sm" :class="pwMsg.ok ? 'text-green-400' : 'text-red-400'">{{ pwMsg.text }}</div>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Current password</label>
          <input v-model="pwForm.current" type="password" class="input-dark" />
        </div>
        <div>
          <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">New password</label>
          <input v-model="pwForm.password" type="password" class="input-dark" />
        </div>
        <div>
          <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Confirm new password</label>
          <input v-model="pwForm.confirm" type="password" class="input-dark" />
        </div>
        <button @click="changePassword" :disabled="savingPw" class="btn-ghost disabled:opacity-50">
          {{ savingPw ? 'Updating...' : 'Update password' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/lib/api'

const auth        = useAuthStore()
const avatarInput = ref(null)
const profileMsg  = ref('')
const pwMsg       = ref(null)
const savingProfile = ref(false)
const savingPw    = ref(false)

const profileForm = ref({ name: '', email: '' })
const pwForm      = ref({ current: '', password: '', confirm: '' })

const initials = computed(() => (auth.user?.name ?? 'U').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2))

onMounted(() => {
  profileForm.value.name  = auth.user?.name  ?? ''
  profileForm.value.email = auth.user?.email ?? ''
})

async function saveProfile() {
  savingProfile.value = true
  profileMsg.value = ''
  try {
    const { data } = await api.put('/user', profileForm.value)
    auth.user = data
    profileMsg.value = 'Profile updated!'
    setTimeout(() => { profileMsg.value = '' }, 3000)
  } finally { savingProfile.value = false }
}

async function changePassword() {
  if (pwForm.value.password !== pwForm.value.confirm) {
    pwMsg.value = { ok: false, text: 'Passwords do not match.' }
    return
  }
  savingPw.value = true
  pwMsg.value = null
  try {
    await api.put('/user/password', {
      current_password:      pwForm.value.current,
      password:              pwForm.value.password,
      password_confirmation: pwForm.value.confirm,
    })
    pwMsg.value = { ok: true, text: 'Password updated successfully.' }
    pwForm.value = { current: '', password: '', confirm: '' }
  } catch (err) {
    const errs = err.response?.data?.errors
    pwMsg.value = { ok: false, text: errs ? Object.values(errs).flat().join(' ') : 'Update failed.' }
  } finally { savingPw.value = false }
}

async function uploadAvatar(e) {
  const file = e.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('avatar', file)
  const { data } = await api.post('/user/avatar', fd)
  auth.user = data
}
</script>
