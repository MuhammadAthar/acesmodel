<template>
  <div class="max-w-4xl mx-auto px-6 py-10">
    <div class="mb-10">
      <h1 class="heading-lg text-white">Brand DNA</h1>
      <p class="text-muted mt-1">Teach the AI your brand's unique visual identity</p>
    </div>

    <!-- Brand selector / create new -->
    <div class="flex items-center gap-4 mb-10">
      <div class="flex-1">
        <select v-model="selectedBrandId" class="input-dark">
          <option value="">— Select a brand —</option>
          <option v-for="b in brandStore.brands" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>
      <button @click="showCreateBrand = true" class="btn-ghost">+ New Brand</button>
    </div>

    <!-- Create brand modal -->
    <div v-if="showCreateBrand" class="glass rounded-3xl p-6 border border-border mb-8 animate-fade-up">
      <h3 class="font-medium text-white mb-4">Create Brand</h3>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Brand name</label>
          <input v-model="newBrand.name" type="text" class="input-dark" placeholder="e.g. Nova Studio" />
        </div>
        <div>
          <label class="block text-xs font-medium text-muted uppercase tracking-widest mb-2">Website (optional)</label>
          <input v-model="newBrand.website" type="url" class="input-dark" placeholder="https://yourbrand.com" />
        </div>
        <div class="flex items-center gap-3">
          <button @click="createBrand" :disabled="!newBrand.name" class="btn-accent disabled:opacity-50">Create Brand</button>
          <button @click="showCreateBrand = false" class="btn-ghost">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Brand DNA upload (only if brand selected) -->
    <div v-if="activeBrand" class="space-y-8">

      <!-- DNA Status -->
      <div class="glass rounded-3xl p-6 border" :class="activeBrand.dna_analyzed ? 'border-green-500/30' : 'border-border'">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl"
            :class="activeBrand.dna_analyzed ? 'bg-green-500/20' : 'bg-accent-glow border border-accent/30'">
            <i v-if="activeBrand.dna_analyzed" class="fa-solid fa-circle-check text-2xl text-green-400"></i>
            <i v-else class="fa-solid fa-dna text-2xl text-accent"></i>
          </div>
          <div>
            <h3 class="font-medium text-white">{{ activeBrand.name }}</h3>
            <p class="text-muted text-sm">
              {{ activeBrand.dna_analyzed ? 'Brand DNA analyzed and active' : 'Upload brand assets to train the AI' }}
            </p>
          </div>
        </div>

        <!-- Analyzed brand profile -->
        <div v-if="activeBrand.dna_analyzed" class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="glass rounded-xl p-3 border border-border">
            <p class="text-xs text-muted mb-1">Tone</p>
            <p class="text-sm text-white capitalize">{{ activeBrand.tone || '—' }}</p>
          </div>
          <div class="glass rounded-xl p-3 border border-border">
            <p class="text-xs text-muted mb-1">Photography</p>
            <p class="text-sm text-white capitalize">{{ activeBrand.photography_style || '—' }}</p>
          </div>
          <div class="glass rounded-xl p-3 border border-border">
            <p class="text-xs text-muted mb-1">Brand Colors</p>
            <div class="flex items-center gap-1.5 mt-1">
              <div v-for="c in (activeBrand.colors ?? []).slice(0,5)" :key="c"
                class="w-4 h-4 rounded-full border border-white/20" :style="`background:${c}`"></div>
            </div>
          </div>
          <div class="glass rounded-xl p-3 border border-border">
            <p class="text-xs text-muted mb-2">Aesthetic</p>
            <div class="flex flex-wrap gap-1">
              <span v-for="tag in (activeBrand.aesthetic_tags ?? []).slice(0,3)" :key="tag" class="tag text-xs">{{ tag }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Upload brand assets -->
      <div>
        <h3 class="font-medium text-white mb-2">Upload Brand Assets</h3>
        <p class="text-muted text-sm mb-4">Upload your logo, product shots, Instagram screenshots, or past campaigns. The more you upload, the better the AI learns your style.</p>

        <div
          class="h-40 rounded-2xl border-2 border-dashed border-border flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-accent/40 transition-colors"
          @click="dnaInput.click()"
          @dragover.prevent
          @drop.prevent="handleDnaFiles"
        >
          <i class="fa-solid fa-image text-2xl text-muted"></i>
          <p class="text-sm text-muted">Drop images or click to browse</p>
          <p class="text-xs text-muted opacity-60">Logo, product photos, Instagram posts, campaigns</p>
        </div>
        <input ref="dnaInput" type="file" multiple accept="image/*" class="hidden" @change="handleDnaSelect" />

        <!-- Preview selected files -->
        <div v-if="dnaFiles.length" class="mt-4 flex flex-wrap gap-3">
          <div v-for="(f, i) in dnaFiles" :key="i"
            class="relative w-16 h-16 rounded-xl overflow-hidden border border-border">
            <img :src="previewUrl(f)" class="w-full h-full object-cover" />
          </div>
        </div>

        <div v-if="dnaFiles.length" class="mt-4 flex items-center gap-3">
          <button @click="analyzeDna" :disabled="analyzing" class="btn-accent disabled:opacity-50">
            <i v-if="analyzing" class="fa-solid fa-spinner fa-spin"></i>
            {{ analyzing ? 'Analyzing DNA...' : `Analyze ${dnaFiles.length} asset${dnaFiles.length > 1 ? 's' : ''}` }}
          </button>
          <button @click="dnaFiles = []" class="btn-ghost">Clear</button>
        </div>
      </div>
    </div>

    <!-- No brand selected -->
    <div v-else class="text-center py-20">
      <i class="fa-solid fa-dna text-5xl mb-4 text-muted"></i>
      <p class="text-muted">Select or create a brand to get started.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useBrandStore } from '@/stores/brand'

const brandStore       = useBrandStore()
const selectedBrandId  = ref('')
const showCreateBrand  = ref(false)
const newBrand         = ref({ name: '', website: '' })
const dnaFiles         = ref([])
const dnaInput         = ref(null)
const analyzing        = ref(false)

const activeBrand = computed(() => brandStore.brands.find(b => b.id == selectedBrandId.value) ?? null)

onMounted(async () => {
  await brandStore.fetchAll()
  if (brandStore.brands.length) selectedBrandId.value = brandStore.brands[0].id
})

async function createBrand() {
  await brandStore.create({ name: newBrand.value.name, website: newBrand.value.website || null })
  selectedBrandId.value = brandStore.brands[brandStore.brands.length - 1].id
  showCreateBrand.value = false
  newBrand.value = { name: '', website: '' }
}

function handleDnaFiles(e) { dnaFiles.value = Array.from(e.dataTransfer.files) }
function handleDnaSelect(e) { dnaFiles.value = Array.from(e.target.files) }
function previewUrl(file) { return URL.createObjectURL(file) }

async function analyzeDna() {
  analyzing.value = true
  try {
    await brandStore.analyzeDna(selectedBrandId.value, dnaFiles.value)
    dnaFiles.value = []
    await brandStore.fetchAll()
  } finally {
    analyzing.value = false
  }
}
</script>
