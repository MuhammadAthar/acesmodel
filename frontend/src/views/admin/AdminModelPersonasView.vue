<template>
  <div>

    <!-- Header bar -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-white text-lg font-semibold">Model Personas</h2>
        <p class="text-[#555] text-sm mt-0.5">Curated AI model personas available to all clients in the Studio.</p>
      </div>
      <button @click="openForm()" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[#e8d5b7] text-[#0a0a0a] text-sm font-semibold hover:bg-[#d4c19e] transition-colors">
        <i class="fa-solid fa-plus"></i> Add Persona
      </button>
    </div>

    <!-- Filter bar -->
    <div class="flex items-center gap-3 mb-4 flex-wrap">
      <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[#555] text-sm"></i>
        <input v-model="search" placeholder="Search personas…"
          class="bg-[#111] border border-[#1e1e1e] rounded-lg pl-9 pr-4 py-2 text-sm text-white placeholder-[#555] focus:outline-none focus:border-[#333] w-56" />
      </div>
      <select v-model="filterGender"
        class="bg-[#111] border border-[#1e1e1e] rounded-lg px-3 py-2 text-sm text-[#888] focus:outline-none focus:border-[#333]">
        <option value="">All genders</option>
        <option v-for="g in genderOptions" :key="g.value" :value="g.value">{{ g.label }}</option>
      </select>
    </div>

    <!-- Persona grid -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-8 h-8 rounded-full border-2 border-[#e8d5b7]/30 border-t-[#e8d5b7] animate-spin"></div>
    </div>

    <div v-else-if="!filtered.length" class="flex flex-col items-center justify-center py-20 text-center">
      <i class="fa-solid fa-person-dress text-4xl text-[#333] mb-4"></i>
      <p class="text-[#555] text-sm">No personas found.</p>
    </div>

    <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
      <div v-for="p in filtered" :key="p.id"
        class="bg-[#111] border border-[#1e1e1e] rounded-xl overflow-hidden group hover:border-[#333] transition-colors">

        <!-- Avatar -->
        <div class="relative aspect-[3/4] bg-[#0a0a0a] overflow-hidden">
          <img v-if="p.avatar_url" :src="p.avatar_url" :alt="p.name"
            class="w-full h-full object-cover object-top" />
          <div v-else class="w-full h-full flex items-center justify-center">
            <i :class="genderIcon(p.gender)" class="text-4xl text-[#333]"></i>
          </div>
          <!-- Active badge -->
          <div class="absolute top-2 left-2">
            <span v-if="p.is_active" class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-green-500/20 text-green-400 border border-green-500/30">Active</span>
            <span v-else class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-[#333]/60 text-[#888]">Hidden</span>
          </div>
          <!-- Actions overlay -->
          <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center gap-2 pb-3">
            <button @click="openForm(p)" class="px-3 py-1.5 rounded-lg bg-[#e8d5b7] text-[#0a0a0a] text-xs font-semibold hover:bg-[#d4c19e]">
              <i class="fa-solid fa-pen mr-1"></i>Edit
            </button>
            <button @click="remove(p)" class="px-3 py-1.5 rounded-lg bg-red-500/20 text-red-400 text-xs font-semibold hover:bg-red-500/30 border border-red-500/30">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>

        <!-- Info -->
        <div class="p-3">
          <div class="flex items-start justify-between gap-1 mb-1">
            <p class="text-white text-sm font-semibold truncate">{{ p.name }}</p>
            <span class="flex-shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-medium border"
              :class="genderBadge(p.gender)">{{ genderLabel(p.gender) }}</span>
          </div>
          <p class="text-[#666] text-xs">
            {{ [p.age ? p.age + ' yrs' : null, p.nationality].filter(Boolean).join(' · ') || '—' }}
          </p>
          <p v-if="p.ethnicity" class="text-[#555] text-[11px] mt-0.5 truncate">{{ p.ethnicity }}</p>
          <p v-if="p.best_for" class="text-[#444] text-[11px] mt-1.5 leading-relaxed line-clamp-2">
            <i class="fa-solid fa-star text-[#e8d5b7]/50 mr-1"></i>{{ p.best_for }}
          </p>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════
         FORM MODAL
    ════════════════════════════════════════ -->
    <Transition name="modal">
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70" @click="closeForm"></div>
        <div class="relative bg-[#111] border border-[#1e1e1e] rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
          <!-- Modal header -->
          <div class="flex items-center justify-between px-6 py-5 border-b border-[#1e1e1e] sticky top-0 bg-[#111] z-10">
            <div>
              <h3 class="text-white font-semibold">{{ editingId ? 'Edit' : 'Add' }} Model Persona</h3>
              <p class="text-[#555] text-xs mt-0.5">Fill in the persona details. More detail = better AI output.</p>
            </div>
            <button @click="closeForm" class="text-[#555] hover:text-white transition-colors text-lg">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Form body -->
          <form @submit.prevent="save" class="p-6 space-y-5">

            <!-- Row 1: Name + Gender -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-[#888] mb-1.5">Name <span class="text-red-400">*</span></label>
                <input v-model="form.name" type="text" required placeholder="e.g. Sophia"
                  class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
              </div>
              <div>
                <label class="block text-xs font-medium text-[#888] mb-1.5">Gender <span class="text-red-400">*</span></label>
                <select v-model="form.gender" required
                  class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-[#444]">
                  <option value="" disabled>Select…</option>
                  <option v-for="g in genderOptions" :key="g.value" :value="g.value">{{ g.label }}</option>
                </select>
              </div>
            </div>

            <!-- Row 2: Age + Nationality -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-[#888] mb-1.5">Age</label>
                <input v-model.number="form.age" type="number" min="1" max="80" placeholder="e.g. 24"
                  class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
              </div>
              <div>
                <label class="block text-xs font-medium text-[#888] mb-1.5">Nationality</label>
                <input v-model="form.nationality" type="text" placeholder="e.g. Pakistani"
                  class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
              </div>
            </div>

            <!-- Row 3: Ethnicity + Skin tone -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-[#888] mb-1.5">Ethnicity</label>
                <input v-model="form.ethnicity" type="text" placeholder="e.g. South Asian"
                  class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
              </div>
              <div>
                <label class="block text-xs font-medium text-[#888] mb-1.5">Skin Tone</label>
                <input v-model="form.skin_tone" type="text" placeholder="e.g. warm olive"
                  class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
              </div>
            </div>

            <!-- Row 4: Body type + Hair -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-[#888] mb-1.5">Body Type</label>
                <select v-model="form.body_type"
                  class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-[#444]">
                  <option value="">Select…</option>
                  <option value="slim">Slim</option>
                  <option value="petite">Petite</option>
                  <option value="average">Average</option>
                  <option value="athletic">Athletic</option>
                  <option value="curvy">Curvy</option>
                  <option value="plus_size">Plus Size</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-[#888] mb-1.5">Hair</label>
                <input v-model="form.hair" type="text" placeholder="e.g. long dark wavy hair"
                  class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
              </div>
            </div>

            <!-- Best For -->
            <div>
              <label class="block text-xs font-medium text-[#888] mb-1.5">Best For</label>
              <input v-model="form.best_for" type="text" placeholder="e.g. luxury wear, bridal, evening gowns, Eid collections"
                class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
              <p class="text-[#444] text-[11px] mt-1">Shown to clients as a hint for which garments this model fits best.</p>
            </div>

            <!-- Avatar URL + AI Generate -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-medium text-[#888]">Avatar / Preview Image URL</label>
                <button type="button" @click="openGenerateDialog"
                  :disabled="!form.name || !form.gender"
                  class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold transition-all border"
                  :class="(!form.name || !form.gender)
                    ? 'bg-[#1a1a1a] border-[#333] text-[#666] cursor-not-allowed'
                    : 'bg-[#e8d5b7]/10 border-[#e8d5b7]/30 text-[#e8d5b7] hover:bg-[#e8d5b7]/20 hover:border-[#e8d5b7]/50'"
                >
                  <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i>
                  Generate with AI
                </button>
              </div>
              <!-- Avatar preview + URL input row -->
              <div class="flex gap-3 items-start">
                <div class="flex-shrink-0 w-16 h-20 rounded-lg border border-[#1e1e1e] bg-[#0a0a0a] overflow-hidden">
                  <img v-if="form.avatar_url" :src="form.avatar_url" class="w-full h-full object-cover object-top" />
                  <div v-else class="w-full h-full flex items-center justify-center">
                    <i class="fa-solid fa-image text-[#333] text-xl"></i>
                  </div>
                </div>
                <div class="flex-1">
                  <input v-model="form.avatar_url" type="text" placeholder="https://… or /storage/… or generate with AI →"
                    class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
                  <p class="text-[#444] text-[11px] mt-1.5">Paste any image URL, or click "Generate with AI" to create a portrait. Saved avatars appear on the public landing page.</p>
                </div>
              </div>
              <p v-if="generateAvatarError" class="text-red-400 text-xs mt-2">{{ generateAvatarError }}</p>
            </div>

            <!-- Custom AI Description -->
            <div>
              <label class="block text-xs font-medium text-[#888] mb-1.5">Custom AI Description <span class="text-[#555]">(optional — overrides auto-generated)</span></label>
              <textarea v-model="form.description" rows="3"
                placeholder="e.g. a graceful 23-year-old Pakistani female fashion model, warm olive skin, slim figure, long dark wavy hair, confident elegant posture"
                class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444] resize-none"></textarea>
              <p class="text-[#444] text-[11px] mt-1">This exact text will be injected into the AI image generation prompt. Leave blank to auto-build from the fields above.</p>
            </div>

            <!-- Preview auto-description -->
            <div v-if="!form.description && form.name" class="p-3 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
              <p class="text-[11px] text-[#555] font-medium mb-1 uppercase tracking-widest">Auto-generated prompt preview</p>
              <p class="text-[#888] text-xs leading-relaxed italic">{{ autoPreview }}</p>
            </div>

            <!-- Row last: Sort order + Active -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-[#888] mb-1.5">Sort Order</label>
                <input v-model.number="form.sort_order" type="number" min="0" placeholder="0"
                  class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
              </div>
              <div class="flex items-end pb-0.5">
                <label class="flex items-center gap-3 cursor-pointer select-none">
                  <div class="relative">
                    <input type="checkbox" v-model="form.is_active" class="sr-only" />
                    <div class="w-10 h-5 rounded-full transition-colors" :class="form.is_active ? 'bg-green-500' : 'bg-[#333]'"></div>
                    <div class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform" :class="form.is_active ? 'translate-x-5' : ''"></div>
                  </div>
                  <span class="text-sm text-[#888]">{{ form.is_active ? 'Active (visible to clients)' : 'Hidden' }}</span>
                </label>
              </div>
            </div>

            <!-- ── POSES SECTION (editing only) ──────────────────────── -->
            <template v-if="editingId">
              <div class="border-t border-[#1e1e1e] pt-5">
                <div class="flex items-center justify-between mb-3">
                  <div>
                    <h4 class="text-white text-sm font-semibold">Poses</h4>
                    <p class="text-[#555] text-[11px] mt-0.5">Upload or generate additional model poses. The character seed keeps them consistent.</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <button type="button" @click="$refs.poseFileInput.click()"
                      :disabled="uploadingPose"
                      class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-[#333] text-[#888] hover:border-[#555] hover:text-white transition-colors disabled:opacity-50">
                      <i class="fa-solid fa-upload text-[10px]"></i> Upload
                    </button>
                    <button type="button" @click="openPoseDialog"
                      class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-[#e8d5b7]/30 text-[#e8d5b7] hover:bg-[#e8d5b7]/10 transition-colors">
                      <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i> Generate
                    </button>
                  </div>
                </div>

                <!-- Hidden file input -->
                <input ref="poseFileInput" type="file" accept="image/*" class="hidden"
                  @change="uploadPose" />

                <p v-if="poseUploadError" class="text-red-400 text-xs mb-2">{{ poseUploadError }}</p>

                <!-- Loading -->
                <div v-if="posesLoading" class="flex items-center justify-center py-6">
                  <div class="w-5 h-5 rounded-full border-2 border-[#e8d5b7]/30 border-t-[#e8d5b7] animate-spin"></div>
                </div>

                <!-- Empty state -->
                <div v-else-if="!poses.length" class="flex flex-col items-center justify-center py-6 rounded-xl border border-dashed border-[#1e1e1e] text-center">
                  <i class="fa-solid fa-images text-2xl text-[#333] mb-2"></i>
                  <p class="text-[#555] text-xs">No poses yet. Upload or generate to add poses.</p>
                </div>

                <!-- Pose grid -->
                <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                  <div v-for="pose in poses" :key="pose.id"
                    class="relative group/pose rounded-xl overflow-hidden bg-[#0a0a0a] border border-[#1e1e1e] hover:border-[#333] transition-colors">
                    <div class="aspect-[3/4]">
                      <img :src="pose.file_path" :alt="pose.pose_label"
                        class="w-full h-full object-cover object-top" />
                    </div>
                    <!-- Label + prompt toggle overlay -->
                    <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/90 to-transparent px-2 pt-6 pb-2">
                      <p class="text-white text-[10px] font-medium truncate mb-1">{{ pose.pose_label }}</p>
                      <button v-if="pose.prompt_used" type="button"
                        @click.stop="expandedPromptId = expandedPromptId === pose.id ? null : pose.id"
                        class="flex items-center gap-1 text-[9px] text-[#e8d5b7]/70 hover:text-[#e8d5b7] transition-colors">
                        <i class="fa-solid fa-scroll text-[8px]"></i>
                        {{ expandedPromptId === pose.id ? 'hide prompt' : 'view prompt' }}
                      </button>
                    </div>
                    <!-- Action buttons (top-right) -->
                    <div class="absolute top-1.5 right-1.5 flex gap-1 opacity-0 group-hover/pose:opacity-100 transition-opacity">
                      <button v-if="pose.prompt_used" type="button" @click.stop="copyPrompt(pose.prompt_used)"
                        class="w-6 h-6 rounded-full bg-black/70 border border-[#e8d5b7]/30 text-[#e8d5b7] text-[10px] hover:bg-[#e8d5b7]/20 flex items-center justify-center"
                        :title="'Copy prompt'">
                        <i :class="copiedPromptId === pose.id ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
                      </button>
                      <button type="button" @click.stop="removePose(pose)"
                        class="w-6 h-6 rounded-full bg-black/70 border border-red-500/30 text-red-400 text-[10px] hover:bg-red-500/20 flex items-center justify-center">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Expanded prompt panel -->
                <div v-if="expandedPromptId && poses.find(p => p.id === expandedPromptId)?.prompt_used"
                  class="p-3 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e] space-y-2">
                  <div class="flex items-center justify-between">
                    <p class="text-[11px] text-[#555] font-medium uppercase tracking-widest">
                      Prompt — {{ poses.find(p => p.id === expandedPromptId)?.pose_label }}
                    </p>
                    <div class="flex items-center gap-2">
                      <button type="button"
                        @click="loadPromptIntoDialog(poses.find(p => p.id === expandedPromptId)?.prompt_used)"
                        class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] border border-[#e8d5b7]/30 text-[#e8d5b7] hover:bg-[#e8d5b7]/10 transition-colors">
                        <i class="fa-solid fa-wand-magic-sparkles text-[9px]"></i> Use in Generate
                      </button>
                      <button type="button"
                        @click="copyPrompt(poses.find(p => p.id === expandedPromptId)?.prompt_used)"
                        class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] border border-[#333] text-[#888] hover:text-white hover:border-[#555] transition-colors">
                        <i class="fa-solid fa-copy text-[9px]"></i> Copy
                      </button>
                      <button type="button" @click="expandedPromptId = null" class="text-[#555] hover:text-white">
                        <i class="fa-solid fa-xmark text-xs"></i>
                      </button>
                    </div>
                  </div>
                  <p class="text-[#888] text-[11px] leading-relaxed font-mono whitespace-pre-wrap break-all">
                    {{ poses.find(p => p.id === expandedPromptId)?.prompt_used }}
                  </p>
                </div>
              </div>
            </template>

            <!-- Error -->
            <p v-if="formError" class="text-red-400 text-sm text-center">{{ formError }}</p>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-2">
              <button type="button" @click="closeForm" class="px-4 py-2 rounded-lg text-sm text-[#888] hover:text-white border border-[#1e1e1e] hover:border-[#333] transition-colors">Cancel</button>
              <button type="submit" :disabled="saving"
                class="px-5 py-2 rounded-lg text-sm font-semibold bg-[#e8d5b7] text-[#0a0a0a] hover:bg-[#d4c19e] transition-colors disabled:opacity-60 flex items-center gap-2">
                <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
                {{ saving ? 'Saving…' : (editingId ? 'Save Changes' : 'Add Persona') }}
              </button>
            </div>

          </form>
        </div>
      </div>
    </Transition>

    <!-- ════════════════════════════════════════
         GENERATE AVATAR DIALOG (z-60 — above form modal)
    ════════════════════════════════════════ -->
    <Transition name="modal">
      <div v-if="showGenerateDialog" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80" @click="closeGenerateDialog"></div>
        <div class="relative bg-[#111] border border-[#1e1e1e] rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">

          <!-- Dialog header -->
          <div class="flex items-center justify-between px-6 py-5 border-b border-[#1e1e1e]">
            <div>
              <h3 class="text-white font-semibold flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-[#e8d5b7] text-sm"></i>
                Generate Avatar with AI
              </h3>
              <p class="text-[#555] text-xs mt-0.5">for <span class="text-[#888]">{{ form.name }}</span></p>
            </div>
            <button @click="closeGenerateDialog" class="text-[#555] hover:text-white transition-colors">
              <i class="fa-solid fa-xmark text-lg"></i>
            </button>
          </div>

          <div class="p-6 space-y-4">
            <!-- Mode selector -->
            <div class="grid grid-cols-3 gap-2">
              <button v-for="m in generateModes" :key="m.value" type="button"
                @click="genMode = m.value"
                class="p-3 rounded-xl border text-left transition-all"
                :class="genMode === m.value
                  ? 'border-[#e8d5b7]/50 bg-[#e8d5b7]/10 text-white'
                  : 'border-[#1e1e1e] text-[#666] hover:border-[#333] hover:text-[#888]'"
              >
                <i :class="m.icon" class="text-[#e8d5b7] mb-2 text-base block"></i>
                <p class="text-xs font-semibold">{{ m.label }}</p>
                <p class="text-[10px] mt-0.5 leading-relaxed opacity-70">{{ m.desc }}</p>
              </button>
            </div>

            <!-- Saved prompts (from existing poses) -->
            <div v-if="savedAvatarPrompts.length" class="p-3 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
              <p class="text-[11px] text-[#555] font-medium uppercase tracking-widest mb-2">
                <i class="fa-solid fa-bookmark mr-1"></i>Saved Prompts — click to load
              </p>
              <div class="space-y-1.5 max-h-40 overflow-y-auto">
                <button v-for="sp in savedAvatarPrompts" :key="sp.label" type="button"
                  @click="loadSavedAvatarPrompt(sp)"
                  class="w-full text-left p-2 rounded-lg border border-[#1e1e1e] hover:border-[#333] hover:bg-[#111] transition-all group/sp">
                  <p class="text-[#888] text-[10px] font-semibold mb-0.5 group-hover/sp:text-[#e8d5b7] transition-colors">
                    <i class="fa-solid fa-image mr-1 opacity-50"></i>{{ sp.label }}
                  </p>
                  <p class="text-[#555] text-[10px] leading-relaxed line-clamp-2 font-mono">{{ sp.prompt }}</p>
                </button>
              </div>
            </div>

            <!-- Auto mode: show the prompt preview -->
            <div v-if="genMode === 'auto'" class="p-3 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
              <p class="text-[11px] text-[#555] font-medium mb-1.5 uppercase tracking-widest">Prompt that will be sent</p>
              <p class="text-[#888] text-xs leading-relaxed italic">{{ autoAvatarPrompt }}</p>
            </div>

            <!-- Custom prompt mode -->
            <div v-if="genMode === 'custom_prompt'">
              <label class="block text-xs font-medium text-[#888] mb-1.5">Portrait prompt</label>
              <textarea v-model="genCustomPrompt" rows="5"
                placeholder="e.g. RAW photograph of a confident 26-year-old Nigerian female model, warm dark skin, slim athletic build, short natural afro, wearing a white studio background, soft diffused lighting, Canon EOS R5, 85mm f/1.4, hyperrealistic 8K UHD"
                class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444] resize-none font-mono text-xs leading-relaxed"></textarea>
              <p class="text-[#444] text-[11px] mt-1">Describe the portrait in detail. The AI will generate exactly what you write.</p>
            </div>

            <!-- JSON mode -->
            <div v-if="genMode === 'custom_json'">
              <label class="block text-xs font-medium text-[#888] mb-1.5">
                Replicate input JSON
                <span class="text-[#444] font-normal ml-1">— full <code class="text-[#666]">input</code> object</span>
              </label>
              <textarea v-model="genCustomJson" rows="8"
                placeholder='{\n  "prompt": "RAW photograph of a Nigerian female model…",\n  "aspect_ratio": "2:3",\n  "output_quality": 90,\n  "num_inference_steps": 4\n}'
                class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-xs text-[#a8e6a3] placeholder-[#333] focus:outline-none focus:border-[#444] resize-none font-mono leading-relaxed"></textarea>
              <p class="text-[#444] text-[11px] mt-1">This JSON is sent directly as the model input. Must be valid JSON. <code class="text-[#666]">prompt</code> is required.</p>
              <p v-if="jsonParseError" class="text-red-400 text-xs mt-1.5">{{ jsonParseError }}</p>
            </div>

            <!-- Generating progress -->
            <div v-if="generatingAvatar" class="p-4 rounded-xl border border-[#e8d5b7]/20 bg-[#e8d5b7]/5">
              <div class="flex items-center gap-3">
                <div class="w-5 h-5 flex-shrink-0 rounded-full border-2 border-[#e8d5b7]/30 border-t-[#e8d5b7] animate-spin"></div>
                <div>
                  <p class="text-xs text-[#e8d5b7] font-medium">Generating portrait…</p>
                  <p class="text-[11px] text-[#666] mt-0.5">Running AI model. Takes ~15–30 seconds.</p>
                </div>
              </div>
            </div>

            <p v-if="generateAvatarError" class="text-red-400 text-sm">{{ generateAvatarError }}</p>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-1">
              <button type="button" @click="closeGenerateDialog"
                class="px-4 py-2 rounded-lg text-sm text-[#888] hover:text-white border border-[#1e1e1e] hover:border-[#333] transition-colors">
                Cancel
              </button>
              <button type="button" @click="runGenerate" :disabled="generatingAvatar"
                class="px-5 py-2 rounded-lg text-sm font-semibold bg-[#e8d5b7] text-[#0a0a0a] hover:bg-[#d4c19e] transition-colors disabled:opacity-60 flex items-center gap-2">
                <i v-if="generatingAvatar" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-bolt"></i>
                {{ generatingAvatar ? 'Generating…' : 'Generate' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

  <!-- ════════════════════════════════════════
       GENERATE POSE DIALOG (z-70 — above all)
  ════════════════════════════════════════ -->
  <Transition name="modal">
    <div v-if="showPoseDialog" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80" @click="closePoseDialog"></div>
      <div class="relative bg-[#111] border border-[#1e1e1e] rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-5 border-b border-[#1e1e1e]">
          <div>
            <h3 class="text-white font-semibold flex items-center gap-2">
              <i class="fa-solid fa-person-walking text-[#e8d5b7] text-sm"></i>
              Generate Pose with AI
            </h3>
            <p class="text-[#555] text-xs mt-0.5">Character seed kept consistent for <span class="text-[#888]">{{ form.name }}</span></p>
          </div>
          <button @click="closePoseDialog" class="text-[#555] hover:text-white transition-colors">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
          <!-- Saved prompts from existing poses -->
          <div v-if="savedPosePrompts.length" class="p-3 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
            <p class="text-[11px] text-[#555] font-medium uppercase tracking-widest mb-2">
              <i class="fa-solid fa-bookmark mr-1"></i>Saved Prompts — click to load
            </p>
            <div class="space-y-1.5 max-h-36 overflow-y-auto">
              <button v-for="sp in savedPosePrompts" :key="sp.label" type="button"
                @click="loadSavedPosePrompt(sp)"
                class="w-full text-left p-2 rounded-lg border border-[#1e1e1e] hover:border-[#333] hover:bg-[#111] transition-all group/sp">
                <p class="text-[#888] text-[10px] font-semibold mb-0.5 group-hover/sp:text-[#e8d5b7] transition-colors">
                  <i class="fa-solid fa-person-walking mr-1 opacity-50"></i>{{ sp.label }}
                </p>
                <p class="text-[#555] text-[10px] leading-relaxed line-clamp-2 font-mono">{{ sp.prompt }}</p>
              </button>
            </div>
          </div>

          <!-- Pose Preset chips -->
          <div>
            <label class="block text-xs font-medium text-[#888] mb-2">Pose Preset</label>
            <div class="flex flex-wrap gap-2">
              <button v-for="preset in POSE_PRESETS" :key="preset.label" type="button"
                @click="selectPosePreset(preset)"
                class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                :class="genPoseLabel === preset.label
                  ? 'border-[#e8d5b7]/50 bg-[#e8d5b7]/10 text-[#e8d5b7]'
                  : 'border-[#1e1e1e] text-[#666] hover:border-[#333] hover:text-[#888]'">
                {{ preset.label }}
              </button>
            </div>
          </div>

          <!-- Label + Description row -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-[#888] mb-1.5">Pose Label <span class="text-red-400">*</span></label>
              <input v-model="genPoseLabel" type="text" placeholder="e.g. Walking Stride"
                class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
            </div>
            <div>
              <label class="block text-xs font-medium text-[#888] mb-1.5">Pose Description <span class="text-red-400">*</span></label>
              <input v-model="genPoseDesc" type="text" placeholder="e.g. walking, arms relaxed"
                class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-sm text-white placeholder-[#444] focus:outline-none focus:border-[#444]" />
            </div>
          </div>

          <!-- Scene Builder -->
          <div class="rounded-xl border border-[#1e1e1e] overflow-hidden">
            <button type="button" @click="showSceneBuilder = !showSceneBuilder"
              class="w-full flex items-center justify-between px-4 py-3 bg-[#0a0a0a] hover:bg-[#111] transition-colors">
              <span class="text-xs font-semibold text-[#888] flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-[#e8d5b7]"></i>
                Scene Builder
                <span class="text-[#555] font-normal">— background, props, shadow, style</span>
              </span>
              <i class="fa-solid text-[#555] text-xs" :class="showSceneBuilder ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>

            <div v-show="showSceneBuilder" class="p-4 space-y-4 border-t border-[#1e1e1e]">

              <!-- Background -->
              <div>
                <p class="text-[11px] text-[#555] font-medium uppercase tracking-widest mb-2">
                  <i class="fa-solid fa-panorama mr-1"></i>Background
                </p>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in BACKGROUND_OPTIONS" :key="opt.value" type="button"
                    @click="genBackground = opt.value"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="genBackground === opt.value
                      ? 'border-[#e8d5b7]/50 bg-[#e8d5b7]/10 text-[#e8d5b7]'
                      : 'border-[#1e1e1e] text-[#666] hover:border-[#333] hover:text-[#888]'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Beside / Props -->
              <div>
                <p class="text-[11px] text-[#555] font-medium uppercase tracking-widest mb-2">
                  <i class="fa-solid fa-couch mr-1"></i>Beside / Props <span class="text-[#444] normal-case font-normal">(multi-select)</span>
                </p>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in PROP_OPTIONS" :key="opt.value" type="button"
                    @click="toggleProp(opt.value)"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="genProps.includes(opt.value)
                      ? 'border-[#e8d5b7]/50 bg-[#e8d5b7]/10 text-[#e8d5b7]'
                      : 'border-[#1e1e1e] text-[#666] hover:border-[#333] hover:text-[#888]'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Under / Floor -->
              <div>
                <p class="text-[11px] text-[#555] font-medium uppercase tracking-widest mb-2">
                  <i class="fa-solid fa-arrow-down mr-1"></i>Under / Floor
                </p>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in FLOOR_OPTIONS" :key="opt.value" type="button"
                    @click="genFloor = opt.value"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="genFloor === opt.value
                      ? 'border-[#e8d5b7]/50 bg-[#e8d5b7]/10 text-[#e8d5b7]'
                      : 'border-[#1e1e1e] text-[#666] hover:border-[#333] hover:text-[#888]'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Shadow -->
              <div>
                <p class="text-[11px] text-[#555] font-medium uppercase tracking-widest mb-2">
                  <i class="fa-solid fa-circle-half-stroke mr-1"></i>Shadow
                </p>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in SHADOW_OPTIONS" :key="opt.value" type="button"
                    @click="genShadow = opt.value"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="genShadow === opt.value
                      ? 'border-[#e8d5b7]/50 bg-[#e8d5b7]/10 text-[#e8d5b7]'
                      : 'border-[#1e1e1e] text-[#666] hover:border-[#333] hover:text-[#888]'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Style / Filter -->
              <div>
                <p class="text-[11px] text-[#555] font-medium uppercase tracking-widest mb-2">
                  <i class="fa-solid fa-wand-sparkles mr-1"></i>Style / Filter
                </p>
                <div class="flex flex-wrap gap-1.5">
                  <button v-for="opt in FILTER_OPTIONS" :key="opt.value" type="button"
                    @click="genFilter = opt.value"
                    class="px-2.5 py-1 rounded-lg text-xs border transition-all"
                    :class="genFilter === opt.value
                      ? 'border-[#e8d5b7]/50 bg-[#e8d5b7]/10 text-[#e8d5b7]'
                      : 'border-[#1e1e1e] text-[#666] hover:border-[#333] hover:text-[#888]'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>

            </div>
          </div>

          <!-- Mode selector -->
          <div class="grid grid-cols-3 gap-2">
            <button v-for="m in generateModes" :key="m.value" type="button"
              @click="genPoseMode = m.value"
              class="p-2.5 rounded-xl border text-left transition-all"
              :class="genPoseMode === m.value
                ? 'border-[#e8d5b7]/50 bg-[#e8d5b7]/10 text-white'
                : 'border-[#1e1e1e] text-[#666] hover:border-[#333] hover:text-[#888]'">
              <i :class="m.icon" class="text-[#e8d5b7] mb-1 text-sm block"></i>
              <p class="text-[11px] font-semibold">{{ m.label }}</p>
            </button>
          </div>

          <!-- Auto mode: live prompt preview -->
          <div v-if="genPoseMode === 'auto'" class="p-3 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
            <div class="flex items-center justify-between mb-1.5">
              <p class="text-[11px] text-[#555] font-medium uppercase tracking-widest">Prompt Preview</p>
              <button type="button" @click="genPoseMode = 'custom_prompt'; genPoseCustomPrompt = autoPosePromptPreview"
                class="text-[10px] text-[#e8d5b7]/60 hover:text-[#e8d5b7] transition-colors">
                <i class="fa-solid fa-pen text-[9px] mr-1"></i>Edit manually
              </button>
            </div>
            <p class="text-[#888] text-[11px] leading-relaxed font-mono">{{ autoPosePromptPreview }}</p>
          </div>

          <!-- Custom prompt -->
          <div v-if="genPoseMode === 'custom_prompt'">
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs font-medium text-[#888]">Custom Prompt</label>
              <button type="button" @click="genPoseCustomPrompt = autoPosePromptPreview"
                class="text-[10px] text-[#e8d5b7]/60 hover:text-[#e8d5b7] transition-colors">
                <i class="fa-solid fa-rotate mr-1"></i>Reset from builder
              </button>
            </div>
            <textarea v-model="genPoseCustomPrompt" rows="5"
              placeholder="RAW photograph of …"
              class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-xs text-white placeholder-[#444] focus:outline-none focus:border-[#444] resize-none font-mono"></textarea>
          </div>

          <!-- JSON -->
          <div v-if="genPoseMode === 'custom_json'">
            <label class="block text-xs font-medium text-[#888] mb-1.5">JSON input</label>
            <textarea v-model="genPoseCustomJson" rows="6"
              placeholder='{"prompt": "…", "aspect_ratio": "2:3"}'
              class="w-full bg-[#0a0a0a] border border-[#1e1e1e] rounded-lg px-3 py-2.5 text-xs text-[#a8e6a3] placeholder-[#333] focus:outline-none focus:border-[#444] resize-none font-mono"></textarea>
            <p v-if="poseJsonError" class="text-red-400 text-xs mt-1">{{ poseJsonError }}</p>
          </div>

          <!-- Progress -->
          <div v-if="generatingPose" class="p-4 rounded-xl border border-[#e8d5b7]/20 bg-[#e8d5b7]/5">
            <div class="flex items-center gap-3">
              <div class="w-5 h-5 flex-shrink-0 rounded-full border-2 border-[#e8d5b7]/30 border-t-[#e8d5b7] animate-spin"></div>
              <div>
                <p class="text-xs text-[#e8d5b7] font-medium">Generating pose…</p>
                <p class="text-[11px] text-[#666] mt-0.5">Runs with your character seed for consistency.</p>
              </div>
            </div>
          </div>

          <p v-if="poseDialogError" class="text-red-400 text-sm">{{ poseDialogError }}</p>

          <div class="flex items-center justify-end gap-3 pt-1">
            <button type="button" @click="closePoseDialog"
              class="px-4 py-2 rounded-lg text-sm text-[#888] hover:text-white border border-[#1e1e1e] hover:border-[#333] transition-colors">
              Cancel
            </button>
            <button type="button" @click="runGeneratePose" :disabled="generatingPose"
              class="px-5 py-2 rounded-lg text-sm font-semibold bg-[#e8d5b7] text-[#0a0a0a] hover:bg-[#d4c19e] transition-colors disabled:opacity-60 flex items-center gap-2">
              <i v-if="generatingPose" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-bolt"></i>
              {{ generatingPose ? 'Generating…' : 'Generate Pose' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/api'

const personas    = ref([])
const loading     = ref(false)
const showForm    = ref(false)
const saving      = ref(false)
const formError   = ref('')
const editingId   = ref(null)
const search      = ref('')
const filterGender = ref('')
const generatingAvatar    = ref(false)
const generateAvatarError = ref('')

// ── Generate dialog state ────────────────────────────────────────────────
const showGenerateDialog = ref(false)
const genMode            = ref('auto')   // 'auto' | 'custom_prompt' | 'custom_json'
const genCustomPrompt    = ref('')
const genCustomJson      = ref('')
const jsonParseError     = ref('')

const generateModes = [
  { value: 'auto',          label: 'Auto',          icon: 'fa-solid fa-magic',        desc: 'Build prompt from persona fields' },
  { value: 'custom_prompt', label: 'Custom Prompt',  icon: 'fa-solid fa-align-left',   desc: 'Write your own portrait prompt' },
  { value: 'custom_json',   label: 'JSON Params',    icon: 'fa-solid fa-code',         desc: 'Full Replicate input as JSON' },
]

const genderOptions = [
  { value: 'female',     label: 'Female (Woman)' },
  { value: 'male',       label: 'Male (Man)' },
  { value: 'girl',       label: 'Girl (Teen/Child)' },
  { value: 'boy',        label: 'Boy (Teen/Child)' },
  { value: 'child',      label: 'Child (Unisex)' },
  { value: 'non_binary', label: 'Non-Binary / Androgynous' },
]

const emptyForm = () => ({
  name: '', gender: '', age: null, nationality: '', ethnicity: '',
  skin_tone: '', body_type: '', hair: '', best_for: '',
  description: '', avatar_url: '', is_active: true, sort_order: 0,
})

const form = ref(emptyForm())

// Auto-preview description for the PERSONA description field
const autoPreview = computed(() => {
  if (!form.value.name) return ''
  const g = genderOptions.find(x => x.value === form.value.gender)
  const parts = [
    `a professional ${g?.label?.toLowerCase() ?? form.value.gender} fashion model`,
    form.value.age ? `${form.value.age} years old` : null,
    form.value.nationality ? `${form.value.nationality} nationality` : null,
    form.value.ethnicity ? `${form.value.ethnicity} ethnicity` : null,
    form.value.skin_tone ? `${form.value.skin_tone} skin tone` : null,
    form.value.body_type ? `${form.value.body_type.replace('_', ' ')} build` : null,
    form.value.hair || null,
    'perfect posture, elegant and confident',
  ].filter(Boolean)
  return parts.join(', ')
})

// Auto-avatar prompt shown in the generate dialog preview
const autoAvatarPrompt = computed(() => {
  const f = form.value
  if (!f.name) return ''
  const g = genderOptions.find(x => x.value === f.gender)
  const subject = f.description || [
    `a professional ${g?.label?.toLowerCase() ?? f.gender} fashion model`,
    f.age ? `${f.age} years old` : null,
    f.nationality ? `${f.nationality} nationality` : null,
    f.ethnicity ? `${f.ethnicity} ethnicity` : null,
    f.skin_tone ? `${f.skin_tone} skin tone` : null,
    f.body_type ? `${f.body_type.replace('_', ' ')} build` : null,
    f.hair || null,
    'elegant posture, confident expression',
  ].filter(Boolean).join(', ')
  return [
    `RAW photograph, photorealistic portrait of ${subject}`,
    'plain white seamless studio background',
    'soft diffused key light, subtle fill light, rim light',
    'Canon EOS R5 Mark II, 85mm f/1.4 prime lens',
    'sharp focus on face, hyperrealistic, ultra-detailed, 8K UHD',
    'no text, no watermark',
  ].join(', ')
})

const filtered = computed(() => {
  return personas.value.filter(p => {
    const q = search.value.toLowerCase()
    const matchText = !q ||
      p.name.toLowerCase().includes(q) ||
      (p.nationality || '').toLowerCase().includes(q) ||
      (p.ethnicity || '').toLowerCase().includes(q) ||
      (p.best_for || '').toLowerCase().includes(q)
    const matchGender = !filterGender.value || p.gender === filterGender.value
    return matchText && matchGender
  })
})

function genderIcon(g) {
  return {
    female: 'fa-solid fa-person-dress',
    male: 'fa-solid fa-person',
    girl: 'fa-solid fa-person-dress',
    boy: 'fa-solid fa-person',
    child: 'fa-solid fa-child',
    non_binary: 'fa-solid fa-person',
  }[g] || 'fa-solid fa-person'
}

function genderLabel(g) {
  return { female: 'F', male: 'M', girl: 'Girl', boy: 'Boy', child: 'Child', non_binary: 'NB' }[g] || g
}

function genderBadge(g) {
  return {
    female: 'border-pink-500/30 text-pink-400 bg-pink-500/10',
    male: 'border-blue-500/30 text-blue-400 bg-blue-500/10',
    girl: 'border-pink-500/30 text-pink-300 bg-pink-500/10',
    boy: 'border-blue-500/30 text-blue-300 bg-blue-500/10',
    child: 'border-yellow-500/30 text-yellow-400 bg-yellow-500/10',
    non_binary: 'border-purple-500/30 text-purple-400 bg-purple-500/10',
  }[g] || 'border-[#333] text-[#888]'
}

function openGenerateDialog() {
  jsonParseError.value = ''
  generateAvatarError.value = ''

  // Pre-fill with the previously used avatar prompt when editing an existing persona.
  // The Portrait pose (sort_order 0) stores the last full prompt in prompt_used.
  const savedPrompt = editingId.value
    ? (poses.value.find(p => p.pose_label === 'Portrait' && p.prompt_used)
        ?? poses.value.find(p => p.prompt_used))?.prompt_used ?? ''
    : ''

  if (savedPrompt) {
    genMode.value = 'custom_prompt'
    genCustomPrompt.value = savedPrompt
  } else {
    genMode.value = 'auto'
    genCustomPrompt.value = ''
  }

  genCustomJson.value = JSON.stringify({
    prompt: savedPrompt || autoAvatarPrompt.value,
    aspect_ratio: '2:3',
    output_format: 'jpg',
    output_quality: 90,
    num_inference_steps: 4,
  }, null, 2)

  showGenerateDialog.value = true
}

function closeGenerateDialog() {
  if (generatingAvatar.value) return
  showGenerateDialog.value = false
}

async function runGenerate() {
  jsonParseError.value = ''
  generateAvatarError.value = ''

  if (genMode.value === 'custom_json') {
    try { JSON.parse(genCustomJson.value) }
    catch { jsonParseError.value = 'Invalid JSON — please fix before generating.'; return }
  }

  generatingAvatar.value = true
  try {
    const payload = {
      name:          form.value.name,
      gender:        form.value.gender,
      age:           form.value.age || null,
      nationality:   form.value.nationality || null,
      ethnicity:     form.value.ethnicity || null,
      skin_tone:     form.value.skin_tone || null,
      body_type:     form.value.body_type || null,
      hair:          form.value.hair || null,
      description:   form.value.description || null,
      prompt_mode:   genMode.value,
      custom_prompt: genMode.value === 'custom_prompt' ? genCustomPrompt.value : null,
      custom_json:   genMode.value === 'custom_json' ? genCustomJson.value : null,
    }
    if (editingId.value) payload.persona_id = editingId.value
    const res = await api.post('/admin/model-personas/generate-avatar', payload)
    form.value.avatar_url = res.data.avatar_url

    // Refresh poses so the saved Portrait prompt is available next time
    if (editingId.value) await fetchPoses()

    showGenerateDialog.value = false
  } catch (err) {
    generateAvatarError.value = err.response?.data?.message || 'AI generation failed. Check AI Provider config.'
  } finally {
    generatingAvatar.value = false
  }
}

async function fetchPersonas() {
  loading.value = true
  try {
    const res = await api.get('/admin/model-personas')
    personas.value = res.data
  } finally {
    loading.value = false
  }
}

function openForm(p = null) {
  formError.value = ''
  generateAvatarError.value = ''
  generatingAvatar.value = false
  if (p) {
    editingId.value = p.id
    form.value = { ...emptyForm(), ...p }
    poses.value = []
    fetchPoses()
  } else {
    editingId.value = null
    form.value = emptyForm()
    poses.value = []
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingId.value = null
  generatingAvatar.value = false
  generateAvatarError.value = ''
  showGenerateDialog.value = false
  showPoseDialog.value = false
  poses.value = []
}

async function save() {
  saving.value = true
  formError.value = ''
  try {
    if (editingId.value) {
      const res = await api.put(`/admin/model-personas/${editingId.value}`, form.value)
      const idx = personas.value.findIndex(p => p.id === editingId.value)
      if (idx >= 0) personas.value[idx] = res.data
    } else {
      const res = await api.post('/admin/model-personas', form.value)
      personas.value.push(res.data)
      personas.value.sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0) || a.name.localeCompare(b.name))
    }
    closeForm()
  } catch (err) {
    formError.value = err.response?.data?.message || Object.values(err.response?.data?.errors || {})?.[0]?.[0] || 'Save failed.'
  } finally {
    saving.value = false
  }
}

async function remove(p) {
  if (!confirm(`Delete "${p.name}"? This cannot be undone.`)) return
  await api.delete(`/admin/model-personas/${p.id}`)
  personas.value = personas.value.filter(x => x.id !== p.id)
}

// ── POSES ─────────────────────────────────────────────────────────────────

const poses            = ref([])
const posesLoading     = ref(false)
const uploadingPose    = ref(false)
const poseUploadError  = ref('')
const showPoseDialog   = ref(false)
const genPoseMode      = ref('auto')
const genPoseLabel     = ref('')
const genPoseDesc      = ref('')
const genPoseCustomPrompt = ref('')
const genPoseCustomJson   = ref('')
const generatingPose   = ref(false)
const poseDialogError  = ref('')
const poseJsonError    = ref('')
const expandedPromptId = ref(null)
const copiedPromptId   = ref(null)
const showSceneBuilder = ref(false)

// Scene builder state
const genBackground = ref('white_studio')
const genProps      = ref([])     // multi-select
const genFloor      = ref('auto')
const genShadow     = ref('soft_natural')
const genFilter     = ref('natural')

const BACKGROUND_OPTIONS = [
  { value: 'white_studio',    label: 'White Studio',  prompt: 'plain white seamless studio background' },
  { value: 'cream',           label: 'Cream',         prompt: 'warm cream seamless paper background' },
  { value: 'gradient_grey',   label: 'Grey Fade',     prompt: 'soft neutral grey gradient studio background' },
  { value: 'outdoor_garden',  label: 'Garden',        prompt: 'lush outdoor garden with soft natural bokeh' },
  { value: 'urban_street',    label: 'Urban Street',  prompt: 'urban street environment, soft blurred city bokeh' },
  { value: 'marble',          label: 'Marble',        prompt: 'white marble floor and wall studio background' },
  { value: 'wood_floor',      label: 'Wood Floor',    prompt: 'light oak wood floor with clean white wall' },
  { value: 'rooftop',         label: 'Rooftop',       prompt: 'rooftop terrace, city skyline softly blurred in background' },
  { value: 'showroom',        label: 'Showroom',      prompt: 'minimalist luxury fashion showroom background' },
]

const PROP_OPTIONS = [
  { value: 'chair',    label: 'Chair',          prompt: 'minimalist white chair beside the model' },
  { value: 'stool',    label: 'Stool',          prompt: 'wooden bar stool beside the model' },
  { value: 'rack',     label: 'Clothes Rack',   prompt: 'clothing rack with garments visible in background' },
  { value: 'plants',   label: 'Plants',         prompt: 'lush tropical potted plants on the side' },
  { value: 'column',   label: 'Column',         prompt: 'white plaster architectural column beside the model' },
  { value: 'mirror',   label: 'Mirror',         prompt: 'tall ornate floor mirror beside the model' },
  { value: 'steps',    label: 'Steps',          prompt: 'white marble steps under and around the model' },
  { value: 'bag',      label: 'Handbag',        prompt: 'luxury handbag held or placed beside the model' },
]

const FLOOR_OPTIONS = [
  { value: 'auto',      label: 'Auto',           prompt: null },
  { value: 'white',     label: 'White Floor',    prompt: 'standing on a plain white polished floor' },
  { value: 'carpet',    label: 'Carpet',         prompt: 'standing on a plush neutral carpet' },
  { value: 'marble',    label: 'Marble Floor',   prompt: 'standing on a white marble tile floor' },
  { value: 'wood',      label: 'Wood Floor',     prompt: 'standing on a warm oak hardwood floor' },
  { value: 'concrete',  label: 'Concrete',       prompt: 'standing on polished concrete floor' },
  { value: 'grass',     label: 'Grass',          prompt: 'standing on lush green grass' },
  { value: 'reflection',label: 'Reflection',     prompt: 'standing on a highly reflective glossy surface with mirror reflection' },
]

const SHADOW_OPTIONS = [
  { value: 'none',          label: 'None',           prompt: null },
  { value: 'soft_natural',  label: 'Soft Natural',   prompt: 'soft natural diffused shadow on the floor' },
  { value: 'side_shadow',   label: 'Side Shadow',    prompt: 'dramatic directional side shadow cast on the background wall' },
  { value: 'drop_shadow',   label: 'Drop Shadow',    prompt: 'clean sharp drop shadow below the feet' },
  { value: 'rim_glow',      label: 'Rim Glow',       prompt: 'warm backlit rim glow around the silhouette' },
  { value: 'dual_shadow',   label: 'Dual Shadow',    prompt: 'soft dual shadows from two opposing light sources' },
]

const FILTER_OPTIONS = [
  { value: 'natural',       label: 'Natural',         prompt: null },
  { value: 'editorial',     label: 'Editorial',       prompt: 'high-end editorial fashion photography style' },
  { value: 'warm',          label: 'Warm Tone',       prompt: 'warm golden hour tone, soft warm color grading' },
  { value: 'cool',          label: 'Cool Tone',       prompt: 'cool desaturated editorial color grade' },
  { value: 'high_contrast', label: 'High Contrast',   prompt: 'high contrast dramatic chiaroscuro lighting' },
  { value: 'matte_film',    label: 'Matte Film',      prompt: 'matte film photography look, subtle grain, lifted blacks' },
  { value: 'cinematic',     label: 'Cinematic',       prompt: 'cinematic anamorphic color grade, subtle lens flare' },
]

// Live prompt preview assembled from all scene options
const autoPosePromptPreview = computed(() => {
  const subject = form.value.description || autoPreview.value || 'a professional fashion model'
  const poseDesc = genPoseDesc.value || 'standing naturally, facing camera, full body shot'
  const bg    = BACKGROUND_OPTIONS.find(o => o.value === genBackground.value)?.prompt
  const floor = FLOOR_OPTIONS.find(o => o.value === genFloor.value)?.prompt
  const shadow = SHADOW_OPTIONS.find(o => o.value === genShadow.value)?.prompt
  const filter = FILTER_OPTIONS.find(o => o.value === genFilter.value)?.prompt
  const propParts = genProps.value
    .map(v => PROP_OPTIONS.find(o => o.value === v)?.prompt)
    .filter(Boolean)
  return [
    `RAW photograph, photorealistic full body shot of ${subject}`,
    poseDesc,
    bg,
    floor,
    ...propParts,
    shadow,
    'soft diffused key light, rim light',
    'Canon EOS R5 Mark II, 85mm f/1.4',
    filter,
    'hyperrealistic, ultra-detailed, 8K UHD',
    'no text, no watermark, no logo',
  ].filter(Boolean).join(', ')
})

function toggleProp(value) {
  const idx = genProps.value.indexOf(value)
  if (idx >= 0) genProps.value.splice(idx, 1)
  else genProps.value.push(value)
}

// Saved prompts derived from existing pose records
const savedAvatarPrompts = computed(() =>
  poses.value
    .filter(p => p.prompt_used)
    .map(p => ({ label: p.pose_label, prompt: p.prompt_used }))
)
const savedPosePrompts = computed(() => savedAvatarPrompts.value)

function copyPrompt(text) {
  navigator.clipboard?.writeText(text).catch(() => {})
  // Flash the icon briefly — find the pose id from the open panel or card
  const pose = poses.value.find(p => p.prompt_used === text)
  if (pose) {
    copiedPromptId.value = pose.id
    setTimeout(() => { copiedPromptId.value = null }, 1500)
  }
}

function loadPromptIntoDialog(prompt) {
  if (!prompt) return
  expandedPromptId.value = null
  openPoseDialog()
  genPoseMode.value         = 'custom_prompt'
  genPoseCustomPrompt.value = prompt
}

function loadSavedAvatarPrompt(sp) {
  genMode.value         = 'custom_prompt'
  genCustomPrompt.value = sp.prompt
}

function loadSavedPosePrompt(sp) {
  genPoseMode.value         = 'custom_prompt'
  genPoseCustomPrompt.value = sp.prompt
  // also try to parse label
  if (!genPoseLabel.value) genPoseLabel.value = sp.label
}

const POSE_PRESETS = [
  { label: 'Front Standing',      desc: 'standing straight, facing camera, arms relaxed at sides, full body shot' },
  { label: 'Side Profile',        desc: 'side profile view, standing tall, looking forward, full body shot' },
  { label: 'Three-Quarter Turn',  desc: 'three-quarter turn to camera, slight shoulder twist, full body shot' },
  { label: 'Walking Stride',      desc: 'walking confidently, mid-stride, arms slightly swinging, full body shot' },
  { label: 'Sitting',             desc: 'sitting on a stool, elegant posture, hands on lap, three-quarter view' },
  { label: 'Close-up Portrait',   desc: 'close-up portrait, face and shoulders only, direct eye contact, slight smile' },
  { label: 'Arms Up',             desc: 'both arms raised, hands near hair, slight tilt of head, full body shot' },
  { label: 'Casual Lean',         desc: 'casually leaning against a white wall, one foot crossed, relaxed arms' },
]

function selectPosePreset(preset) {
  genPoseLabel.value = preset.label
  genPoseDesc.value  = preset.desc
}

async function fetchPoses() {
  if (!editingId.value) return
  posesLoading.value = true
  try {
    const res = await api.get(`/admin/model-personas/${editingId.value}/poses`)
    poses.value = res.data
  } finally {
    posesLoading.value = false
  }
}

function openPoseDialog() {
  genPoseMode.value          = 'auto'
  genPoseLabel.value         = ''
  genPoseDesc.value          = ''
  genPoseCustomPrompt.value  = ''
  genPoseCustomJson.value    = ''
  poseDialogError.value      = ''
  poseJsonError.value        = ''
  expandedPromptId.value     = null
  // Reset scene builder to defaults
  genBackground.value = 'white_studio'
  genProps.value      = []
  genFloor.value      = 'auto'
  genShadow.value     = 'soft_natural'
  genFilter.value     = 'natural'
  showPoseDialog.value = true
}

function closePoseDialog() {
  if (generatingPose.value) return
  showPoseDialog.value = false
}

async function runGeneratePose() {
  poseDialogError.value = ''
  poseJsonError.value   = ''

  if (!genPoseLabel.value.trim()) { poseDialogError.value = 'Pose label is required.'; return }
  if (!genPoseDesc.value.trim() && genPoseMode.value === 'auto') { poseDialogError.value = 'Pose description is required.'; return }

  if (genPoseMode.value === 'custom_json') {
    try { JSON.parse(genPoseCustomJson.value) }
    catch { poseJsonError.value = 'Invalid JSON.'; return }
  }

  generatingPose.value = true
  try {
    // Auto mode: send the fully assembled prompt (including scene options) as custom_prompt
    // so the backend uses it directly instead of rebuilding with hardcoded background.
    const isAuto = genPoseMode.value === 'auto'
    const payload = {
      pose_label:    genPoseLabel.value,
      pose_desc:     genPoseDesc.value,
      prompt_mode:   isAuto ? 'custom_prompt' : genPoseMode.value,
      custom_prompt: isAuto ? autoPosePromptPreview.value : (genPoseMode.value === 'custom_prompt' ? genPoseCustomPrompt.value : null),
      custom_json:   genPoseMode.value === 'custom_json' ? genPoseCustomJson.value : null,
    }
    const res = await api.post(`/admin/model-personas/${editingId.value}/poses/generate`, payload)
    poses.value.push(res.data)
    showPoseDialog.value = false
  } catch (err) {
    poseDialogError.value = err.response?.data?.message || 'Pose generation failed.'
  } finally {
    generatingPose.value = false
  }
}

async function uploadPose(event) {
  const file = event.target.files?.[0]
  if (!file) return
  poseUploadError.value = ''

  const label = prompt('Pose label (e.g. Side Profile, Walking Stride):', file.name.replace(/\.[^.]+$/, ''))
  if (!label) { event.target.value = ''; return }

  uploadingPose.value = true
  try {
    const fd = new FormData()
    fd.append('image', file)
    fd.append('pose_label', label)
    const res = await api.post(`/admin/model-personas/${editingId.value}/poses`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    poses.value.push(res.data)
  } catch (err) {
    poseUploadError.value = err.response?.data?.message || 'Upload failed.'
  } finally {
    uploadingPose.value = false
    event.target.value = ''
  }
}

async function removePose(pose) {
  if (!confirm(`Delete pose "${pose.pose_label}"?`)) return
  await api.delete(`/admin/model-personas/${editingId.value}/poses/${pose.id}`)
  poses.value = poses.value.filter(x => x.id !== pose.id)
}

onMounted(fetchPersonas)
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
