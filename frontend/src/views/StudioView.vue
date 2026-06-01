<template>
  <!-- Canva-style 3-panel layout: fills viewport below the top nav -->
  <div class="flex overflow-hidden" style="height: calc(100vh - 4rem)">

    <!-- ═══════════════════════════════════════
         LEFT PANEL — Assets
    ═══════════════════════════════════════ -->
    <aside class="w-72 flex-shrink-0 flex flex-col border-r border-border bg-surface">
      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-4 border-b border-border">
        <h2 class="text-xs font-semibold text-white uppercase tracking-[0.2em]">Assets</h2>
        <button
          @click="fileInput.click()"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-accent/30 bg-accent/10 text-accent text-xs font-medium hover:bg-accent/20 transition-colors"
        >
          <i class="fa-solid fa-plus text-xs"></i> Upload
        </button>
      </div>

      <!-- Search -->
      <div class="px-4 py-3 border-b border-border">
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-canvas border border-border">
          <i class="fa-solid fa-magnifying-glass text-xs text-muted"></i>
          <input
            v-model="assetSearch"
            type="text"
            placeholder="Search assets…"
            class="bg-transparent text-sm text-white placeholder-muted outline-none flex-1 min-w-0"
          />
        </div>
      </div>

      <!-- Garments scrollable area (also drag target) -->
      <div
        class="flex-1 overflow-y-auto p-4"
        @dragover.prevent="dragging = true"
        @dragleave.self="dragging = false"
        @drop.prevent="handleDrop"
      >
        <!-- Drag indicator -->
        <div
          v-if="dragging"
          class="mb-3 h-20 rounded-2xl border-2 border-dashed border-accent/60 bg-accent/5 flex items-center justify-center gap-2 text-accent text-xs font-medium"
        >
          <i class="fa-solid fa-cloud-arrow-up"></i> Drop to upload
        </div>

        <!-- Loading -->
        <div v-if="garmentStore.loading" class="flex items-center justify-center py-10">
          <div class="w-6 h-6 rounded-full border-2 border-accent/30 border-t-accent animate-spin"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="!filteredGarments.length" class="flex flex-col items-center justify-center py-12 text-center">
          <i class="fa-solid fa-shirt text-2xl text-muted/30 mb-3"></i>
          <p class="text-xs text-muted leading-relaxed">No assets yet.<br/>Click Upload to add your first garment.</p>
        </div>

        <!-- Garment thumbnail grid -->
        <div v-else class="grid grid-cols-2 gap-2">
          <button
            v-for="g in filteredGarments" :key="g.id"
            @click="selectGarment(g)"
            class="group relative rounded-xl overflow-hidden border transition-all duration-200"
            style="aspect-ratio: 3/4"
            :class="activeGarment?.id === g.id
              ? 'border-accent ring-1 ring-accent/30'
              : 'border-border hover:border-accent/30'"
          >
            <img :src="`/storage/${g.original_image}`" :alt="g.name || 'Garment'" class="w-full h-full object-cover" />
            <!-- Hover overlay + delete -->
            <div
              class="absolute inset-0 bg-black/50 flex items-start justify-end p-1.5 transition-opacity"
              :class="activeGarment?.id === g.id ? 'opacity-0' : 'opacity-0 group-hover:opacity-100'"
            >
              <button
                @click.stop="removeGarment(g.id)"
                class="w-6 h-6 rounded-full bg-black/70 hover:bg-red-500/80 flex items-center justify-center transition-colors"
              >
                <i class="fa-solid fa-xmark text-white text-xs"></i>
              </button>
            </div>
            <!-- Active dot -->
            <div v-if="activeGarment?.id === g.id" class="absolute top-2 left-2 w-2 h-2 rounded-full bg-accent shadow-lg"></div>
            <!-- Name label -->
            <div class="absolute bottom-0 left-0 right-0 px-2 py-1.5 bg-gradient-to-t from-black/80">
              <p class="text-xs text-white truncate font-medium">{{ g.name || g.category || 'Garment' }}</p>
            </div>
          </button>
        </div>

        <!-- Upload in progress -->
        <div v-if="uploading" class="flex items-center gap-3 mt-3 px-3 py-3 rounded-xl border border-accent/30 bg-accent/5">
          <div class="w-5 h-5 rounded-full border-2 border-accent/30 border-t-accent animate-spin flex-shrink-0"></div>
          <p class="text-xs text-accent">Uploading & analyzing…</p>
        </div>
      </div>

      <input ref="fileInput" type="file" class="hidden" accept="image/*" @change="handleFileSelect" />
    </aside>

    <!-- ═══════════════════════════════════════
         CENTER PANEL — Workspace
    ═══════════════════════════════════════ -->
    <main class="flex-1 flex flex-col overflow-hidden min-w-0">
      <!-- Empty state: no garment selected -->
      <div v-if="!activeGarment" class="flex-1 flex flex-col items-center justify-center px-8 text-center">
        <div class="w-20 h-20 rounded-3xl glass border border-border flex items-center justify-center mb-6">
          <i class="fa-solid fa-wand-magic-sparkles text-3xl text-accent/40"></i>
        </div>
        <h2 class="text-xl font-semibold text-white mb-2">Select an asset to begin</h2>
        <p class="text-sm text-muted max-w-xs leading-relaxed">Pick a garment from the left panel, or upload a new one to start building your campaign.</p>
        <button @click="fileInput.click()" class="btn-accent mt-6 text-sm">
          <i class="fa-solid fa-plus mr-2"></i>Upload Garment
        </button>
      </div>

      <!-- Active garment workspace -->
      <template v-else>
        <!-- Workspace top bar -->
        <div class="flex-shrink-0 flex items-center justify-between px-6 py-3.5 border-b border-border bg-surface">
          <div class="flex items-center gap-3 min-w-0">
            <img
              :src="`/storage/${activeGarment.original_image}`"
              class="w-8 h-10 rounded-lg object-cover border border-border flex-shrink-0"
            />
            <div class="min-w-0">
              <h2 class="text-sm font-semibold text-white truncate">{{ activeGarment.name || activeGarment.category || 'Untitled Garment' }}</h2>
              <p class="text-xs text-muted capitalize truncate">{{ [activeGarment.category, activeGarment.gender, activeGarment.season].filter(Boolean).join(' · ') || 'No metadata' }}</p>
            </div>
            <span
              v-if="activeGarment.analyzed"
              class="flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-medium"
              style="color:#4ade80; border:1px solid rgba(74,222,128,0.3); background:rgba(74,222,128,0.08)"
            ><i class="fa-solid fa-check mr-1"></i>Analyzed</span>
            <template v-else>
              <span v-if="retryingAnalysis" class="flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-medium animate-pulse border border-accent/30 text-accent bg-accent/5">
                <i class="fa-solid fa-spinner fa-spin mr-1"></i>Analyzing…
              </span>
              <button v-else @click="retryAnalysis"
                class="flex-shrink-0 flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium border border-red-500/30 text-red-400 bg-red-500/5 hover:bg-red-500/10 transition-colors">
                <i class="fa-solid fa-rotate-right"></i>Analysis failed — Retry
              </button>
            </template>
          </div>
          <button @click="activeGarment = null" class="ml-4 flex-shrink-0 text-xs text-muted hover:text-white transition-colors flex items-center gap-1.5">
            <i class="fa-solid fa-xmark"></i> Close
          </button>
        </div>

        <!-- Scrollable builder body -->
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
          <div>
            <h3 class="text-base font-semibold text-white mb-1">Build a Campaign</h3>
            <p class="text-sm text-muted">Select what you want to generate from this garment.</p>
          </div>

          <!-- ① Choose Model — required -->
          <div v-if="selectedTypes.includes('photo')"
            class="rounded-2xl border transition-all"
            :class="!selectedPersona ? 'border-accent/40 bg-accent/5' : 'border-border glass'">
            <div class="flex items-center justify-between px-4 py-3.5">
              <div class="flex items-center gap-3 min-w-0">
                <!-- Selected avatar preview -->
                <div v-if="selectedPersona"
                  class="w-10 h-[52px] rounded-lg overflow-hidden border border-accent/40 flex-shrink-0">
                  <img v-if="selectedPersona.avatar_url" :src="selectedPersona.avatar_url"
                    :alt="selectedPersona.name" class="w-full h-full object-cover object-top" />
                </div>
                <div v-else class="w-10 h-[52px] rounded-lg bg-canvas border border-dashed border-accent/40 flex items-center justify-center flex-shrink-0">
                  <i class="fa-solid fa-person-dress text-accent/40 text-lg"></i>
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5 mb-0.5">
                    <p class="text-xs font-semibold text-white">Model</p>
                    <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-500/15 border border-red-500/30 text-red-400 font-semibold uppercase tracking-wide">Required</span>
                  </div>
                  <p v-if="selectedPersona" class="text-sm font-semibold text-accent truncate">{{ selectedPersona.name }}</p>
                  <p v-else class="text-xs text-muted">No model selected</p>
                  <p v-if="selectedPersona?.best_for" class="text-[10px] text-muted truncate mt-0.5">
                    <i class="fa-solid fa-star text-accent/40 mr-1"></i>{{ selectedPersona.best_for }}
                  </p>
                </div>
              </div>
              <button
                @click="showModelPicker = true"
                class="flex-shrink-0 flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold transition-all"
                :class="selectedPersona
                  ? 'border border-border text-muted hover:border-accent/40 hover:text-white'
                  : 'bg-accent text-canvas hover:bg-accent/90'"
              >
                <i class="fa-solid fa-person-dress text-xs"></i>
                {{ selectedPersona ? 'Change Model' : 'Choose Model' }}
              </button>
            </div>
          </div>

          <!-- ② Output types — required -->
          <div>
            <div class="flex items-center gap-2 mb-3">
              <p class="text-xs font-semibold text-white uppercase tracking-widest">Output Type</p>
              <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-500/15 border border-red-500/30 text-red-400 font-semibold uppercase tracking-wide">Required</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
            <button
              v-for="type in outputTypes" :key="type.key"
              @click="toggleType(type.key)"
              class="group relative p-4 rounded-2xl border transition-all duration-300 text-left"
              :class="selectedTypes.includes(type.key)
                ? 'border-accent/50 bg-accent-glow'
                : 'border-border glass-hover'"
            >
              <div class="flex items-start justify-between mb-3">
                <i :class="type.icon" class="text-xl text-accent"></i>
                <div v-if="selectedTypes.includes(type.key)" class="w-5 h-5 rounded-full bg-accent flex items-center justify-center">
                  <i class="fa-solid fa-check text-canvas text-xs"></i>
                </div>
              </div>
              <p class="text-sm font-medium text-white">{{ type.label }}</p>
              <p class="text-xs text-muted mt-0.5">{{ type.desc }}</p>
            </button>
            </div>
          </div>

          <!-- ③ Photo styles — required when photo selected -->
          <div v-if="selectedTypes.includes('photo')" class="glass rounded-2xl p-4 border"
            :class="!selectedSubtypes.length ? 'border-red-500/20' : 'border-border'">
            <div class="flex items-center gap-2 mb-3">
              <p class="text-xs font-medium text-muted uppercase tracking-widest">Photo styles</p>
              <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-500/15 border border-red-500/30 text-red-400 font-semibold uppercase tracking-wide">Required</span>
            </div>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="s in photoSubtypes" :key="s.key"
                @click="toggleSubtype(s.key)"
                class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
                :class="selectedSubtypes.includes(s.key)
                  ? 'border-accent/50 text-accent bg-accent-glow'
                  : 'border-border text-muted hover:border-accent/30 hover:text-white'"
              >{{ s.label }}</button>
            </div>
          </div>

          <!-- Scene Builder -->
          <div v-if="selectedTypes.includes('photo')" class="rounded-2xl border overflow-hidden transition-all"
            :class="(!sceneBackground || !sceneFloor || !sceneShadow || !sceneFilter) ? 'border-red-500/20' : 'border-border'">
            <div class="flex items-center gap-2 px-4 py-3 bg-canvas border-b border-border">
              <i class="fa-solid fa-layer-group text-accent text-xs"></i>
              <span class="text-xs font-semibold text-white">Scene Builder</span>
              <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-500/15 border border-red-500/30 text-red-400 font-semibold uppercase tracking-wide">Required</span>
            </div>

            <div class="p-4 space-y-4 bg-canvas/50">

              <!-- Background -->
              <div class="p-3 rounded-xl border transition-all" :class="!sceneBackground ? 'border-red-500/20 bg-red-500/5' : 'border-border'">
                <div class="flex items-center gap-2 mb-2">
                  <p class="text-[11px] text-muted font-medium uppercase tracking-widest">
                    <i class="fa-solid fa-panorama mr-1"></i>Background
                  </p>
                  <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-500/15 border border-red-500/30 text-red-400 font-semibold uppercase tracking-wide">Required</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in SCENE_BACKGROUND_OPTIONS" :key="opt.value"
                    @click="sceneBackground = opt.value"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="sceneBackground === opt.value
                      ? 'border-accent/50 bg-accent/10 text-accent'
                      : 'border-border text-muted hover:border-accent/30 hover:text-white'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Beside / Props -->
              <div class="p-3 rounded-xl border border-border">
                <p class="text-[11px] text-muted font-medium uppercase tracking-widest mb-2">
                  <i class="fa-solid fa-couch mr-1"></i>Beside / Props
                  <span class="text-muted/40 normal-case font-normal">(optional, multi-select)</span>
                </p>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in SCENE_PROP_OPTIONS" :key="opt.value"
                    @click="toggleSceneProp(opt.value)"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="sceneProps.includes(opt.value)
                      ? 'border-accent/50 bg-accent/10 text-accent'
                      : 'border-border text-muted hover:border-accent/30 hover:text-white'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Under / Floor -->
              <div class="p-3 rounded-xl border transition-all" :class="!sceneFloor ? 'border-red-500/20 bg-red-500/5' : 'border-border'">
                <div class="flex items-center gap-2 mb-2">
                  <p class="text-[11px] text-muted font-medium uppercase tracking-widest">
                    <i class="fa-solid fa-arrow-down mr-1"></i>Under / Floor
                  </p>
                  <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-500/15 border border-red-500/30 text-red-400 font-semibold uppercase tracking-wide">Required</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in SCENE_FLOOR_OPTIONS" :key="opt.value"
                    @click="sceneFloor = opt.value"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="sceneFloor === opt.value
                      ? 'border-accent/50 bg-accent/10 text-accent'
                      : 'border-border text-muted hover:border-accent/30 hover:text-white'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Shadow -->
              <div class="p-3 rounded-xl border transition-all" :class="!sceneShadow ? 'border-red-500/20 bg-red-500/5' : 'border-border'">
                <div class="flex items-center gap-2 mb-2">
                  <p class="text-[11px] text-muted font-medium uppercase tracking-widest">
                    <i class="fa-solid fa-circle-half-stroke mr-1"></i>Shadow
                  </p>
                  <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-500/15 border border-red-500/30 text-red-400 font-semibold uppercase tracking-wide">Required</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in SCENE_SHADOW_OPTIONS" :key="opt.value"
                    @click="sceneShadow = opt.value"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="sceneShadow === opt.value
                      ? 'border-accent/50 bg-accent/10 text-accent'
                      : 'border-border text-muted hover:border-accent/30 hover:text-white'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Style / Filter -->
              <div class="p-3 rounded-xl border transition-all" :class="!sceneFilter ? 'border-red-500/20 bg-red-500/5' : 'border-border'">
                <div class="flex items-center gap-2 mb-2">
                  <p class="text-[11px] text-muted font-medium uppercase tracking-widest">
                    <i class="fa-solid fa-wand-sparkles mr-1"></i>Style / Filter
                  </p>
                  <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-500/15 border border-red-500/30 text-red-400 font-semibold uppercase tracking-wide">Required</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in SCENE_FILTER_OPTIONS" :key="opt.value"
                    @click="sceneFilter = opt.value"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="sceneFilter === opt.value
                      ? 'border-accent/50 bg-accent/10 text-accent'
                      : 'border-border text-muted hover:border-accent/30 hover:text-white'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Live prompt preview -->
              <div class="p-3 rounded-xl bg-canvas border border-border">
                <p class="text-[11px] text-muted font-medium uppercase tracking-widest mb-1.5">
                  <i class="fa-solid fa-eye text-accent/50 mr-1"></i>Scene prompt preview
                </p>
                <p class="text-[11px] text-white/50 leading-relaxed font-mono">
                  {{ scenePromptSuffix || 'Default scene — no overrides applied.' }}
                </p>
              </div>

            </div>
          </div>

          <!-- ④ Campaign theme — required -->
          <div class="glass rounded-2xl p-4 border"
            :class="!selectedTheme ? 'border-red-500/20' : 'border-border'">
            <div class="flex items-center gap-2 mb-3">
              <p class="text-xs font-medium text-muted uppercase tracking-widest">Campaign theme</p>
              <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-500/15 border border-red-500/30 text-red-400 font-semibold uppercase tracking-wide">Required</span>
            </div>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="theme in themes" :key="theme"
                @click="selectedTheme = theme"
                class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all capitalize"
                :class="selectedTheme === theme
                  ? 'border-accent/50 text-accent bg-accent-glow'
                  : 'border-border text-muted hover:border-accent/30 hover:text-white'"
              >{{ theme }}</button>
            </div>
          </div>

          <!-- Validation checklist (shown when not ready) -->
          <div v-if="missingSteps.length && !noCredits" class="rounded-xl border border-border bg-canvas p-3 space-y-1.5">
            <p class="text-[11px] font-semibold text-muted uppercase tracking-widest mb-2">Still needed</p>
            <div v-for="step in missingSteps" :key="step.key" class="flex items-center gap-2 text-xs text-muted">
              <i class="fa-regular fa-circle-dot text-[10px] text-accent/50 flex-shrink-0"></i>
              {{ step.label }}
            </div>
          </div>

          <!-- Credits out banner -->
          <div v-if="noCredits" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
            <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
            <div class="min-w-0">
              <p class="font-semibold">Not enough credits</p>
              <p class="text-xs text-red-400/70 mt-0.5">
                This generation needs <span class="font-semibold text-red-400">{{ estimatedCost }}</span>
                credit{{ estimatedCost !== 1 ? 's' : '' }} — you have
                <span class="font-semibold text-red-400">{{ auth.credits }}</span>.
                <RouterLink to="/billing" class="underline underline-offset-2 hover:text-red-300 transition-colors ml-1">Upgrade your plan →</RouterLink>
              </p>
            </div>
          </div>

          <!-- Generate action bar -->
          <div class="flex items-center justify-between pt-2">
            <p class="text-sm" :class="noCredits ? 'text-red-400' : 'text-muted'">
              <span class="font-medium" :class="noCredits ? 'text-red-400' : 'text-white'">{{ estimatedCost }}</span>
              credit{{ estimatedCost !== 1 ? 's' : '' }} required ·
              <span :class="noCredits ? 'text-red-400 font-semibold' : 'text-muted'">{{ auth.credits }} remaining</span>
            </p>
            <button
              @click="launchCampaign"
              :disabled="!canGenerate"
              class="btn-accent disabled:opacity-40 disabled:cursor-not-allowed transition-opacity"
            >
              <i v-if="generating" class="fa-solid fa-spinner fa-spin mr-2"></i>
              {{ generating ? 'Generating…' : 'Generate Campaign' }}
            </button>
          </div>
        </div>
      </template>
    </main>

    <!-- ═══════════════════════════════════════
         RIGHT PANEL — Output
    ═══════════════════════════════════════ -->
    <aside class="w-80 flex-shrink-0 flex flex-col border-l border-border bg-surface">
      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-4 border-b border-border flex-shrink-0">
        <h2 class="text-xs font-semibold text-white uppercase tracking-[0.2em]">Output</h2>
        <RouterLink v-if="latestCampaignId" :to="`/studio/campaigns/${latestCampaignId}`" class="text-xs text-accent hover:text-accent/80 transition-colors">View campaign</RouterLink>
      </div>

      <!-- ── Preview area ── -->

      <!-- A: No garment selected -->
      <div v-if="!activeGarment" class="p-4 flex-shrink-0">
        <div class="h-48 rounded-2xl border border-dashed border-border flex flex-col items-center justify-center gap-3 bg-canvas">
          <i class="fa-solid fa-person-dress text-3xl text-muted/20"></i>
          <p class="text-xs text-muted text-center px-6 leading-relaxed">Select a garment to see model preview here</p>
        </div>
      </div>

      <!-- B: Generated assets ready -->
      <div v-else-if="latestAssets.length" class="p-3 flex-1 overflow-y-auto">
        <div class="flex items-center gap-1.5 mb-2">
          <i class="fa-solid fa-wand-magic-sparkles text-[10px] text-accent"></i>
          <p class="text-[10px] font-semibold text-muted uppercase tracking-widest">AI Generated</p>
        </div>
        <div class="grid grid-cols-2 gap-1.5">
          <div
            v-for="asset in latestAssets.slice(0, 4)" :key="asset.id"
            @click="openPreview(asset)"
            class="aspect-[3/4] rounded-xl overflow-hidden bg-canvas border border-border relative group cursor-pointer"
          >
            <img :src="`/storage/${asset.file_path}`" :alt="asset.asset_subtype" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-between p-2">
              <span class="text-[9px] text-white font-medium capitalize">{{ asset.asset_subtype }}</span>
              <i class="fa-solid fa-expand text-white/70 text-[10px]"></i>
            </div>
          </div>
          <div
            v-for="n in Math.max(0, 4 - latestAssets.slice(0,4).length)" :key="'ph'+n"
            class="aspect-[3/4] rounded-xl border border-dashed border-border bg-canvas flex items-center justify-center"
          >
            <i class="fa-solid fa-image text-muted/20 text-sm"></i>
          </div>
        </div>
        <RouterLink
          :to="`/studio/campaigns/${latestCampaignId}`"
          class="mt-2.5 flex items-center justify-center gap-1.5 py-2.5 rounded-xl border border-accent/30 bg-accent/5 text-xs text-accent hover:bg-accent/10 transition-colors"
        >
          View all {{ latestAssets.length }} outputs <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </RouterLink>
        <RouterLink
          to="/studio/campaigns"
          class="mt-1.5 flex items-center justify-center gap-1.5 py-2 rounded-xl text-xs text-muted hover:text-white transition-colors"
        >
          <i class="fa-solid fa-layer-group text-[10px]"></i> All campaigns
        </RouterLink>
      </div>

      <!-- C: Garment selected, no outputs yet -->
      <div v-else class="p-3 flex-shrink-0">
        <p class="text-[10px] font-semibold text-muted uppercase tracking-widest mb-2">Original Garment</p>
        <div class="relative rounded-xl overflow-hidden h-52 border border-border">
          <img
            :src="`/storage/${activeGarment.original_image}`"
            :alt="activeGarment.name || 'Garment'"
            class="w-full h-full object-cover object-top"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent flex items-end p-3">
            <div class="w-full">
              <p class="text-xs font-semibold text-white truncate">{{ activeGarment.name || activeGarment.category || 'Garment' }}</p>
              <p class="text-[10px] mt-0.5" :class="activeGarment.analyzed ? 'text-green-400' : 'text-accent animate-pulse'">
                {{ activeGarment.analyzed ? 'Analyzed · Ready to generate' : 'Analyzing garment…' }}
              </p>
            </div>
          </div>
          <div v-if="generating" class="absolute inset-0 bg-black/65 flex flex-col items-center justify-center gap-3">
            <div class="w-12 h-12 rounded-full border-2 border-accent/30 border-t-accent animate-spin"></div>
            <p class="text-xs font-semibold text-accent">Placing on model…</p>
            <p class="text-[10px] text-muted">AI is generating now</p>
          </div>
        </div>
        <p v-if="!generating" class="mt-2 text-[10px] text-muted text-center px-2 leading-relaxed">
          Select output types and click <span class="text-white font-medium">Generate Campaign</span>
        </p>
      </div>

    </aside>

  </div>

  <!-- ═══════════════════════════════════════
       MODEL PICKER POPUP
  ═══════════════════════════════════════ -->
  <Transition name="modal">
    <div v-if="showModelPicker" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showModelPicker = false"></div>
      <div class="relative bg-surface border border-border rounded-2xl shadow-2xl flex flex-col overflow-hidden"
        style="width: min(900px, 96vw); max-height: 88vh">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-5 border-b border-border flex-shrink-0">
          <div>
            <h2 class="text-white font-semibold text-lg flex items-center gap-2">
              <i class="fa-solid fa-person-dress text-accent text-base"></i>
              Choose a Model
            </h2>
            <p class="text-muted text-xs mt-0.5">Select the persona for your campaign. This determines who wears the garment.</p>
          </div>
          <button @click="showModelPicker = false"
            class="text-muted hover:text-white transition-colors w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/5">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="personasLoading" class="flex-1 flex items-center justify-center py-16">
          <div class="w-8 h-8 rounded-full border-2 border-accent/30 border-t-accent animate-spin"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="!personas.length" class="flex-1 flex flex-col items-center justify-center py-16 text-center px-8">
          <i class="fa-solid fa-person-dress text-4xl text-muted/20 mb-4"></i>
          <p class="text-muted text-sm">No model personas available. An admin needs to add them in the Model Personas panel.</p>
        </div>

        <!-- Model grid -->
        <div v-else class="flex-1 overflow-y-auto p-5">
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            <button
              v-for="p in personas" :key="p.id"
              @click="selectedPersona = p; showModelPicker = false"
              class="group relative flex flex-col rounded-2xl border overflow-hidden transition-all duration-200 text-left"
              :class="selectedPersona?.id === p.id
                ? 'border-accent ring-2 ring-accent/30 scale-[1.02]'
                : 'border-border hover:border-accent/40 hover:scale-[1.01]'"
            >
              <!-- Avatar image — tall portrait crop -->
              <div class="relative w-full bg-canvas" style="aspect-ratio: 2/3">
                <img v-if="p.avatar_url" :src="p.avatar_url" :alt="p.name"
                  class="w-full h-full object-cover object-top" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <i :class="personaGenderIcon(p.gender)" class="text-4xl text-muted/20"></i>
                </div>

                <!-- Selected overlay -->
                <div v-if="selectedPersona?.id === p.id"
                  class="absolute inset-0 bg-accent/20 flex items-center justify-center">
                  <div class="w-10 h-10 rounded-full bg-accent flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-check text-canvas text-base"></i>
                  </div>
                </div>

                <!-- Gender + age badge top-left -->
                <div class="absolute top-2 left-2">
                  <span class="text-[9px] px-1.5 py-0.5 rounded-full border font-semibold backdrop-blur-sm bg-black/50"
                    :class="personaGenderBadge(p.gender)">
                    {{ personaGenderShort(p.gender) }}{{ p.age ? ' · ' + p.age : '' }}
                  </span>
                </div>
              </div>

              <!-- Info -->
              <div class="p-2.5 bg-surface flex-1">
                <p class="text-xs font-semibold text-white truncate">{{ p.name }}</p>
                <p v-if="p.nationality" class="text-[10px] text-muted truncate mt-0.5">{{ p.nationality }}</p>
                <p v-if="p.best_for" class="text-[9px] text-muted/60 truncate mt-1 leading-relaxed">
                  <i class="fa-solid fa-star text-accent/30 mr-0.5"></i>{{ p.best_for }}
                </p>
              </div>
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-6 py-4 border-t border-border flex-shrink-0 bg-canvas/50">
          <p v-if="selectedPersona" class="text-xs text-accent font-medium">
            <i class="fa-solid fa-check mr-1"></i>{{ selectedPersona.name }} selected
          </p>
          <p v-else class="text-xs text-muted">No model selected yet</p>
          <div class="flex items-center gap-3">
            <button @click="showModelPicker = false"
              class="px-4 py-2 rounded-xl text-xs text-muted hover:text-white border border-border hover:border-accent/30 transition-colors">
              Cancel
            </button>
            <button
              @click="showModelPicker = false"
              :disabled="!selectedPersona"
              class="px-5 py-2 rounded-xl text-xs font-semibold bg-accent text-canvas hover:bg-accent/90 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
              Confirm Selection
            </button>
          </div>
        </div>

      </div>
    </div>
  </Transition>

  <!-- ═══════════════════════════════════════
       IMAGE PREVIEW + REGENERATE POPUP
  ═══════════════════════════════════════ -->
  <Transition name="modal">
    <div v-if="previewAsset" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/90 backdrop-blur-sm" @click="previewAsset = null"></div>
      <div class="relative flex flex-col lg:flex-row bg-surface border border-border rounded-2xl shadow-2xl overflow-hidden"
        style="width: min(960px, 96vw); max-height: 92vh">

        <!-- Image side -->
        <div class="flex-1 min-h-0 bg-canvas flex items-center justify-center overflow-hidden" style="min-height: 280px">
          <img
            :src="`/storage/${previewAsset.file_path}`"
            :alt="previewAsset.asset_subtype"
            class="max-w-full max-h-full object-contain"
            style="max-height: 88vh"
          />
        </div>

        <!-- Controls side -->
        <div class="w-full lg:w-72 flex flex-col border-t lg:border-t-0 lg:border-l border-border flex-shrink-0">
          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-border flex-shrink-0">
            <div>
              <p class="text-sm font-semibold text-white capitalize">{{ previewAsset.asset_subtype }}</p>
              <p class="text-[11px] text-muted mt-0.5 capitalize">{{ previewAsset.asset_type }} &middot; {{ previewAsset.status }}</p>
            </div>
            <button @click="previewAsset = null"
              class="w-8 h-8 rounded-lg flex items-center justify-center text-muted hover:text-white hover:bg-white/5 transition-colors">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Regen controls -->
          <div class="flex-1 overflow-y-auto p-5 space-y-4">
            <div>
              <p class="text-xs font-semibold text-white mb-1">Describe changes</p>
              <p class="text-[11px] text-muted leading-relaxed mb-3">Tell the AI what to change — background, lighting, pose, colours, style, etc. Leave blank to regenerate with the original settings.</p>
              <textarea
                v-model="regenInstructions"
                placeholder="e.g. change background to outdoor garden, warmer golden lighting, more confident pose…"
                rows="5"
                class="w-full rounded-xl bg-canvas border border-border text-xs text-white placeholder-muted/40 p-3 resize-none focus:outline-none focus:border-accent/40 transition-colors"
              ></textarea>
            </div>

            <!-- Regenerating status -->
            <div v-if="regenerating"
              class="flex items-center gap-2 px-3 py-2.5 rounded-lg bg-accent/10 border border-accent/30 text-xs text-accent">
              <i class="fa-solid fa-spinner fa-spin text-[10px] flex-shrink-0"></i>
              Regenerating with AI — this may take up to 30s…
            </div>
          </div>

          <!-- Footer actions -->
          <div class="px-5 py-4 border-t border-border space-y-2 flex-shrink-0">
            <button
              @click="regenerateAsset"
              :disabled="regenerating"
              class="w-full py-2.5 rounded-xl text-xs font-semibold bg-accent text-canvas hover:bg-accent/90 transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <i v-if="regenerating" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-wand-magic-sparkles"></i>
              {{ regenerating ? 'Regenerating…' : 'Regenerate Image' }}
            </button>
            <button
              @click="previewAsset = null"
              class="w-full py-2 rounded-xl text-xs text-muted hover:text-white border border-border hover:border-accent/30 transition-colors"
            >
              Close Preview
            </button>
          </div>
        </div>

      </div>
    </div>
  </Transition>

