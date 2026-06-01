<template>
  <div class="space-y-6">

    <!-- Task type tabs -->
    <div class="flex items-center gap-1 p-1 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e] flex-wrap">
      <button
        v-for="t in taskTypes" :key="t.key"
        @click="activeTask = t.key"
        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all"
        :class="activeTask === t.key
          ? 'bg-[#1a1a1a] text-white border border-[#2a2a2a]'
          : 'text-[#555] hover:text-[#888]'"
      >
        <i :class="t.icon" class="text-xs"></i>
        {{ t.label }}
      </button>
    </div>

    <!-- Task description -->
    <div class="flex items-start gap-3 px-4 py-3 rounded-xl bg-[#0d0d0d] border border-[#1a1a1a]">
      <i :class="currentTask.icon" class="text-accent mt-0.5"></i>
      <div>
        <p class="text-sm font-semibold text-white">{{ currentTask.label }}</p>
        <p class="text-xs text-[#666] mt-0.5">{{ currentTask.description }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-7 h-7 rounded-full border-2 border-[#333] border-t-[#e8d5b7] animate-spin"></div>
    </div>

    <!-- 4 tier cards -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
      <div
        v-for="tier in tiers" :key="tier.key"
        class="flex flex-col rounded-2xl border overflow-hidden transition-all"
        :class="tier.borderClass"
      >
        <!-- Card header -->
        <div class="px-4 py-3 flex items-center justify-between" :class="tier.headerClass">
          <div class="flex items-center gap-2">
            <i :class="[tier.icon, tier.iconClass]" class="w-4 text-center"></i>
            <div>
              <p class="text-xs font-bold uppercase tracking-widest" :class="tier.labelClass">{{ tier.label }}</p>
              <p class="text-[10px] text-[#666] mt-0.5">{{ tier.subtitle }}</p>
            </div>
          </div>
          <!-- Active toggle -->
          <button
            @click="toggleActive(tier.key)"
            class="w-9 h-5 rounded-full transition-all relative flex-shrink-0"
            :class="forms[tier.key].is_active ? 'bg-accent' : 'bg-[#2a2a2a]'"
            :title="forms[tier.key].is_active ? 'Active' : 'Inactive'"
          >
            <span
              class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all"
              :class="forms[tier.key].is_active ? 'left-4.5' : 'left-0.5'"
              style="transition: left 0.15s"
            ></span>
          </button>
        </div>

        <!-- Card body -->
        <div class="flex-1 p-4 space-y-3 bg-[#111]">

          <!-- Garment-aware banner -->
          <div
            v-if="activeTask === 'image_generation'"
            class="flex items-start gap-2 px-3 py-2.5 rounded-xl border text-[10px] leading-relaxed"
            :class="isGarmentAwareModel(forms[tier.key].model_id)
              ? 'bg-green-500/5 border-green-500/20 text-green-400'
              : 'bg-yellow-500/5 border-yellow-500/20 text-yellow-500/80'"
          >
            <i :class="isGarmentAwareModel(forms[tier.key].model_id) ? 'fa-solid fa-shirt' : 'fa-solid fa-triangle-exclamation'" class="mt-0.5 flex-shrink-0"></i>
            <span v-if="isGarmentAwareModel(forms[tier.key].model_id)">
              <strong>Garment-aware:</strong> Places your uploaded garment on a model photo. Recommended for fashion.
            </span>
            <span v-else>
              <strong>Text-to-image only:</strong> Uses garment description as text. Your garment photo is not used. Switch to <strong>FASHN Try-On</strong> or <strong>FLUX Kontext</strong> for garment accuracy.
            </span>
          </div>

          <!-- Provider -->
          <div>
            <label class="block text-[10px] font-semibold text-[#555] uppercase tracking-widest mb-1.5">Provider</label>
            <select
              v-model="forms[tier.key].provider"
              @change="applyProviderPreset(tier.key)"
              class="w-full bg-[#0a0a0a] border border-[#222] rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#333] appearance-none"
            >
              <option v-for="p in providers" :key="p" :value="p">{{ p }}</option>
            </select>
          </div>

          <!-- Preset model picker -->
          <div v-if="availablePresets(activeTask, tier.key, forms[tier.key].provider).length">
            <label class="block text-[10px] font-semibold text-[#555] uppercase tracking-widest mb-1.5">Recommended Model</label>
            <select
              v-model="selectedPreset[tier.key]"
              @change="applyPreset(tier.key)"
              class="w-full bg-[#0a0a0a] border border-[#222] rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#333] appearance-none"
            >
              <option value="">— pick a preset —</option>
              <option
                v-for="p in availablePresets(activeTask, tier.key, forms[tier.key].provider)"
                :key="p.model_id"
                :value="p.model_id"
              >{{ p.model_name }} ({{ formatCost(p.cost_per_use) }})</option>
            </select>
          </div>

          <!-- Model ID -->
          <div>
            <label class="block text-[10px] font-semibold text-[#555] uppercase tracking-widest mb-1.5">Model ID / Endpoint</label>
            <input
              v-model="forms[tier.key].model_id"
              type="text"
              placeholder="e.g. black-forest-labs/flux-schnell"
              class="w-full bg-[#0a0a0a] border border-[#222] rounded-lg px-3 py-2 text-xs text-white placeholder-[#444] focus:outline-none focus:border-[#333] font-mono"
            />
          </div>

          <!-- Model Display Name -->
          <div>
            <label class="block text-[10px] font-semibold text-[#555] uppercase tracking-widest mb-1.5">Display Name</label>
            <input
              v-model="forms[tier.key].model_name"
              type="text"
              placeholder="e.g. FLUX.1 Schnell"
              class="w-full bg-[#0a0a0a] border border-[#222] rounded-lg px-3 py-2 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#333]"
            />
          </div>

          <!-- Cost per use -->
          <div>
            <label class="block text-[10px] font-semibold text-[#555] uppercase tracking-widest mb-1.5">Cost per Use (USD)</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#555] text-xs">$</span>
              <input
                v-model="forms[tier.key].cost_per_use"
                type="number"
                step="0.000001"
                min="0"
                placeholder="0.0030"
                class="w-full bg-[#0a0a0a] border border-[#222] rounded-lg pl-6 pr-3 py-2 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#333]"
              />
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-[10px] font-semibold text-[#555] uppercase tracking-widest mb-1.5">Notes</label>
            <textarea
              v-model="forms[tier.key].description"
              rows="2"
              placeholder="Optional notes about quality, speed, limits…"
              class="w-full bg-[#0a0a0a] border border-[#222] rounded-lg px-3 py-2 text-xs text-white placeholder-[#444] focus:outline-none focus:border-[#333] resize-none"
            ></textarea>
          </div>

          <!-- API Key -->
          <div>
            <label class="block text-[10px] font-semibold text-[#555] uppercase tracking-widest mb-1.5">
              <i class="fa-solid fa-key mr-1"></i>API Key
              <span class="text-[#444] normal-case tracking-normal font-normal ml-1">(stored in settings)</span>
            </label>
            <div class="relative">
              <input
                v-model="forms[tier.key].api_key"
                :type="showApiKey[tier.key] ? 'text' : 'password'"
                placeholder="sk-… or r8_…"
                autocomplete="off"
                class="w-full bg-[#0a0a0a] border border-[#222] rounded-lg px-3 pr-9 py-2 text-xs text-white placeholder-[#444] focus:outline-none focus:border-[#444] font-mono"
              />
              <button
                type="button"
                @click="showApiKey[tier.key] = !showApiKey[tier.key]"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#555] hover:text-white transition-colors"
              >
                <i :class="showApiKey[tier.key] ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
              </button>
            </div>
            <!-- Provider API key link -->
            <a
              v-if="providerKeyUrls[forms[tier.key].provider]"
              :href="providerKeyUrls[forms[tier.key].provider].url"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-1.5 flex items-center gap-1.5 text-[10px] text-[#555] hover:text-accent transition-colors group"
            >
              <i class="fa-solid fa-arrow-up-right-from-square text-[9px] group-hover:text-accent"></i>
              <span>Get API key: {{ providerKeyUrls[forms[tier.key].provider].label }}</span>
            </a>
          </div>

        </div>

        <!-- Card footer: test + save -->
        <div class="px-4 py-3 border-t border-[#1a1a1a] bg-[#0d0d0d] flex items-center justify-between gap-2">
          <span class="text-xs" :class="saveStatus[tier.key] === 'saved' ? 'text-green-400' : saveStatus[tier.key] === 'error' ? 'text-red-400' : 'text-transparent'">
            <template v-if="saveStatus[tier.key] === 'saved'"><i class="fa-solid fa-check mr-1"></i>Saved</template>
            <template v-else-if="saveStatus[tier.key] === 'error'"><i class="fa-solid fa-xmark mr-1"></i>Error</template>
          </span>
          <div class="flex items-center gap-2">
            <button
              v-if="configs[activeTask]?.[tier.key]?.id"
              @click="resetConfig(tier.key)"
              class="px-3 py-1.5 rounded-lg text-xs text-[#555] hover:text-red-400 border border-[#1e1e1e] hover:border-red-400/30 transition-all"
            >Reset</button>
            <button
              @click="testModel(tier.key)"
              :disabled="testState[tier.key].loading || !forms[tier.key].model_id || !forms[tier.key].api_key"
              class="px-3 py-1.5 rounded-lg text-xs font-medium border border-[#2a2a2a] text-[#888] hover:text-white hover:border-[#444] transition-all disabled:opacity-30 flex items-center gap-1.5"
              title="Run a live test with these settings"
            >
              <i v-if="testState[tier.key].loading" class="fa-solid fa-spinner fa-spin text-xs"></i>
              <i v-else class="fa-solid fa-flask text-xs"></i>
              {{ testState[tier.key].loading ? 'Testing…' : 'Test' }}
            </button>
            <button
              @click="saveConfig(tier.key)"
              :disabled="saving[tier.key] || !forms[tier.key].model_id"
              class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-accent text-canvas hover:opacity-90 disabled:opacity-40 transition-all flex items-center gap-1.5"
            >
              <i v-if="saving[tier.key]" class="fa-solid fa-spinner fa-spin text-xs"></i>
              {{ saving[tier.key] ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </div>

        <!-- Test result panel -->
        <div v-if="testState[tier.key].result || testState[tier.key].error || testState[tier.key].loading"
          class="border-t border-[#1a1a1a] bg-[#080808]">
          <!-- Loading state -->
          <div v-if="testState[tier.key].loading" class="flex items-center gap-3 px-4 py-4">
            <div class="w-5 h-5 rounded-full border-2 border-[#333] border-t-[#e8d5b7] animate-spin flex-shrink-0"></div>
            <div>
              <p class="text-xs font-medium text-[#aaa]">Running live test…</p>
              <p class="text-[10px] text-[#555] mt-0.5">Image models may take up to 30s</p>
            </div>
          </div>
          <!-- Error -->
          <div v-else-if="testState[tier.key].error" class="px-4 py-3">
            <div class="flex items-start gap-2 p-3 rounded-xl bg-red-500/5 border border-red-500/20">
              <i class="fa-solid fa-circle-xmark text-red-400 mt-0.5 flex-shrink-0"></i>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-red-400 mb-1">Test Failed</p>
                <p class="text-[10px] text-[#666] break-words leading-relaxed">{{ testState[tier.key].error }}</p>
              </div>
            </div>
            <button @click="testState[tier.key].error = null" class="mt-2 text-[10px] text-[#555] hover:text-[#888] transition-colors">Dismiss</button>
          </div>
          <!-- Result -->
          <div v-else-if="testState[tier.key].result" class="px-4 py-3">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-circle-check text-green-400 text-xs"></i>
                <p class="text-[10px] font-semibold text-green-400 uppercase tracking-widest">Test Passed</p>
              </div>
              <button @click="testState[tier.key].result = null; testState[tier.key].type = null"
                class="text-[#444] hover:text-[#777] transition-colors">
                <i class="fa-solid fa-xmark text-xs"></i>
              </button>
            </div>
            <!-- Image result -->
            <div v-if="testState[tier.key].type === 'image'" class="rounded-xl overflow-hidden border border-[#222]">
              <img :src="testState[tier.key].result" alt="Test output" class="w-full object-cover max-h-64" />
            </div>
            <!-- Base64 image (Stability AI) -->
            <div v-else-if="testState[tier.key].type === 'image_base64'" class="rounded-xl overflow-hidden border border-[#222]">
              <img :src="`data:image/png;base64,${testState[tier.key].result}`" alt="Test output" class="w-full object-cover max-h-64" />
            </div>
            <!-- Text result -->
            <div v-else-if="testState[tier.key].type === 'text'"
              class="p-3 rounded-xl bg-[#0d0d0d] border border-[#1e1e1e] text-xs text-[#ccc] leading-relaxed whitespace-pre-wrap">{{ testState[tier.key].result }}</div>
            <!-- Key valid -->
            <div v-else-if="testState[tier.key].type === 'key_valid'"
              class="flex items-start gap-2 p-3 rounded-xl bg-green-500/5 border border-green-500/20">
              <i class="fa-solid fa-key text-green-400 text-xs mt-0.5"></i>
              <p class="text-xs text-[#aaa] leading-relaxed">{{ testState[tier.key].result }}</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap items-center gap-6 px-4 py-3 rounded-xl bg-[#0a0a0a] border border-[#1a1a1a] text-xs text-[#555]">
      <span v-for="tier in tiers" :key="tier.key" class="flex items-center gap-1.5">
        <i :class="[tier.icon, tier.iconClass]"></i>
        <span :class="tier.labelClass">{{ tier.label }}</span>
        <span>— {{ tier.planHint }}</span>
      </span>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '@/lib/api'

// ─── Task types ────────────────────────────────────────────────────────────
const taskTypes = [
  { key: 'image_generation', label: 'Image Generation', icon: 'fa-solid fa-image',           description: 'AI model photos, studio shots, lifestyle imagery for garments.' },
  { key: 'video_generation', label: 'Video Generation', icon: 'fa-solid fa-film',             description: 'Reels, TikTok clips, product showcase videos.' },
  { key: 'ad_creative',      label: 'Ad Creatives',     icon: 'fa-solid fa-bullhorn',         description: 'Meta ads, Google Display banners, promotional graphics.' },
  { key: 'copy_generation',  label: 'Copy & Captions',  icon: 'fa-solid fa-pen-nib',          description: 'Product descriptions, captions, and marketing copy.' },
  { key: 'social_post',      label: 'Social Posts',     icon: 'fa-solid fa-mobile-screen',    description: 'Ready-to-publish Instagram, TikTok, and Facebook posts.' },
  { key: 'seo_content',      label: 'SEO Content',      icon: 'fa-solid fa-magnifying-glass', description: 'Product page copy, meta descriptions, keyword-rich content.' },
]

// ─── Tiers ────────────────────────────────────────────────────────────────
const tiers = [
  { key: 'free',        label: 'Free',        icon: 'fa-solid fa-circle-dot',        iconClass: 'text-[#666]',      subtitle: 'No cost to user',     planHint: 'Free plan',        borderClass: 'border-[#1e1e1e]',         headerClass: 'bg-[#0f0f0f] border-b border-[#1e1e1e]', labelClass: 'text-[#888]'    },
  { key: 'freemium',    label: 'Freemium',    icon: 'fa-solid fa-bolt',              iconClass: 'text-blue-400',    subtitle: 'Light usage credits',  planHint: 'Starter plan',     borderClass: 'border-blue-500/20',       headerClass: 'bg-blue-500/5 border-b border-blue-500/20', labelClass: 'text-blue-400' },
  { key: 'premium',     label: 'Premium',     icon: 'fa-solid fa-gem',               iconClass: 'text-accent',      subtitle: 'High quality output',  planHint: 'Growth / Agency',  borderClass: 'border-accent/30',         headerClass: 'bg-accent/5 border-b border-accent/30',    labelClass: 'text-accent'   },
  { key: 'pro_premium', label: 'Pro Premium', icon: 'fa-solid fa-rocket',            iconClass: 'text-purple-400',  subtitle: 'Best-in-class models', planHint: 'Enterprise plan',  borderClass: 'border-purple-500/30',     headerClass: 'bg-purple-500/5 border-b border-purple-500/20', labelClass: 'text-purple-400' },
]

const providers = ['Replicate', 'OpenAI', 'Anthropic', 'Runway', 'Stability AI', 'Google', 'Mistral', 'Custom']

const providerKeyUrls = {
  'Replicate':     { url: 'https://replicate.com/account/api-tokens',       label: 'replicate.com → Account → API Tokens' },
  'OpenAI':        { url: 'https://platform.openai.com/api-keys',            label: 'platform.openai.com → API Keys' },
  'Anthropic':     { url: 'https://console.anthropic.com/settings/keys',     label: 'console.anthropic.com → API Keys' },
  'Runway':        { url: 'https://app.runwayml.com/settings',               label: 'app.runwayml.com → Settings → API' },
  'Stability AI':  { url: 'https://platform.stability.ai/account/keys',      label: 'platform.stability.ai → Account → API Keys' },
  'Google':        { url: 'https://aistudio.google.com/app/apikey',           label: 'aistudio.google.com → Get API Key' },
  'Mistral':       { url: 'https://console.mistral.ai/api-keys',             label: 'console.mistral.ai → API Keys' },
  'Custom':        null,
}

// ─── Preset catalogue ─────────────────────────────────────────────────────
const presetCatalogue = {
  image_generation: {
    Replicate: [
      { tier: 'free',        model_id: 'cuuupid/idm-vton',                                                                   model_name: 'IDM-VTON (Try-On)',         cost_per_use: 0.008,  description: '★ Recommended: Places YOUR garment image on a model. Best for fashion.' },
      { tier: 'free',        model_id: 'stability-ai/sdxl:c221b2b8ef527988fb59bf24a8b97c4561508827',                      model_name: 'Stable Diffusion XL',       cost_per_use: 0.0023, description: 'Text-to-image only. Open-source, fast, good quality.' },
      { tier: 'freemium',    model_id: 'cuuupid/idm-vton',                                                                   model_name: 'IDM-VTON (Try-On)',         cost_per_use: 0.008,  description: '★ Recommended: Places YOUR garment image on a real model photo.' },
      { tier: 'freemium',    model_id: 'black-forest-labs/flux-schnell',                                                   model_name: 'FLUX.1 Schnell',            cost_per_use: 0.003,  description: 'Text-to-image only. Ultra-fast 4-step generation. No garment reference.' },
      { tier: 'premium',     model_id: 'cuuupid/idm-vton',                                                                   model_name: 'IDM-VTON (Try-On)',         cost_per_use: 0.008,  description: '★ Recommended: Realistic garment try-on with fashion model.' },
      { tier: 'premium',     model_id: 'black-forest-labs/flux-kontext-pro',                                               model_name: 'FLUX Kontext Pro',          cost_per_use: 0.04,   description: 'Context-aware: uses garment image as reference for generation.' },
      { tier: 'premium',     model_id: 'black-forest-labs/flux-dev',                                                       model_name: 'FLUX.1 Dev',                cost_per_use: 0.025,  description: 'High-fidelity text-to-image. Great for product photography.' },
      { tier: 'pro_premium', model_id: 'cuuupid/idm-vton',                                                                   model_name: 'IDM-VTON (Try-On)',         cost_per_use: 0.008,  description: '★ Recommended: Best garment-on-model accuracy for all categories.' },
      { tier: 'pro_premium', model_id: 'black-forest-labs/flux-kontext-max',                                               model_name: 'FLUX Kontext Max',          cost_per_use: 0.08,   description: 'Top-tier context-aware image generation with garment reference.' },
      { tier: 'pro_premium', model_id: 'black-forest-labs/flux-pro',                                                       model_name: 'FLUX.1 Pro',                cost_per_use: 0.055,  description: 'Flagship FLUX model. Top quality, photorealistic.' },
    ],
    'Stability AI': [
      { tier: 'free',        model_id: 'stability-ai/stable-diffusion:ac732df83cea7fff18b8472768c88ad041fa750d25ebba',      model_name: 'Stable Diffusion 1.5',      cost_per_use: 0.0010, description: 'Classic free option, lower quality but very cheap.' },
      { tier: 'premium',     model_id: 'stabilityai/stable-image-ultra',                                                   model_name: 'Stable Image Ultra',        cost_per_use: 0.014,  description: 'High-resolution, photorealistic images.' },
    ],
  },
  video_generation: {
    Replicate: [
      { tier: 'free',        model_id: 'anotherjesse/zeroscope-v2-xl:9f747673945c62801b13b84701c783929c0ee784e', model_name: 'ZeroScope V2 XL',    cost_per_use: 0.02,  description: 'Open-source, free-to-use video model. Short clips.' },
      { tier: 'freemium',    model_id: 'minimax/video-01',                                                      model_name: 'MiniMax Video-01',   cost_per_use: 0.05,  description: 'Good quality 6s clips. Cost-effective freemium choice.' },
      { tier: 'premium',     model_id: 'wan-video/wan2.1-t2v-480p',                                             model_name: 'Wan 2.1 (480p)',     cost_per_use: 0.08,  description: 'High-quality open-source video model.' },
      { tier: 'pro_premium', model_id: 'minimax/video-01-live',                                                 model_name: 'MiniMax Video-01 Live', cost_per_use: 0.12, description: 'Cinematic quality, realistic motion.' },
    ],
    Runway: [
      { tier: 'premium',     model_id: 'gen3a_turbo',  model_name: 'Runway Gen-3 Turbo',  cost_per_use: 0.10,  description: 'Fast Runway generation. Good for social media.' },
      { tier: 'pro_premium', model_id: 'gen3a',        model_name: 'Runway Gen-3 Alpha',  cost_per_use: 0.25,  description: 'Best Runway quality. Cinematic results.' },
    ],
  },
  ad_creative: {
    Replicate: [
      { tier: 'free',        model_id: 'stability-ai/sdxl:c221b2b8ef527988fb59bf24a8b97c4561508827', model_name: 'Stable Diffusion XL',  cost_per_use: 0.0023, description: 'Free ad image generation with SDXL.' },
      { tier: 'freemium',    model_id: 'black-forest-labs/flux-schnell',                             model_name: 'FLUX.1 Schnell',       cost_per_use: 0.003,  description: 'Fast ad creatives with great prompt adherence.' },
      { tier: 'premium',     model_id: 'black-forest-labs/flux-dev',                                 model_name: 'FLUX.1 Dev',           cost_per_use: 0.025,  description: 'High-fidelity ad images. Excellent brand consistency.' },
      { tier: 'pro_premium', model_id: 'recraft-ai/recraft-v3',                                     model_name: 'Recraft V3',           cost_per_use: 0.04,   description: 'Purpose-built for brand assets and marketing.' },
    ],
  },
  copy_generation: {
    OpenAI: [
      { tier: 'free',        model_id: 'gpt-3.5-turbo',              model_name: 'GPT-3.5 Turbo',      cost_per_use: 0.0005, description: 'Fast and cheap. Good for basic captions.' },
      { tier: 'freemium',    model_id: 'gpt-4o-mini',                model_name: 'GPT-4o Mini',        cost_per_use: 0.0003, description: 'Better than 3.5 at lower cost. Best freemium pick.' },
      { tier: 'premium',     model_id: 'gpt-4o',                     model_name: 'GPT-4o',             cost_per_use: 0.005,  description: 'Excellent creative writing and brand voice.' },
    ],
    Anthropic: [
      { tier: 'pro_premium', model_id: 'claude-3-5-sonnet-20241022', model_name: 'Claude 3.5 Sonnet',  cost_per_use: 0.003,  description: 'Best copy quality, nuanced brand tone, long-form.' },
      { tier: 'premium',     model_id: 'claude-3-haiku-20240307',    model_name: 'Claude 3 Haiku',     cost_per_use: 0.0004, description: 'Fast and cheap Anthropic model.' },
    ],
    Mistral: [
      { tier: 'freemium',    model_id: 'mistral-small-latest',       model_name: 'Mistral Small',      cost_per_use: 0.0002, description: 'Cheapest EU-hosted LLM. GDPR friendly.' },
      { tier: 'premium',     model_id: 'mistral-large-latest',       model_name: 'Mistral Large',      cost_per_use: 0.003,  description: 'High quality from Mistral. Good for EU deployments.' },
    ],
  },
  social_post: {
    OpenAI: [
      { tier: 'free',        model_id: 'gpt-3.5-turbo',              model_name: 'GPT-3.5 Turbo',      cost_per_use: 0.0005, description: 'Good for simple Instagram captions.' },
      { tier: 'freemium',    model_id: 'gpt-4o-mini',                model_name: 'GPT-4o Mini',        cost_per_use: 0.0003, description: 'Viral-worthy captions at minimal cost.' },
      { tier: 'premium',     model_id: 'gpt-4o',                     model_name: 'GPT-4o',             cost_per_use: 0.005,  description: 'Rich social content with trend awareness.' },
    ],
    Anthropic: [
      { tier: 'pro_premium', model_id: 'claude-3-5-sonnet-20241022', model_name: 'Claude 3.5 Sonnet',  cost_per_use: 0.003,  description: 'Best engagement-optimised social copy.' },
    ],
    Mistral: [
      { tier: 'freemium',    model_id: 'mistral-small-latest',       model_name: 'Mistral Small',      cost_per_use: 0.0002, description: 'Budget social post generation.' },
    ],
  },
  seo_content: {
    OpenAI: [
      { tier: 'free',        model_id: 'gpt-3.5-turbo',              model_name: 'GPT-3.5 Turbo',      cost_per_use: 0.0005, description: 'Basic product descriptions and meta tags.' },
      { tier: 'freemium',    model_id: 'gpt-4o-mini',                model_name: 'GPT-4o Mini',        cost_per_use: 0.0003, description: 'Good keyword-aware writing at low cost.' },
      { tier: 'premium',     model_id: 'gpt-4o',                     model_name: 'GPT-4o',             cost_per_use: 0.005,  description: 'High-quality SEO-optimised product copy.' },
    ],
    Anthropic: [
      { tier: 'pro_premium', model_id: 'claude-3-5-sonnet-20241022', model_name: 'Claude 3.5 Sonnet',  cost_per_use: 0.003,  description: 'Best long-form SEO content, nuanced and accurate.' },
    ],
    Google: [
      { tier: 'premium',     model_id: 'gemini-1.5-flash',           model_name: 'Gemini 1.5 Flash',   cost_per_use: 0.00035, description: 'Fast, cheap Google model. Great for bulk SEO.' },
      { tier: 'pro_premium', model_id: 'gemini-1.5-pro',             model_name: 'Gemini 1.5 Pro',     cost_per_use: 0.0035, description: 'Long context, excellent for detailed product pages.' },
    ],
    Mistral: [
      { tier: 'freemium',    model_id: 'mistral-small-latest',       model_name: 'Mistral Small',      cost_per_use: 0.0002, description: 'Cheapest option for bulk SEO generation.' },
    ],
  },
}

// ─── Default provider per task ─────────────────────────────────────────────
const defaultProvider = {
  image_generation: 'Replicate',
  video_generation: 'Replicate',
  ad_creative:      'Replicate',
  copy_generation:  'OpenAI',
  social_post:      'OpenAI',
  seo_content:      'OpenAI',
}

// ─── State ────────────────────────────────────────────────────────────────
const activeTask     = ref('image_generation')
const configs        = ref({})   // task → tier → saved config
const loading        = ref(false)
const saving         = ref({})
const saveStatus     = ref({})
const selectedPreset = ref({})

function blankForm(task, tierKey) {
  const p = defaultProvider[task] || 'Replicate'
  return { provider: p, model_id: '', model_name: '', description: '', cost_per_use: '', is_active: true, api_key: '' }
}

const showApiKey = ref({ free: false, freemium: false, premium: false, pro_premium: false })

function blankTestState() {
  return { loading: false, result: null, type: null, error: null }
}
const testState = ref({
  free:        blankTestState(),
  freemium:    blankTestState(),
  premium:     blankTestState(),
  pro_premium: blankTestState(),
})

const forms = ref({
  free: blankForm('image_generation', 'free'),
  freemium: blankForm('image_generation', 'freemium'),
  premium: blankForm('image_generation', 'premium'),
  pro_premium: blankForm('image_generation', 'pro_premium'),
})

const currentTask = computed(() => taskTypes.find(t => t.key === activeTask.value))

// ─── Helpers ──────────────────────────────────────────────────────────────
function availablePresets(task, tierKey, provider) {
  return (presetCatalogue[task]?.[provider] ?? [])
}

function applyProviderPreset(tierKey) {
  selectedPreset.value[tierKey] = ''
  forms.value[tierKey].model_id   = ''
  forms.value[tierKey].model_name = ''
}

function applyPreset(tierKey) {
  const pId = selectedPreset.value[tierKey]
  if (!pId) return
  const task     = activeTask.value
  const provider = forms.value[tierKey].provider
  const preset   = (presetCatalogue[task]?.[provider] ?? []).find(p => p.model_id === pId)
  if (!preset) return
  forms.value[tierKey].model_id     = preset.model_id
  forms.value[tierKey].model_name   = preset.model_name
  forms.value[tierKey].cost_per_use = preset.cost_per_use
  forms.value[tierKey].description  = preset.description ?? ''
}

function toggleActive(tierKey) {
  forms.value[tierKey].is_active = !forms.value[tierKey].is_active
}

function formatCost(v) {
  if (!v && v !== 0) return ''
  return `$${Number(v).toFixed(4)}`
}

/** Models that actually use the uploaded garment image (try-on or context-aware). */
function isGarmentAwareModel(modelId) {
  if (!modelId) return false
  const id = modelId.toLowerCase()
  return id.includes('tryon') || id.includes('try-on') || id.includes('viton')
      || id.includes('idm-vton') || id.includes('kontext') || id.includes('img2img')
}

// ─── Fetch + populate ─────────────────────────────────────────────────────
async function fetchConfigs() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/ai-provider-configs')
    configs.value = data
    populateForms()
  } finally {
    loading.value = false
  }
}

function populateForms() {
  const task = activeTask.value
  tiers.forEach(tier => {
    const saved = configs.value[task]?.[tier.key]
    if (saved) {
      forms.value[tier.key] = {
        provider:     saved.provider,
        model_id:     saved.model_id,
        model_name:   saved.model_name,
        description:  saved.description ?? '',
        cost_per_use: saved.cost_per_use ?? '',
        is_active:    saved.is_active,
        api_key:      saved.settings?.api_key ?? '',
      }
    } else {
      forms.value[tier.key] = blankForm(task, tier.key)
    }
    // Restore preset dropdown if current model_id matches a known preset
    const modelId  = forms.value[tier.key].model_id
    const provider = forms.value[tier.key].provider
    const match    = (presetCatalogue[task]?.[provider] ?? []).find(p => p.model_id === modelId)
    selectedPreset.value[tier.key] = match ? modelId : ''
    saveStatus.value[tier.key]     = null
  })
}

// Re-populate forms when switching task tab
watch(activeTask, () => populateForms())

// ─── Save / Reset ─────────────────────────────────────────────────────────
async function saveConfig(tierKey) {
  saving.value[tierKey]    = true
  saveStatus.value[tierKey] = null
  try {
    const { api_key, ...rest } = forms.value[tierKey]
    const payload = {
      task_type: activeTask.value,
      tier:      tierKey,
      ...rest,
      settings:  api_key ? { api_key } : {},
    }
    const { data } = await api.post('/admin/ai-provider-configs', payload)
    if (!configs.value[activeTask.value]) configs.value[activeTask.value] = {}
    configs.value[activeTask.value][tierKey] = data
    saveStatus.value[tierKey] = 'saved'
    setTimeout(() => { saveStatus.value[tierKey] = null }, 3000)
  } catch {
    saveStatus.value[tierKey] = 'error'
  } finally {
    saving.value[tierKey] = false
  }
}

async function resetConfig(tierKey) {
  const id = configs.value[activeTask.value]?.[tierKey]?.id
  if (!id) return
  try {
    await api.delete(`/admin/ai-provider-configs/${id}`)
    delete configs.value[activeTask.value][tierKey]
    forms.value[tierKey]         = blankForm(activeTask.value, tierKey)
    selectedPreset.value[tierKey] = ''
    saveStatus.value[tierKey]    = null
  } catch {}
}

async function testModel(tierKey) {
  const ts = testState.value[tierKey]
  ts.loading = true
  ts.result  = null
  ts.type    = null
  ts.error   = null
  try {
    const { data } = await api.post('/admin/ai-provider-configs/test', {
      provider:  forms.value[tierKey].provider,
      model_id:  forms.value[tierKey].model_id,
      api_key:   forms.value[tierKey].api_key || null,
      task_type: activeTask.value,
    })
    if (data.error) {
      ts.error = data.error
    } else {
      ts.type   = data.type
      ts.result = data.output
    }
  } catch (err) {
    ts.error = err?.response?.data?.error ?? err?.response?.data?.message ?? 'Request failed'
  } finally {
    ts.loading = false
  }
}

onMounted(fetchConfigs)
</script>
