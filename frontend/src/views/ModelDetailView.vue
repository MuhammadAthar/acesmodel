<template>
  <div class="min-h-screen" style="background:#f7f4ef; font-family: 'Plus Jakarta Sans', sans-serif;">

    <!-- Top nav bar -->
    <nav class="sticky top-0 z-10 bg-[#f7f4ef]/90 backdrop-blur-sm border-b border-[#e0d8cf] px-6 py-4 flex items-center justify-between">
      <a href="/#models"
        class="flex items-center gap-2 text-sm font-medium text-[#6b5c47] hover:text-[#3a2e22] transition-colors">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Back to Models
      </a>
      <a href="/" class="text-xl font-bold tracking-tight text-[#3a2e22]">modelfy</a>
      <a href="/register"
        class="px-4 py-2 rounded-full bg-[#3a2e22] text-[#f7f4ef] text-sm font-semibold hover:bg-[#5a4637] transition-colors">
        Book Model
      </a>
    </nav>

    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-40">
      <div class="w-10 h-10 rounded-full border-2 border-[#3a2e22]/20 border-t-[#3a2e22] animate-spin"></div>
    </div>

    <!-- Not found -->
    <div v-else-if="!persona" class="flex flex-col items-center justify-center py-40 text-center px-4">
      <i class="fa-solid fa-person-circle-question text-6xl text-[#c5b8a8] mb-4"></i>
      <h2 class="text-[#3a2e22] text-2xl font-bold mb-2">Model not found</h2>
      <p class="text-[#6b5c47] mb-6">This model profile doesn't exist or has been removed.</p>
      <a href="/#models" class="px-6 py-3 rounded-full bg-[#3a2e22] text-[#f7f4ef] font-semibold hover:bg-[#5a4637] transition-colors">
        View All Models
      </a>
    </div>

    <!-- Persona detail -->
    <div v-else class="max-w-6xl mx-auto px-4 sm:px-6 py-10">

      <!-- Hero section -->
      <div class="flex flex-col md:flex-row gap-8 md:gap-12 mb-12">

        <!-- Avatar -->
        <div class="flex-shrink-0 w-full md:w-72">
          <div class="aspect-[3/4] rounded-3xl overflow-hidden bg-[#e0d8cf] shadow-xl">
            <img v-if="persona.avatar_url" :src="persona.avatar_url" :alt="persona.name"
              class="w-full h-full object-cover object-top" />
            <div v-else class="w-full h-full flex items-center justify-center">
              <i class="fa-solid fa-person-dress text-5xl text-[#c5b8a8]"></i>
            </div>
          </div>
        </div>

        <!-- Info -->
        <div class="flex-1 py-2">
          <!-- Name + gender badge -->
          <div class="flex items-start gap-3 mb-3">
            <h1 class="text-4xl font-bold text-[#3a2e22] leading-tight">{{ persona.name }}</h1>
            <span class="mt-2 flex-shrink-0 px-3 py-1 rounded-full text-xs font-semibold border"
              :class="genderBadge(persona.gender)">
              {{ genderLabel(persona.gender) }}
            </span>
          </div>

          <!-- Quick stats row -->
          <div class="flex flex-wrap gap-4 mb-6">
            <div v-if="persona.age" class="flex items-center gap-1.5 text-[#6b5c47] text-sm">
              <i class="fa-solid fa-cake-candles text-[#c5b8a8] text-xs"></i>
              {{ persona.age }} years old
            </div>
            <div v-if="persona.nationality" class="flex items-center gap-1.5 text-[#6b5c47] text-sm">
              <i class="fa-solid fa-flag text-[#c5b8a8] text-xs"></i>
              {{ persona.nationality }}
            </div>
            <div v-if="persona.ethnicity" class="flex items-center gap-1.5 text-[#6b5c47] text-sm">
              <i class="fa-solid fa-earth-asia text-[#c5b8a8] text-xs"></i>
              {{ persona.ethnicity }}
            </div>
            <div v-if="persona.body_type" class="flex items-center gap-1.5 text-[#6b5c47] text-sm">
              <i class="fa-solid fa-ruler-vertical text-[#c5b8a8] text-xs"></i>
              {{ persona.body_type.replace('_', ' ') }}
            </div>
            <div v-if="persona.skin_tone" class="flex items-center gap-1.5 text-[#6b5c47] text-sm">
              <i class="fa-solid fa-droplet text-[#c5b8a8] text-xs"></i>
              {{ persona.skin_tone }} skin
            </div>
            <div v-if="persona.hair" class="flex items-center gap-1.5 text-[#6b5c47] text-sm">
              <i class="fa-solid fa-scissors text-[#c5b8a8] text-xs"></i>
              {{ persona.hair }}
            </div>
          </div>

          <!-- Best For -->
          <div v-if="persona.best_for" class="mb-6 p-4 rounded-2xl" style="background:#ede8e0; border:1px solid #d8d0c4;">
            <p class="text-xs font-semibold uppercase tracking-widest text-[#9b8a78] mb-1.5">Best For</p>
            <p class="text-[#3a2e22] text-sm leading-relaxed">{{ persona.best_for }}</p>
          </div>

          <!-- Poses count -->
          <div class="flex items-center gap-2 text-sm text-[#9b8a78]">
            <i class="fa-solid fa-images"></i>
            <span>{{ persona.poses?.length || 0 }} pose{{ persona.poses?.length !== 1 ? 's' : '' }} available</span>
          </div>

          <!-- CTA -->
          <div class="mt-8 flex flex-wrap gap-3">
            <a href="/register"
              class="px-8 py-3 rounded-full bg-[#3a2e22] text-[#f7f4ef] font-semibold hover:bg-[#5a4637] transition-colors shadow-md">
              Book This Model
            </a>
            <a href="/#models"
              class="px-6 py-3 rounded-full border-2 border-[#3a2e22]/30 text-[#3a2e22] font-semibold hover:border-[#3a2e22] transition-colors">
              Browse Others
            </a>
          </div>
        </div>
      </div>

      <!-- Poses section -->
      <div v-if="persona.poses?.length">
        <div class="flex items-center gap-3 mb-6">
          <h2 class="text-2xl font-bold text-[#3a2e22]">All Poses</h2>
          <div class="flex-1 h-px bg-[#d8d0c4]"></div>
          <span class="text-sm text-[#9b8a78]">{{ persona.poses.length }} photos</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
          <div v-for="pose in persona.poses" :key="pose.id"
            class="group relative rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-shadow cursor-pointer"
            style="background:#e0d8cf;"
            @click="openLightbox(pose)">
            <div class="aspect-[3/4]">
              <img :src="pose.file_path" :alt="pose.pose_label"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" />
            </div>
            <!-- Label overlay -->
            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
              <p class="text-white text-xs font-semibold">{{ pose.pose_label }}</p>
            </div>
            <!-- Visible label badge -->
            <div class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[10px] font-medium"
              style="background:rgba(247,244,239,0.9); color:#3a2e22;">
              {{ pose.pose_label }}
            </div>
          </div>
        </div>
      </div>

      <!-- No poses empty state -->
      <div v-else class="flex flex-col items-center justify-center py-16 text-center rounded-3xl" style="background:#ede8e0;">
        <i class="fa-solid fa-images text-4xl text-[#c5b8a8] mb-3"></i>
        <p class="text-[#6b5c47] font-medium">Poses coming soon</p>
        <p class="text-[#9b8a78] text-sm mt-1">This model's photo portfolio is being updated.</p>
      </div>

    </div>

    <!-- Lightbox -->
    <Transition name="fade">
      <div v-if="lightboxPose" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90"
        @click.self="lightboxPose = null">
        <button @click="lightboxPose = null"
          class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
        <div class="max-h-[90vh] max-w-[90vw]">
          <img :src="lightboxPose.file_path" :alt="lightboxPose.pose_label"
            class="max-h-[85vh] max-w-[85vw] object-contain rounded-2xl shadow-2xl" />
          <p class="text-white/70 text-sm text-center mt-3">{{ lightboxPose.pose_label }}</p>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route       = useRoute()
const persona     = ref(null)
const loading     = ref(true)
const lightboxPose = ref(null)

function genderLabel(g) {
  return {
    female: 'Female', male: 'Male', girl: 'Girl', boy: 'Boy',
    child: 'Child', non_binary: 'Non-Binary',
  }[g] || g
}

function genderBadge(g) {
  return {
    female:     'border-pink-300 text-pink-600 bg-pink-50',
    male:       'border-blue-300 text-blue-600 bg-blue-50',
    girl:       'border-pink-300 text-pink-500 bg-pink-50',
    boy:        'border-blue-300 text-blue-500 bg-blue-50',
    child:      'border-yellow-300 text-yellow-600 bg-yellow-50',
    non_binary: 'border-purple-300 text-purple-600 bg-purple-50',
  }[g] || 'border-gray-300 text-gray-600 bg-gray-50'
}

function openLightbox(pose) {
  lightboxPose.value = pose
}

onMounted(async () => {
  try {
    const res = await axios.get(`/api/public/model-personas/${route.params.id}`)
    persona.value = res.data
  } catch {
    persona.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