</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useGarmentStore }  from '@/stores/garment'
import { useCampaignStore } from '@/stores/campaign'
import { useAuthStore }     from '@/stores/auth'
import api from '@/lib/api'

const garmentStore  = useGarmentStore()
const campaignStore = useCampaignStore()
const auth          = useAuthStore()

const fileInput        = ref(null)
const uploading        = ref(false)
const dragging         = ref(false)
const activeGarment    = ref(null)
const retryingAnalysis = ref(false)
const assetSearch      = ref('')
const generating       = ref(false)
const latestCampaignId = ref(null)
const latestAssets     = ref([])

// Campaign builder state — nothing pre-selected; everything is mandatory
const selectedTypes    = ref([])
const selectedSubtypes = ref([])
const selectedTheme    = ref(null)

// Scene builder state
const sceneBackground  = ref(null)
const sceneProps       = ref([])   // multi-select (optional)
const sceneFloor       = ref(null)
const sceneShadow      = ref(null)
const sceneFilter      = ref(null)

// Model picker popup
const showModelPicker = ref(false)

// Image preview + regeneration popup
const previewAsset      = ref(null)
const regenInstructions = ref('')
const regenerating      = ref(false)

const SCENE_BACKGROUND_OPTIONS = [
  { value: 'white_studio',   label: 'White Studio', prompt: 'pure white seamless studio background' },
  { value: 'cream',          label: 'Cream',        prompt: 'warm cream seamless paper background' },
  { value: 'gradient_grey',  label: 'Grey Fade',    prompt: 'soft neutral grey gradient studio background' },
  { value: 'outdoor_garden', label: 'Garden',       prompt: 'lush outdoor garden with soft natural bokeh' },
  { value: 'urban_street',   label: 'Urban Street', prompt: 'urban street environment, soft blurred city bokeh' },
  { value: 'marble',         label: 'Marble',       prompt: 'white marble floor and wall studio background' },
  { value: 'wood_floor',     label: 'Wood Floor',   prompt: 'light oak wood floor with clean white wall' },
  { value: 'rooftop',        label: 'Rooftop',      prompt: 'rooftop terrace, city skyline softly blurred in background' },
  { value: 'showroom',       label: 'Showroom',     prompt: 'minimalist luxury fashion showroom background' },
]
const SCENE_PROP_OPTIONS = [
  { value: 'chair',  label: 'Chair',        prompt: 'minimalist white chair beside the model' },
  { value: 'stool',  label: 'Stool',        prompt: 'wooden bar stool beside the model' },
  { value: 'rack',   label: 'Clothes Rack', prompt: 'clothing rack with garments visible in background' },
  { value: 'plants', label: 'Plants',       prompt: 'lush tropical potted plants on the side' },
  { value: 'column', label: 'Column',       prompt: 'white plaster architectural column beside the model' },
  { value: 'mirror', label: 'Mirror',       prompt: 'tall ornate floor mirror beside the model' },
  { value: 'steps',  label: 'Steps',        prompt: 'white marble steps under and around the model' },
  { value: 'bag',    label: 'Handbag',      prompt: 'luxury handbag held or placed beside the model' },
]
const SCENE_FLOOR_OPTIONS = [
  { value: 'auto',       label: 'Auto',          prompt: null },
  { value: 'white',      label: 'White Floor',   prompt: 'standing on a plain white polished floor' },
  { value: 'carpet',     label: 'Carpet',        prompt: 'standing on a plush neutral carpet' },
  { value: 'marble',     label: 'Marble Floor',  prompt: 'standing on a white marble tile floor' },
  { value: 'wood',       label: 'Wood Floor',    prompt: 'standing on a warm oak hardwood floor' },
  { value: 'concrete',   label: 'Concrete',      prompt: 'standing on polished concrete floor' },
  { value: 'grass',      label: 'Grass',         prompt: 'standing on lush green grass' },
  { value: 'reflection', label: 'Reflection',    prompt: 'standing on a highly reflective glossy surface with mirror reflection' },
]
const SCENE_SHADOW_OPTIONS = [
  { value: 'none',         label: 'None',         prompt: null },
  { value: 'soft_natural', label: 'Soft Natural', prompt: 'soft natural diffused shadow on the floor' },
  { value: 'side_shadow',  label: 'Side Shadow',  prompt: 'dramatic directional side shadow cast on the background wall' },
  { value: 'drop_shadow',  label: 'Drop Shadow',  prompt: 'clean sharp drop shadow below the feet' },
  { value: 'rim_glow',     label: 'Rim Glow',     prompt: 'warm backlit rim glow around the silhouette' },
  { value: 'dual_shadow',  label: 'Dual Shadow',  prompt: 'soft dual shadows from two opposing light sources' },
]
const SCENE_FILTER_OPTIONS = [
  { value: 'natural',       label: 'Natural',       prompt: null },
  { value: 'editorial',     label: 'Editorial',     prompt: 'high-end editorial fashion photography style' },
  { value: 'warm',          label: 'Warm Tone',     prompt: 'warm golden hour tone, soft warm color grading' },
  { value: 'cool',          label: 'Cool Tone',     prompt: 'cool desaturated editorial color grade' },
  { value: 'high_contrast', label: 'High Contrast', prompt: 'high contrast dramatic chiaroscuro lighting' },
  { value: 'matte_film',    label: 'Matte Film',    prompt: 'matte film photography look, subtle grain, lifted blacks' },
  { value: 'cinematic',     label: 'Cinematic',     prompt: 'cinematic anamorphic color grade, subtle lens flare' },
]

