<template>
  <div class="max-w-screen-xl mx-auto px-6 py-10">
    <div class="mb-10">
      <h1 class="heading-lg text-white">Billing</h1>
      <p class="text-muted mt-1">Upgrade to unlock more generations</p>
    </div>

    <!-- Current plan chip -->
    <div v-if="auth.plan !== 'free'" class="glass rounded-2xl p-4 border border-accent/30 flex items-center gap-4 mb-10">
      <div class="w-10 h-10 rounded-full bg-accent-glow border border-accent/30 flex items-center justify-center">
        <i class="fa-solid fa-star text-accent"></i>
      </div>
      <div>
        <p class="text-white font-medium capitalize">{{ auth.plan }} plan active</p>
        <p class="text-muted text-sm">{{ auth.credits }} credits remaining</p>
      </div>
    </div>

    <!-- Plan cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
      <div v-for="plan in plans" :key="plan.id"
        class="glass rounded-3xl p-6 border flex flex-col"
        :class="plan.popular ? 'border-accent/50' : 'border-border'">
        <!-- Popular badge -->
        <div v-if="plan.popular" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-accent/20 text-accent text-xs font-medium mb-3 self-start">
          <i class="fa-solid fa-star text-xs"></i> Most Popular
        </div>
        <h3 class="text-lg font-medium text-white mb-1">{{ plan.name }}</h3>
        <div class="mb-4">
          <span class="text-3xl font-light text-white">${{ plan.price }}</span>
          <span class="text-muted text-sm">/mo</span>
        </div>
        <p class="text-muted text-sm mb-4">{{ plan.credits }} AI credits</p>
        <ul class="space-y-2 flex-1">
          <li v-for="f in plan.features" :key="f" class="flex items-start gap-2 text-sm text-soft">
            <i class="fa-solid fa-check text-accent mt-0.5 flex-shrink-0"></i> {{ f }}
          </li>
        </ul>
        <button
          @click="selectPlan(plan)"
          :disabled="auth.plan === plan.id"
          class="mt-6 w-full justify-center py-3 disabled:opacity-40 disabled:cursor-not-allowed"
          :class="plan.popular ? 'btn-accent' : 'btn-ghost'"
        >
          {{ auth.plan === plan.id ? 'Current Plan' : 'Get Started' }}
        </button>
      </div>
    </div>

    <!-- Payment gateway selection modal -->
    <Transition name="fade-up">
      <div v-if="selectedPlan" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="selectedPlan = null"></div>
        <div class="relative w-full max-w-md glass rounded-3xl p-7 border border-border">
          <h3 class="font-medium text-white mb-1">Subscribe to {{ selectedPlan.name }}</h3>
          <p class="text-muted text-sm mb-6">Choose a payment method to continue</p>

          <div class="space-y-3">
            <button v-for="gw in gateways" :key="gw.id"
              @click="initiatePayment(gw.id)"
              :disabled="paying === gw.id"
              class="w-full glass-hover rounded-2xl p-4 border border-border flex items-center gap-4 text-left hover:border-accent/40 transition-colors disabled:opacity-50">
              <div class="w-10 h-10 rounded-xl bg-surface border border-border flex items-center justify-center flex-shrink-0">
                <i :class="gw.icon" class="text-lg text-soft"></i>
              </div>
              <div>
                <p class="text-sm font-medium text-white">{{ gw.label }}</p>
                <p class="text-xs text-muted">{{ gw.desc }}</p>
              </div>
              <div v-if="paying === gw.id" class="ml-auto">
                <i class="fa-solid fa-spinner fa-spin text-accent"></i>
              </div>
            </button>
          </div>

          <!-- Bank transfer receipt info -->
          <div v-if="bankInfo" class="mt-5 p-4 rounded-2xl bg-accent-glow border border-accent/20">
            <p class="text-sm font-medium text-accent mb-2">Bank Transfer Details</p>
            <p class="text-xs text-soft">{{ bankInfo.bank_name }}</p>
            <p class="text-xs text-soft">{{ bankInfo.account_title }}</p>
            <p class="text-xs text-soft font-mono">{{ bankInfo.account_number }}</p>
            <p class="text-xs text-soft font-mono">IBAN: {{ bankInfo.iban }}</p>
            <p class="text-xs text-muted mt-2">Send receipt to support@threadai.com after payment</p>
          </div>

          <button @click="selectedPlan = null; bankInfo = null" class="mt-4 btn-ghost w-full justify-center text-sm">Cancel</button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/lib/api'

const auth         = useAuthStore()
const selectedPlan = ref(null)
const paying       = ref('')
const bankInfo     = ref(null)

const plans = [
  {
    id: 'starter', name: 'Starter', price: 29, credits: 200, popular: false,
    features: ['200 AI generations/mo', '5 campaigns', 'All content types', 'Email support'],
  },
  {
    id: 'growth', name: 'Growth', price: 79, credits: 600, popular: true,
    features: ['600 AI generations/mo', 'Unlimited campaigns', 'Custom AI models', 'Brand DNA analysis', 'Priority support'],
  },
  {
    id: 'agency', name: 'Agency', price: 199, credits: 2000, popular: false,
    features: ['2,000 AI generations/mo', 'Multiple brands', 'Lookbook publishing', 'Analytics', 'Dedicated support'],
  },
  {
    id: 'enterprise', name: 'Enterprise', price: 499, credits: 9999, popular: false,
    features: ['Unlimited generations', 'White-label export', 'API access', 'Custom AI training', 'SLA support'],
  },
]

const gateways = [
  { id: 'stripe',        icon: 'fa-brands fa-stripe',           label: 'Credit/Debit Card', desc: 'Visa, Mastercard, Amex via Stripe' },
  { id: 'easypaisa',     icon: 'fa-solid fa-mobile-screen-button', label: 'EasyPaisa',       desc: 'Pay via EasyPaisa mobile wallet' },
  { id: 'jazzcash',      icon: 'fa-solid fa-mobile-screen-button', label: 'JazzCash',        desc: 'Pay via JazzCash mobile wallet' },
  { id: 'bank_transfer', icon: 'fa-solid fa-building-columns',  label: 'Bank Transfer',     desc: 'Manual wire / IBFT transfer' },
]

function selectPlan(plan) {
  selectedPlan.value = plan
  bankInfo.value = null
}

async function initiatePayment(gatewayId) {
  paying.value = gatewayId
  try {
    const { data } = await api.post('/payments/initiate', {
      plan: selectedPlan.value.id,
      gateway: gatewayId,
    })
    if (gatewayId === 'stripe' || gatewayId === 'easypaisa' || gatewayId === 'jazzcash') {
      if (data.url) window.location.href = data.url
      else if (data.form_post) {
        // Build and submit form
        const form = document.createElement('form')
        form.method = 'POST'
        form.action = data.action_url
        Object.entries(data.form_data ?? {}).forEach(([k, v]) => {
          const input = document.createElement('input')
          input.type = 'hidden'; input.name = k; input.value = v
          form.appendChild(input)
        })
        document.body.appendChild(form)
        form.submit()
      }
    } else if (gatewayId === 'bank_transfer') {
      bankInfo.value = data.bank_details
    }
  } catch (err) {
    console.error(err)
  } finally {
    paying.value = ''
  }
}
</script>
