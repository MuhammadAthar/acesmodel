<template>
  <div class="max-w-screen-xl mx-auto px-6 py-10">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="heading-lg text-white">AI Model Marketplace</h1>
        <p class="text-muted mt-1">Create and save custom AI models for your campaigns</p>
      </div>
      <button @click="showCreate = true" class="btn-accent">+ Create Model</button>
    </div>

    <!-- Create model panel -->
    <Transition name="fade-up">
      <div v-if="showCreate" class="glass rounded-3xl p-6 border border-border mb-8">
        <h3 class="font-medium text-white mb-5">New AI Model</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Name</label>
            <input v-model="form.name" type="text" class="input-dark" placeholder="e.g. Aisha" />
          </div>
          <div>
            <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Ethnicity</label>
            <select v-model="form.ethnicity" class="input-dark">
              <option value="">Select...</option>
              <option v-for="e in ethnicities" :key="e">{{ e }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Gender</label>
            <select v-model="form.gender" class="input-dark">
              <option value="female">Female</option>
              <option value="male">Male</option>
              <option value="non-binary">Non-binary</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Age range</label>
            <select v-model="form.age_range" class="input-dark">
              <option v-for="a in ageRanges" :key="a">{{ a }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Body type</label>
            <select v-model="form.body_type" class="input-dark">
              <option v-for="b in bodyTypes" :key="b">{{ b }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Skin tone</label>
            <select v-model="form.skin_tone" class="input-dark">
              <option v-for="s in skinTones" :key="s">{{ s }}</option>
            </select>
          </div>
        </div>
        <div class="flex items-center gap-3 mt-5">
          <button @click="createModel" :disabled="!form.name || saving" class="btn-accent disabled:opacity-50">
            {{ saving ? 'Saving...' : 'Create Model' }}
          </button>
          <button @click="showCreate = false; resetForm()" class="btn-ghost">Cancel</button>
        </div>
      </div>
    </Transition>

    <!-- Models grid (Modelfy-inspired: clean model card grid) -->
    <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
      <div v-for="n in 10" :key="n" class="aspect-[3/4] rounded-2xl skeleton"></div>
    </div>

    <div v-else-if="!models.length" class="text-center py-32">
      <i class="fa-solid fa-wand-magic-sparkles text-5xl mb-4 text-muted"></i>
      <h3 class="text-xl font-medium text-white mb-2">No models yet</h3>
      <p class="text-muted mb-6">Create your first AI model persona to use in campaigns.</p>
      <button @click="showCreate = true" class="btn-accent">Create Model</button>
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
      <div v-for="model in models" :key="model.id"
        class="asset-card group bg-surface">
        <!-- Model preview or placeholder -->
        <div class="aspect-[3/4] relative overflow-hidden bg-gradient-to-b from-surface to-canvas">
          <img v-if="model.preview_image" :src="`/storage/${model.preview_image}`" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center">
            <i class="fa-solid fa-user text-5xl opacity-20 text-muted"></i>
          </div>
          <!-- Delete button -->
          <button @click.stop="deleteModel(model.id)"
            class="absolute top-2 right-2 w-7 h-7 rounded-full bg-black/60 text-red-400 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-xs">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="p-3">
          <p class="font-medium text-sm text-white">{{ model.name }}</p>
          <p class="text-xs text-muted mt-0.5 capitalize">{{ [model.ethnicity, model.age_range, model.body_type].filter(Boolean).join(' · ') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'

const models     = ref([])
const loading    = ref(false)
const showCreate = ref(false)
const saving     = ref(false)
const form       = ref({ name: '', ethnicity: '', gender: 'female', age_range: '25-35', body_type: 'regular', skin_tone: 'medium' })

const ethnicities = ['Pakistani', 'Arab', 'Turkish', 'European', 'South Asian', 'East Asian', 'African', 'Latin', 'Mixed']
const ageRanges   = ['18-25', '25-35', '35-50', '50+']
const bodyTypes   = ['regular', 'plus-size', 'petite', 'athletic', 'tall']
const skinTones   = ['fair', 'medium', 'olive', 'brown', 'dark']

onMounted(fetchModels)

async function fetchModels() {
  loading.value = true
  try {
    const { data } = await api.get('/ai-models')
    models.value = data
  } finally { loading.value = false }
}

async function createModel() {
  saving.value = true
  try {
    const { data } = await api.post('/ai-models', form.value)
    models.value.unshift(data)
    showCreate.value = false
    resetForm()
  } finally { saving.value = false }
}

async function deleteModel(id) {
  await api.delete(`/ai-models/${id}`)
  models.value = models.value.filter(m => m.id !== id)
}

function resetForm() {
  form.value = { name: '', ethnicity: '', gender: 'female', age_range: '25-35', body_type: 'regular', skin_tone: 'medium' }
}
</script>