// Assembled scene prompt suffix sent to the AI
const scenePromptSuffix = computed(() => {
  const parts = []
  const bg     = SCENE_BACKGROUND_OPTIONS.find(o => o.value === sceneBackground.value)?.prompt
  const floor  = SCENE_FLOOR_OPTIONS.find(o => o.value === sceneFloor.value)?.prompt
  const shadow = SCENE_SHADOW_OPTIONS.find(o => o.value === sceneShadow.value)?.prompt
  const filter = SCENE_FILTER_OPTIONS.find(o => o.value === sceneFilter.value)?.prompt
  const props  = sceneProps.value
    .map(v => SCENE_PROP_OPTIONS.find(o => o.value === v)?.prompt)
    .filter(Boolean)
  if (bg)     parts.push(bg)
  if (floor)  parts.push(floor)
  parts.push(...props)
  if (shadow) parts.push(shadow)
  if (filter) parts.push(filter)
  return parts.join(', ')
})

function toggleSceneProp(value) {
  const idx = sceneProps.value.indexOf(value)
  if (idx >= 0) sceneProps.value.splice(idx, 1)
  else sceneProps.value.push(value)
}

// Credits check
const estimatedCost = computed(() => selectedTypes.value.length)
const noCredits     = computed(() => auth.credits < estimatedCost.value)

