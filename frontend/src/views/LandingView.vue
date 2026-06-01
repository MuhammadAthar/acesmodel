<template>
  <div class="min-h-screen bg-canvas text-white overflow-x-hidden">

    <!-- ── NAV ─────────────────────────────────────── -->
    <header class="fixed top-0 left-0 right-0 z-50 transition-all"
      :class="scrolled ? 'bg-canvas/95 backdrop-blur-xl border-b border-white/5' : 'bg-transparent'">
      <div class="max-w-screen-xl mx-auto px-8 h-16 flex items-center justify-between">
        <!-- Logo -->
        <a href="#" class="text-white font-bold text-base tracking-wide">Aces Model</a>

        <!-- Desktop nav -->
        <nav class="hidden md:flex items-center gap-8 text-sm text-white/70">
          <a href="#models"   class="hover:text-white transition-colors font-medium">AI Models</a>
          <a href="#how"      class="hover:text-white transition-colors">How we work</a>
          <a href="#pricing"  class="hover:text-white transition-colors">Pricing</a>
        </nav>

        <!-- CTA -->
        <div class="flex items-center gap-4">
          <template v-if="auth.isLoggedIn">
            <RouterLink :to="auth.isSuperAdmin ? '/admin/dashboard' : '/studio'" class="text-sm text-white font-semibold hover:text-accent transition-colors flex items-center gap-1.5">Go to Dashboard <i class="fa-solid fa-arrow-right text-xs"></i></RouterLink>
          </template>
          <template v-else>
            <RouterLink to="/login"    class="text-sm text-white/70 hover:text-white transition-colors">Sign in</RouterLink>
            <RouterLink to="/register" class="text-sm text-white font-semibold hover:text-accent transition-colors">Start free</RouterLink>
          </template>
        </div>
      </div>
    </header>

    <!-- ── HERO ─────────────────────────────────────── -->
    <section class="relative h-screen flex items-center justify-center overflow-hidden">
      <!-- Background image -->
      <img src="/images/hero-background.jpg" alt=""
        class="absolute inset-0 w-full h-full object-cover object-center" />
      <!-- Very subtle overlay to keep text legible without killing the image -->
      <div class="absolute inset-0 bg-black/20"></div>

      <!-- Centered text — exactly like Modelfy -->
      <div class="relative z-10 text-center px-6">
        <h1 class="font-bold text-white leading-none tracking-tight"
          style="font-size: clamp(5rem, 14vw, 14rem);">
          Aces Model
        </h1>
        <p class="text-white/80 text-sm font-medium tracking-[0.3em] uppercase mt-3">
          AI Fashion Content Operating System
        </p>
      </div>

      <!-- Bottom fade into next section -->
      <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-canvas to-transparent z-10"></div>
    </section>

    <!-- ── TICKER ─────────────────────────────────────── -->
    <div class="border-y border-border overflow-hidden py-4 bg-surface">
      <div class="ticker-track flex gap-16 whitespace-nowrap text-xs font-medium tracking-[0.2em] uppercase text-white">
        <span v-for="i in 4" :key="i" class="flex items-center gap-16">
          <span>AI Model Photos</span><i class="fa-solid fa-star-of-life text-accent text-[0.5rem]"></i>
          <span>Campaign Videos</span><i class="fa-solid fa-star-of-life text-accent text-[0.5rem]"></i>
          <span>Ad Creatives</span><i class="fa-solid fa-star-of-life text-accent text-[0.5rem]"></i>
          <span>Social Content</span><i class="fa-solid fa-star-of-life text-accent text-[0.5rem]"></i>
          <span>Lookbooks</span><i class="fa-solid fa-star-of-life text-accent text-[0.5rem]"></i>
          <span>SEO Copy</span><i class="fa-solid fa-star-of-life text-accent text-[0.5rem]"></i>
          <span>50+ Assets</span><i class="fa-solid fa-star-of-life text-accent text-[0.5rem]"></i>
          <span>Brand DNA</span><i class="fa-solid fa-star-of-life text-accent text-[0.5rem]"></i>
        </span>
      </div>
    </div>

    <!-- ── AI MODEL SHOWCASE ───────────────────────────── -->
    <section id="models" class="py-24 px-6" style="background:#f7f4ef;">
      <div class="max-w-screen-xl mx-auto">
        <div class="text-center mb-12">
          <p class="text-xs tracking-widest uppercase mb-3 font-medium" style="color:#b08d57;">AI Model Marketplace</p>
          <h2 class="font-display text-4xl sm:text-5xl font-light" style="color:#1a1a1a;">
            Models designed to work<br /><span style="color:#1a1a1a;">with your brand</span>
          </h2>
          <p class="mt-4 text-sm" style="color:#6b6b6b; max-width:480px; margin-left:auto; margin-right:auto;">
            Diverse, photorealistic AI talent — styled to match your aesthetic, ready in seconds.
          </p>
        </div>

        <!-- 5-col model grid -->
        <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-3">
          <div v-for="model in showcaseModels" :key="model.name"
            class="group cursor-pointer"
            @click="model.id ? router.push('/models/' + model.id) : null">
            <div class="aspect-[3/4] rounded-2xl overflow-hidden relative bg-[#d4cdc2]">
              <!-- Main image (fades out on hover if hover image exists) -->
              <img
                :src="model.photo"
                :alt="model.name"
                class="absolute inset-0 w-full h-full object-cover object-top transition-opacity duration-500"
                :class="model.hoverPhoto ? 'group-hover:opacity-0' : 'group-hover:scale-105 transition-transform duration-500'"
                loading="lazy"
              />
              <!-- Hover image (fades in on hover) -->
              <img
                v-if="model.hoverPhoto"
                :src="model.hoverPhoto"
                :alt="model.name + ' – alternate pose'"
                class="absolute inset-0 w-full h-full object-cover object-top opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                loading="lazy"
              />
              <!-- AI badge -->
              <div class="absolute top-2 left-2 px-1.5 py-0.5 rounded-full text-[9px] font-semibold tracking-wide"
                style="background:rgba(176,141,87,0.88); color:#fff;">
                AI MODEL
              </div>
              <!-- Hover overlay -->
              <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3"
                style="background: linear-gradient(to top, rgba(0,0,0,0.65) 0%, transparent 55%);">
                <div>
                  <p class="text-xs font-semibold text-white leading-tight">{{ model.name }}</p>
                  <p class="text-[10px] text-white/70 mt-0.5">{{ model.tags }}</p>
                </div>
              </div>
            </div>
            <p class="text-xs mt-2 truncate text-center tracking-widest uppercase font-medium" style="color:#6b6b6b;">{{ model.name }}</p>
          </div>
        </div>

        <!-- Video showcase strip -->
        <div class="mt-12">
          <p class="text-xs tracking-widest uppercase mb-5 font-medium text-center" style="color:#b08d57;">
            <i class="fa-solid fa-film mr-1.5"></i>Sample Generated Videos
          </p>
          <div class="flex gap-3 overflow-x-auto pb-3 snap-x snap-mandatory scrollbar-none" style="-ms-overflow-style:none; scrollbar-width:none;">
            <div v-for="vid in showcaseVideos" :key="vid.label"
              class="group flex-shrink-0 snap-start cursor-pointer"
              style="width:175px;">
              <div class="rounded-2xl overflow-hidden relative" style="aspect-ratio:9/16;">
                <img :src="vid.photo" :alt="vid.label" class="w-full h-full object-cover object-top transition-transform duration-500 group-hover:scale-105" loading="lazy" />
                <div class="absolute inset-0 flex items-center justify-center" style="background:rgba(0,0,0,0.22);">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center transition-transform group-hover:scale-110"
                    style="background:rgba(255,255,255,0.88);">
                    <i class="fa-solid fa-play text-[#1a1a1a] text-sm ml-0.5"></i>
                  </div>
                </div>
                <div class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded text-[9px] font-medium" style="background:rgba(0,0,0,0.65); color:#fff;">{{ vid.duration }}</div>
                <div class="absolute top-2 left-2 px-1.5 py-0.5 rounded-full text-[9px] font-medium" style="background:rgba(176,141,87,0.9); color:#fff;">{{ vid.label }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-10">
          <a href="/register"
            class="inline-flex items-center gap-2 px-7 py-3 rounded-full text-sm font-medium transition-all"
            style="background:#1a1a1a; color:#f7f4ef; letter-spacing:0.04em;">
            Browse All Models &rarr;
          </a>
        </div>
      </div>
    </section>

    <!-- ── DIVIDER ────────────────────────────────────── -->
    <div style="background:#f7f4ef; padding: 0 0 0 0;">
      <div style="position:relative; overflow:hidden; height:90px; background:#f7f4ef;">
        <!-- Thin center line -->
        <div style="position:absolute; left:50%; top:0; bottom:0; width:1px; background:linear-gradient(to bottom, transparent, #b08d57 40%, #b08d57 60%, transparent); transform:translateX(-50%);"></div>
        <!-- Diamond -->
        <div style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%) rotate(45deg); width:14px; height:14px; background:#b08d57;"></div>
        <!-- Left line -->
        <div style="position:absolute; left:0; right:calc(50% + 40px); top:50%; height:1px; background:linear-gradient(to right, transparent, #c9a96e);"></div>
        <!-- Right line -->
        <div style="position:absolute; left:calc(50% + 40px); right:0; top:50%; height:1px; background:linear-gradient(to left, transparent, #c9a96e);"></div>
        <!-- Small dots either side of diamond -->
        <div style="position:absolute; left:50%; top:50%; transform:translate(calc(-50% - 28px), -50%); width:5px; height:5px; border-radius:50%; background:#c9a96e; opacity:0.7;"></div>
        <div style="position:absolute; left:50%; top:50%; transform:translate(calc(-50% + 23px), -50%); width:5px; height:5px; border-radius:50%; background:#c9a96e; opacity:0.7;"></div>
      </div>
    </div>

    <!-- ── HOW IT WORKS ───────────────────────────────── -->
    <section id="how" class="py-24" style="background:#f7f4ef;">
      <div class="max-w-screen-xl mx-auto px-6">
        <div class="text-center mb-16">
          <p class="text-xs tracking-widest uppercase mb-3 font-medium" style="color:#b08d57;">Simple process</p>
          <h2 class="font-display text-4xl sm:text-5xl font-light" style="color:#1a1a1a;">
            From flat lay to full campaign<br /><span style="color:#1a1a1a;">in 3 steps</span>
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div v-for="step in steps" :key="step.n"
            class="rounded-3xl p-8 relative overflow-hidden group transition-colors"
            style="background:#fff; border:1px solid #e8e2d9;">
            <div class="absolute -top-4 -right-4 font-display text-9xl font-light select-none transition-colors"
              style="color:rgba(26,26,26,0.04);">
              {{ step.n }}
            </div>
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl mb-5"
              style="background:rgba(176,141,87,0.12); border:1px solid rgba(176,141,87,0.3);">
              <i :class="step.icon" style="color:#b08d57;"></i>
            </div>
            <h3 class="text-lg font-medium mb-3" style="color:#1a1a1a;">{{ step.title }}</h3>
            <p class="text-sm leading-relaxed" style="color:#6b6b6b;">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── FEATURES ───────────────────────────────────── -->
    <section id="features" class="py-24 border-t border-border">
      <div class="max-w-screen-xl mx-auto px-6">
        <div class="text-center mb-16">
          <p class="text-xs text-accent tracking-widest uppercase mb-3">Everything included</p>
          <h2 class="font-display text-4xl sm:text-5xl font-light text-white">
            One garment. <span class="text-transparent bg-clip-text bg-gradient-accent">50+ assets.</span>
          </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="feat in features" :key="feat.title"
            class="glass rounded-2xl p-6 border border-border hover:border-accent/20 transition-colors group">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-accent-glow border border-accent/20 mb-4">
              <i :class="feat.icon" class="text-accent text-lg"></i>
            </div>
            <h3 class="font-medium text-white mb-2">{{ feat.title }}</h3>
            <p class="text-muted text-sm leading-relaxed">{{ feat.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── FULL-WIDTH EDITORIAL BANNER ───────────────── -->
    <section class="relative h-[70vh] flex items-center justify-center overflow-hidden border-y border-border my-12">
      <div class="absolute inset-0 bg-gradient-to-br from-[#0f0f0f] via-[#151515] to-[#0f0f0f]"></div>
      <div class="absolute inset-0 bg-gradient-radial opacity-40"></div>
      <!-- Large decorative text — scrolling right to left -->
      <div class="absolute inset-0 flex items-center pointer-events-none select-none overflow-hidden">
        <span class="decorative-scroll font-display text-[20vw] font-bold text-white/[0.07] whitespace-nowrap">UNLIMITED CREATIVITY &nbsp;&nbsp;&nbsp; UNLIMITED CREATIVITY &nbsp;&nbsp;&nbsp; UNLIMITED CREATIVITY</span>
      </div>
      <div class="relative z-10 text-center px-6">
        <h2 class="font-display text-5xl sm:text-7xl font-light text-white mb-6">
          Your brand deserves<br />the best.
        </h2>
        <p class="text-muted text-lg max-w-lg mx-auto mb-8">The only AI fashion studio built exclusively for clothing brands. No generic AI — purpose-built for fashion.</p>
        <RouterLink :to="auth.isLoggedIn ? '/studio' : '/register'" class="btn-accent py-4 px-8 text-base flex items-center gap-2 inline-flex">
          {{ auth.isLoggedIn ? 'Open Studio' : 'Start creating free' }} <i class="fa-solid fa-arrow-right text-sm"></i>
        </RouterLink>
      </div>
    </section>

    <!-- ── STATS ────────────────────────────────────── -->
    <section class="py-20 border-b border-border">
      <div class="max-w-screen-xl mx-auto px-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
          <div v-for="stat in statsData" :key="stat.label">
            <p class="font-display text-5xl font-light text-white mb-2">{{ stat.value }}</p>
            <p class="text-muted text-sm">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── PRICING ────────────────────────────────────── -->
    <section id="pricing" class="py-24 border-b border-border">
      <div class="max-w-screen-xl mx-auto px-6">
        <div class="text-center mb-16">
          <p class="text-xs text-accent tracking-widest uppercase mb-3">Simple pricing</p>
          <h2 class="font-display text-4xl sm:text-5xl font-light text-white">
            Plans for every brand
          </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="plan in pricingPlans" :key="plan.name"
            class="glass rounded-3xl p-6 border flex flex-col"
            :class="plan.popular ? 'border-accent/60 relative' : 'border-border'">
            <div v-if="plan.popular"
              class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-medium bg-accent text-canvas">
              Most Popular
            </div>
            <p class="text-xs text-muted uppercase tracking-widest mb-2">{{ plan.name }}</p>
            <div class="mb-5">
              <span class="text-4xl font-light text-white">${{ plan.price }}</span>
              <span class="text-muted text-sm">/mo</span>
            </div>
            <p class="text-accent text-sm mb-4">{{ plan.credits }} AI credits/mo</p>
            <ul class="space-y-2.5 flex-1 mb-6">
              <li v-for="f in plan.features" :key="f" class="flex items-start gap-2 text-sm text-soft">
                <i class="fa-solid fa-check text-accent flex-shrink-0 mt-0.5"></i>
                {{ f }}
              </li>
            </ul>
            <RouterLink :to="auth.isLoggedIn ? '/billing' : '/register'"
              class="w-full text-center py-3 rounded-xl text-sm font-medium transition-all"
              :class="plan.popular
                ? 'bg-accent text-canvas hover:bg-accent-dim'
                : 'border border-border text-muted hover:text-white hover:border-accent/30'">
              {{ auth.isLoggedIn ? 'Upgrade' : 'Get started' }}
            </RouterLink>
          </div>
        </div>
      </div>
    </section>

    <!-- ── FEATURED ON ───────────────────────────────── -->
    <section class="py-12 border-y border-border bg-[#0a0a0a]">
      <div class="max-w-screen-xl mx-auto px-6 mb-6">
        <p class="text-xs font-semibold tracking-[0.25em] uppercase text-[#555] text-center">Product Featured On</p>
      </div>
      <div class="featured-track-wrapper overflow-hidden">
        <div class="featured-track flex items-center gap-8">
          <template v-for="n in 2" :key="n">
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/findly.svg"        alt="Findly"        class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/newtool.svg"      alt="NewTool"       class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/dang.webp"         alt="DANG"          class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/submitai.webp"    alt="SubmitAI"      class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/aihuntlist.svg"   alt="Aihuntlist"    class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/getstools.svg"    alt="Gets.Tools"    class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/superlaunch.svg"  alt="Super Launch"  class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/launchigniter.svg" alt="LaunchIgniter" class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/twelvetools.svg"  alt="Twelve.tools"  class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/goodaitools.webp"  alt="Good AI Tools" class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/listing.svg"       alt="Listing"       class="h-12 w-auto object-contain max-w-[130px]" /></div>
            <div class="h-20 w-36 flex items-center justify-center flex-shrink-0"><img src="/images/producthunt.svg"  alt="Product Hunt"  class="h-12 w-auto object-contain max-w-[130px]" /></div>
          </template>
        </div>
      </div>
    </section>

    <!-- ── FAQ ───────────────────────────────────────── -->
    <section class="py-24 border-b border-border">
      <div class="max-w-2xl mx-auto px-6">
        <div class="text-center mb-14">
          <h2 class="font-display text-4xl font-light text-white">Frequently Asked Questions</h2>
        </div>
        <div class="space-y-2">
          <div v-for="faq in faqs" :key="faq.q"
            class="glass rounded-2xl border border-border overflow-hidden">
            <button @click="toggleFaq(faq)"
              class="w-full flex items-center justify-between gap-4 p-5 text-left hover:bg-white/[0.02] transition-colors">
              <span class="font-medium text-white text-sm">{{ faq.q }}</span>
              <span class="text-accent flex-shrink-0 transition-transform" :class="faq.open ? 'rotate-45' : ''">
                <i class="fa-solid fa-plus"></i>
              </span>
            </button>
            <Transition name="faq-slide">
              <div v-if="faq.open" class="px-5 pb-5">
                <p class="text-muted text-sm leading-relaxed border-t border-border pt-4">{{ faq.a }}</p>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </section>

    <!-- ── FINAL CTA ─────────────────────────────────── -->
    <section class="relative overflow-hidden text-center">
      <!-- Background image — full natural height -->
      <img src="/images/footer image.jpeg" alt="" class="w-full h-auto block" />
      <!-- Dark overlay -->
      <div class="absolute inset-0 bg-black/55"></div>

      <!-- Content centered over image -->
      <div class="absolute inset-0 flex flex-col items-center justify-center px-6">
        <p class="text-xs text-accent tracking-widest uppercase mb-4">Get started today</p>
        <h2 class="font-display text-5xl sm:text-7xl font-light text-white mb-6">
          Let's get started.
        </h2>
        <p class="text-white/60 text-lg mb-10">10 free generations. No credit card. Cancel anytime.</p>
        <RouterLink :to="auth.isLoggedIn ? '/studio' : '/register'" class="btn-accent py-4 px-10 text-lg flex items-center gap-2 inline-flex">
          {{ auth.isLoggedIn ? 'Open your Studio' : 'Create free account' }} <i class="fa-solid fa-arrow-right"></i>
        </RouterLink>
      </div>
    </section>

    <!-- ── FOOTER ─────────────────────────────────────── -->
    <footer class="bg-[#0a0a0a] border-t border-[#1a1a1a]">

      <!-- Main footer grid -->
      <div class="max-w-screen-xl mx-auto px-6 py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10">

          <!-- Brand col -->
          <div class="md:col-span-1">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-9 h-9 rounded-full bg-gradient-accent flex items-center justify-center text-canvas font-bold text-sm">A</div>
              <span class="font-display text-lg tracking-widest text-white">Aces Model</span>
            </div>
            <p class="text-[#666] text-sm leading-relaxed max-w-xs">
              Aces Model is the premier AI destination for fashion content. Powering brands with next-gen studio imagery.
            </p>
          </div>

          <!-- Platform col -->
          <div>
            <p class="text-xs font-semibold text-white uppercase tracking-[0.18em] mb-5">Platform</p>
            <ul class="space-y-3 text-sm text-[#888]">
              <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
              <li><a href="#pricing"  class="hover:text-white transition-colors">Pricing</a></li>
              <li><a href="#models"   class="hover:text-white transition-colors">AI Models</a></li>
              <li><a href="#how"      class="hover:text-white transition-colors">How it works</a></li>
              <li><RouterLink to="/studio"   class="hover:text-white transition-colors">Studio</RouterLink></li>
              <li><RouterLink to="/register" class="hover:text-white transition-colors">Get Started</RouterLink></li>
            </ul>
          </div>

          <!-- Legal col -->
          <div>
            <p class="text-xs font-semibold text-white uppercase tracking-[0.18em] mb-5">Legal</p>
            <ul class="space-y-3 text-sm text-[#888]">
              <li><a href="#" class="hover:text-white transition-colors">Data Privacy</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Refund Policy</a></li>
            </ul>
          </div>

          <!-- Connect col -->
          <div>
            <p class="text-xs font-semibold text-white uppercase tracking-[0.18em] mb-5">Connect</p>
            <div class="flex items-center gap-3">
              <a href="#" class="w-9 h-9 rounded-full border border-[#222] flex items-center justify-center text-[#666] hover:text-white hover:border-[#444] transition-colors">
                <i class="fa-brands fa-instagram text-sm"></i>
              </a>
              <a href="#" class="w-9 h-9 rounded-full border border-[#222] flex items-center justify-center text-[#666] hover:text-white hover:border-[#444] transition-colors">
                <i class="fa-brands fa-tiktok text-sm"></i>
              </a>
              <a href="#" class="w-9 h-9 rounded-full border border-[#222] flex items-center justify-center text-[#666] hover:text-white hover:border-[#444] transition-colors">
                <i class="fa-brands fa-linkedin-in text-sm"></i>
              </a>
              <a href="#" class="w-9 h-9 rounded-full border border-[#222] flex items-center justify-center text-[#666] hover:text-white hover:border-[#444] transition-colors">
                <i class="fa-brands fa-x-twitter text-sm"></i>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="border-t border-[#1a1a1a]">
        <div class="max-w-screen-xl mx-auto px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
          <p class="text-xs text-[#555]">© {{ new Date().getFullYear() }} Aces Model. All Rights Reserved.</p>
          <div class="flex items-center gap-4">
            <!-- Payment badges -->
            <div class="flex items-center gap-2 text-[#555]">
              <i class="fa-brands fa-cc-visa text-xl"></i>
              <i class="fa-brands fa-cc-mastercard text-xl"></i>
              <i class="fa-brands fa-cc-stripe text-xl"></i>
            </div>
            <span class="text-[#444] text-xs flex items-center gap-1.5">
              <i class="fa-solid fa-shield-halved text-[#555]"></i> SSL Encrypted
            </span>
          </div>
        </div>
      </div>

    </footer>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const auth    = useAuthStore()
const router  = useRouter()
const scrolled = ref(false)

function onScroll() { scrolled.value = window.scrollY > 20 }
onMounted(() => {
  window.addEventListener('scroll', onScroll)
  fetchLandingPersonas()
})
onUnmounted(() => window.removeEventListener('scroll', onScroll))

// ── Dynamic landing-page personas (from DB) ───────────────────────────
const dbPersonas = ref([])
async function fetchLandingPersonas() {
  try {
    const res = await axios.get('/api/public/model-personas')
    dbPersonas.value = res.data
  } catch { /* silent — falls back to static */ }
}

// ── DATA ──────────────────────────────────────────────────────────────
const staticModels = [
  { name: 'Aisha Malik',    photo: 'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?w=300&h=400&fit=crop&crop=top', tags: 'South Asian · 25–35 · Studio' },
  { name: 'Layla Hassan',   photo: 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=300&h=400&fit=crop&crop=top', tags: 'Arab · 20–30 · Editorial' },
  { name: 'Bilal Qureshi',  photo: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=300&h=400&fit=crop&crop=top', tags: 'Pakistani · 25–35 · Casual' },
  { name: 'Sofia Khan',     photo: 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=300&h=400&fit=crop&crop=top', tags: 'Mixed · 18–25 · Lifestyle' },
  { name: 'Omar Farooq',    photo: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=300&h=400&fit=crop&crop=top', tags: 'Turkish · 30–40 · Luxury' },
  { name: 'Zara Ahmed',     photo: 'https://images.unsplash.com/photo-1554568218-0f1715e72254?w=300&h=400&fit=crop&crop=top', tags: 'South Asian · 22–30 · Reels' },
  { name: 'Yusuf Ali',      photo: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=300&h=400&fit=crop&crop=top', tags: 'African · 25–35 · Editorial' },
  { name: 'Nadia Rashid',   photo: 'https://images.unsplash.com/photo-1581044777550-4cfa60707c03?w=300&h=400&fit=crop&crop=top', tags: 'European · 28–38 · Studio' },
  { name: 'Fatima Noor',    photo: 'https://images.unsplash.com/photo-1551803091-e20673f15770?w=300&h=400&fit=crop&crop=top', tags: 'Arab · 20–28 · Summer' },
  { name: 'Adam Siddiqui',  photo: 'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?w=300&h=400&fit=crop&crop=top', tags: 'Pakistani · 22–32 · Streetwear' },
  { name: 'Dina Ibrahim',   photo: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=300&h=400&fit=crop&crop=top', tags: 'Turkish · 25–35 · Luxury' },
  { name: 'Raza Hussain',   photo: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=300&h=400&fit=crop&crop=top', tags: 'South Asian · 30–40 · Eid' },
  { name: 'Sara Mirza',     photo: 'https://images.unsplash.com/photo-1548142813-c348350df52b?w=300&h=400&fit=crop&crop=top', tags: 'Mixed · 18–25 · Minimal' },
  { name: 'Kai Johnson',    photo: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=400&fit=crop&crop=top', tags: 'East Asian · 22–30 · Lifestyle' },
  { name: 'Maya Patel',     photo: 'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?w=300&h=400&fit=crop&crop=top', tags: 'South Asian · 25–35 · Editorial' },
]

// Use DB personas when they exist (with avatar), pad/fallback with static
const showcaseModels = computed(() => {
  if (dbPersonas.value.length) {
    const db = dbPersonas.value.map(p => ({
      id:         p.id,
      name:       p.name,
      photo:      p.avatar_url,
      hoverPhoto: p.poses?.[1]?.file_path || p.poses?.[0]?.file_path || null,
      tags:       [p.ethnicity, p.age ? p.age + ' yrs' : null, p.best_for].filter(Boolean).join(' · ') || (p.nationality || ''),
    }))
    // Pad with static fallbacks if fewer than 10 DB personas
    const needed = Math.max(0, 10 - db.length)
    return [...db, ...staticModels.slice(0, needed)]
  }
  return staticModels
})

const showcaseVideos = [
  { label: 'Studio Reel',  duration: '0:15', photo: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=200&h=356&fit=crop&crop=top' },
  { label: 'Lifestyle',    duration: '0:22', photo: 'https://images.unsplash.com/photo-1483985988355-763728e1cec3?w=200&h=356&fit=crop&crop=top' },
  { label: 'Editorial',    duration: '0:18', photo: 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=200&h=356&fit=crop&crop=top' },
  { label: 'TikTok Reel',  duration: '0:30', photo: 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=200&h=356&fit=crop&crop=top' },
  { label: 'Reels Story',  duration: '0:12', photo: 'https://images.unsplash.com/photo-1567401893414-76b7b1e5a7a5?w=200&h=356&fit=crop&crop=top' },
]

const steps = [
  { n: '01', icon: 'fa-solid fa-camera',   title: 'Upload your garment',     desc: 'Drop a product photo — flat lay, hanger, or mannequin. Our AI scans fabric, color, style, and category automatically.' },
  { n: '02', icon: 'fa-solid fa-sliders',  title: 'Configure your campaign',  desc: 'Choose output types (photos, video, ads, copy), pick AI models from our marketplace, set the theme and platform targets.' },
  { n: '03', icon: 'fa-solid fa-download', title: 'Download your campaign',   desc: 'Receive 50+ production-ready assets in minutes. Model photos, social posts, ad creatives, lookbooks, and more.' },
]

const features = [
  { icon: 'fa-solid fa-user',       title: 'AI Model Photos',   desc: 'Studio, lifestyle, editorial and reels-format photos with diverse AI models wearing your garment.' },
  { icon: 'fa-solid fa-film',       title: 'Campaign Videos',   desc: 'Short video clips and reels optimized for Instagram, TikTok, and marketplace listings.' },
  { icon: 'fa-solid fa-bullhorn',   title: 'Ad Creatives',      desc: 'Platform-optimized ad images for Meta, Google, Pinterest with multiple size variants.' },
  { icon: 'fa-solid fa-pen-nib',    title: 'Copy & Captions',   desc: 'Product descriptions, Instagram captions, hashtag sets, and email copy — all AI-written in your brand voice.' },
  { icon: 'fa-solid fa-book-open',  title: 'Digital Lookbooks', desc: 'Publish shareable lookbook pages with your seasonal collection — zero design work.' },
  { icon: 'fa-solid fa-dna',        title: 'Brand DNA Analysis',desc: 'Upload your logo and past content. Aces Model learns your aesthetic and applies it to every generation.' },
]

const statsData = [
  { value: '50+',  label: 'Assets per garment' },
  { value: '30s',  label: 'Average generation time' },
  { value: '9',    label: 'Content formats' },
  { value: '100%', label: 'Fashion-focused AI' },
]

const pricingPlans = [
  {
    name: 'Free', price: 0, credits: 10, popular: false,
    features: ['10 AI generations', '1 campaign', 'Model photos only', 'Community support'],
  },
  {
    name: 'Starter', price: 29, credits: 200, popular: false,
    features: ['200 AI generations/mo', '5 campaigns', 'All content types', 'Email support'],
  },
  {
    name: 'Growth', price: 79, credits: 600, popular: true,
    features: ['600 AI generations/mo', 'Unlimited campaigns', 'Custom AI models', 'Brand DNA', 'Priority support'],
  },
  {
    name: 'Agency', price: 199, credits: 2000, popular: false,
    features: ['2,000 generations/mo', 'Multiple brands', 'Lookbook publishing', 'Analytics', 'Dedicated support'],
  },
]

const faqs = ref([
  { q: 'What kind of garment photos can I upload?',         a: 'Flat lays, hanger shots, mannequin photos, or even poorly-lit product images. Our AI cleans and interprets all of them.' , open: false },
  { q: 'How realistic are the AI model photos?',            a: 'We use Flux Kontext Pro (state-of-the-art) to generate photorealistic models wearing your exact garment. The output is indistinguishable from a real shoot.', open: false },
  { q: 'Can I use these images commercially?',              a: 'Yes. All generated assets are fully licensed for commercial use — websites, ads, social media, and marketplaces.', open: false },
  { q: 'Which payment methods do you accept?',              a: 'We accept credit/debit cards (Stripe), EasyPaisa, JazzCash, and bank transfer for Pakistani customers.', open: false },
  { q: 'Can I create custom AI models for my brand?',       a: 'Yes — on Growth and above plans you can create custom AI model personas with specific ethnicity, body type, age range, and style preferences.', open: false },
  { q: 'How does Brand DNA work?',                          a: 'Upload your logo, campaign photos, and Instagram screenshots. Aces Model analyses your visual identity and applies your aesthetic to every generation automatically.', open: false },
])

function toggleFaq(faq) { faq.open = !faq.open }
</script>

<style scoped>
/* Infinite ticker scroll */
.ticker-track {
  animation: ticker 30s linear infinite;
}
@keyframes ticker {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}

/* Decorative background text scroll */
.decorative-scroll {
  display: inline-block;
  animation: scroll-rtl 55s linear infinite;
}
@keyframes scroll-rtl {
  from { transform: translateX(10%); }
  to   { transform: translateX(-60%); }
}

/* Featured on ticker */
.featured-track-wrapper {
  width: 100%;
}
.featured-track {
  display: flex;
  width: max-content;
  animation: badge-scroll 40s linear infinite;
}
.featured-track:hover {
  animation-play-state: paused;
}
@keyframes badge-scroll {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}

/* FAQ slide transition */
.faq-slide-enter-active,
.faq-slide-leave-active { transition: max-height 0.3s ease, opacity 0.2s ease; max-height: 200px; overflow: hidden; }
.faq-slide-enter-from,
.faq-slide-leave-to    { max-height: 0; opacity: 0; }
</style>