// Validation — collect all missing required selections
const missingSteps = computed(() => {
  const steps = []
  if (!selectedTypes.value.length)
    steps.push({ key: 'type', label: 'Select at least one output type' })
  if (selectedTypes.value.includes('photo')) {
    if (!selectedPersona.value)
      steps.push({ key: 'model', label: 'Select a model' })
    if (!selectedSubtypes.value.length)
      steps.push({ key: 'style', label: 'Select at least one photo style' })
  }
  if (!selectedTheme.value)
    steps.push({ key: 'theme', label: 'Select a campaign theme' })
  if (selectedTypes.value.includes('photo')) {
    if (!sceneBackground.value)
      steps.push({ key: 'scene_bg',     label: 'Scene Builder: select a background' })
    if (!sceneFloor.value)
      steps.push({ key: 'scene_floor',  label: 'Scene Builder: select a floor' })
    if (!sceneShadow.value)
      steps.push({ key: 'scene_shadow', label: 'Scene Builder: select a shadow' })
    if (!sceneFilter.value)
      steps.push({ key: 'scene_filter', label: 'Scene Builder: select a style / filter' })
  }
  return steps
})
const canGenerate = computed(() => !missingSteps.value.length && !noCredits.value && !generating.value)

// Model personas
const personas       = ref([])
const personasLoading = ref(false)
const selectedPersona = ref(null)

async function fetchPersonas() {
  personasLoading.value = true
  try {
    const res = await api.get('/model-personas')
    personas.value = res.data
  } catch { /* silent */ }
  finally { personasLoading.value = false }
}

function personaGenderIcon(g) {
  return { female: 'fa-solid fa-person-dress', male: 'fa-solid fa-person',
    girl: 'fa-solid fa-person-dress', boy: 'fa-solid fa-person',
    child: 'fa-solid fa-child', non_binary: 'fa-solid fa-person' }[g] || 'fa-solid fa-person'
}
function personaGenderShort(g) {
  return { female: 'F', male: 'M', girl: 'Girl', boy: 'Boy', child: 'Child', non_binary: 'NB' }[g] || g
}
function personaGenderBadge(g) {
  return {
    female: 'border-pink-500/30 text-pink-400',
    male:   'border-blue-500/30 text-blue-400',
    girl:   'border-pink-500/30 text-pink-300',
    boy:    'border-blue-500/30 text-blue-300',
    child:  'border-yellow-500/30 text-yellow-400',
    non_binary: 'border-purple-500/30 text-purple-400',
  }[g] || 'border-border text-muted'
}

const outputTypes = [
  { key: 'photo',  icon: 'fa-solid fa-camera',          label: 'Model Photos',    desc: 'Studio, lifestyle, editorial' },
  { key: 'video',  icon: 'fa-solid fa-film',             label: 'Videos',          desc: 'Reels, TikTok, Shorts' },
  { key: 'ad',     icon: 'fa-solid fa-bullhorn',         label: 'Ad Creatives',    desc: 'Meta, Google Display' },
  { key: 'copy',   icon: 'fa-solid fa-pen-nib',          label: 'Copy & Captions', desc: 'Descriptions, hashtags' },
  { key: 'social', icon: 'fa-solid fa-mobile-screen',    label: 'Social Posts',    desc: 'Ready-to-publish content' },
  { key: 'seo',    icon: 'fa-solid fa-magnifying-glass', label: 'SEO Content',     desc: 'Product page copy' },
]
const photoSubtypes = [
  { key: 'studio',    label: 'Studio' },
  { key: 'lifestyle', label: 'Lifestyle' },
  { key: 'editorial', label: 'Editorial' },
  { key: 'reels',     label: 'Reels' },
]
const themes = ['default', 'summer', 'winter', 'luxury', 'streetwear', 'eid', 'black friday', 'minimal', 'beach']

const filteredGarments = computed(() => {
  if (!assetSearch.value) return garmentStore.garments
  const q = assetSearch.value.toLowerCase()
  return garmentStore.garments.filter(g =>
    (g.name || '').toLowerCase().includes(q) ||
    (g.category || '').toLowerCase().includes(q)
  )
})

onMounted(() => {
  garmentStore.fetchAll()
  fetchPersonas()
})

function handleDrop(e) {
  dragging.value = false
  const file = e.dataTransfer.files[0]
  if (file?.type.startsWith('image/')) processUpload(file)
}

function handleFileSelect(e) {
  const file = e.target.files[0]
  if (file) processUpload(file)
}

async function processUpload(file) {
  uploading.value = true
  try {
    const garment = await garmentStore.upload(file)
    activeGarment.value = garment
    pollGarment(garment.id)
  } catch (err) {
    console.error(err)
  } finally {
    uploading.value = false
  }
}

async function pollGarment(id) {
  for (let i = 0; i < 20; i++) {
    await new Promise(r => setTimeout(r, 3000))
    const g = await garmentStore.fetchOne(id)
    if (g.analyzed) { activeGarment.value = g; return }
  }
}

async function retryAnalysis() {
  if (!activeGarment.value || retryingAnalysis.value) return
  retryingAnalysis.value = true
  try {
    const { data } = await api.post(`/garments/${activeGarment.value.id}/retry-analysis`)
    activeGarment.value = data
    const idx = garmentStore.garments.findIndex(g => g.id === data.id)
    if (idx >= 0) garmentStore.garments[idx] = data
    if (!data.analyzed) pollGarment(data.id)
  } catch (err) {
    console.error('Retry analysis failed', err)
  } finally {
    retryingAnalysis.value = false
  }
}

function selectGarment(g) {
  activeGarment.value = g
  latestCampaignId.value = null
  latestAssets.value = []
  selectedPersona.value = null
  selectedTypes.value = []
  selectedSubtypes.value = []
  selectedTheme.value = null
  sceneBackground.value = null
  sceneFloor.value = null
  sceneShadow.value = null
  sceneFilter.value = null
  sceneProps.value = []
}

async function removeGarment(id) {
  if (activeGarment.value?.id === id) activeGarment.value = null
  await garmentStore.remove(id)
}

function toggleType(key) {
  const idx = selectedTypes.value.indexOf(key)
  if (idx >= 0) selectedTypes.value.splice(idx, 1)
  else selectedTypes.value.push(key)
}

function toggleSubtype(key) {
  const idx = selectedSubtypes.value.indexOf(key)
  if (idx >= 0) selectedSubtypes.value.splice(idx, 1)
  else selectedSubtypes.value.push(key)
}

async function launchCampaign() {
  generating.value = true
  latestCampaignId.value = null
  latestAssets.value = []
  try {
    const campaign = await campaignStore.create({
      name:        `${activeGarment.value.category ?? 'Campaign'} — ${selectedTheme.value ?? 'default'}`,
      theme:       selectedTheme.value,
      garment_ids: [activeGarment.value.id],
    })
    await campaignStore.generate(campaign.id, {
      types:        selectedTypes.value,
      subtypes:     selectedSubtypes.value,
      persona_id:   selectedPersona.value?.id ?? null,
      scene_prompt: scenePromptSuffix.value || null,
    })
    latestCampaignId.value = campaign.id
    // With sync queue the job finishes before generate() returns — fetch immediately
    const assets = await campaignStore.fetchAssets(campaign.id)
    const ready = (assets ?? []).filter(a => a.status === 'ready' && a.file_path)
    if (ready.length) {
      latestAssets.value = ready
    } else {
      // Fallback: poll (for async queue or if file write takes a moment)
      pollAssets(campaign.id)
    }
  } catch (err) {
    console.error(err)
  } finally {
    generating.value = false
  }
}

function pollAssets(campaignId) {
  let attempts = 0
  const timer = setInterval(async () => {
    attempts++
    if (attempts > 60) { clearInterval(timer); return } // up to 5 min
    try {
      const list = await campaignStore.fetchAssets(campaignId)
      const ready = (list ?? []).filter(a => a.status === 'ready' && a.file_path)
      if (ready.length) { latestAssets.value = ready; clearInterval(timer) }
    } catch { /* silent */ }
  }, 5000)
}

function openPreview(asset) {
  previewAsset.value = asset
  regenInstructions.value = ''
}

async function regenerateAsset() {
  if (!previewAsset.value || regenerating.value) return
  regenerating.value = true
  try {
    const { data } = await api.post(`/assets/${previewAsset.value.id}/regenerate`, {
      instructions: regenInstructions.value || null,
    })
    // Replace in latestAssets so the thumbnail updates
    const idx = latestAssets.value.findIndex(a => a.id === data.id)
    if (idx >= 0) latestAssets.value[idx] = data
    previewAsset.value = data
    regenInstructions.value = ''
  } catch (err) {
    console.error('Regeneration failed', err)
  } finally {
    regenerating.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .relative, .modal-leave-active .relative { transition: transform 0.2s ease; }
.modal-enter-from .relative, .modal-leave-to .relative { transform: scale(0.97) translateY(8px); }
</style>
