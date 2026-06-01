import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // Public landing page (no auth required)
  { path: '/', name: 'Landing', component: () => import('@/views/LandingView.vue') },

  // Public model detail page (no auth required)
  { path: '/models/:id', name: 'ModelDetail', component: () => import('@/views/ModelDetailView.vue') },

  // Auth (no layout)
  { path: '/login',    name: 'Login',    component: () => import('@/views/auth/LoginView.vue'),    meta: { guest: true } },
  { path: '/register', name: 'Register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guest: true } },

  // App (with AppLayout) — all under /studio
  {
    path: '/studio',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '',              name: 'Studio',    component: () => import('@/views/StudioView.vue') },
      { path: 'campaigns',     name: 'Campaigns', component: () => import('@/views/CampaignsView.vue') },
      { path: 'campaigns/:id', name: 'Campaign',  component: () => import('@/views/CampaignView.vue') },
      { path: 'assets',        name: 'Assets',    component: () => import('@/views/AssetsView.vue') },
      { path: 'brand-dna',     name: 'BrandDNA',  component: () => import('@/views/BrandDnaView.vue') },
      { path: 'models',        name: 'Models',    component: () => import('@/views/ModelsView.vue') },
      { path: 'analytics',     name: 'Analytics', component: () => import('@/views/AnalyticsView.vue') },
      { path: 'billing',       name: 'Billing',   component: () => import('@/views/BillingView.vue') },
      { path: 'settings',      name: 'Settings',  component: () => import('@/views/SettingsView.vue') },
    ],
  },

  // Super Admin area
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      { path: '',           redirect: '/admin/dashboard' },
      { path: 'dashboard',       name: 'AdminDashboard',      component: () => import('@/views/admin/AdminDashboardView.vue') },
      { path: 'users',           name: 'AdminUsers',          component: () => import('@/views/admin/AdminUsersView.vue') },
      { path: 'models',          name: 'AdminModels',         component: () => import('@/views/admin/AdminModelsView.vue') },
      { path: 'model-personas',  name: 'AdminModelPersonas',  component: () => import('@/views/admin/AdminModelPersonasView.vue') },
      { path: 'subscriptions',   name: 'AdminSubscriptions',  component: () => import('@/views/admin/AdminSubscriptionsView.vue') },
      { path: 'ai-config',       name: 'AdminAiConfig',       component: () => import('@/views/admin/AdminAiConfigView.vue') },
    ],
  },

  // Public lookbook
  { path: '/lookbook/:slug', name: 'Lookbook', component: () => import('@/views/LookbookPublicView.vue') },

  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Try to restore session once
  if (!auth.user && auth.token) {
    await auth.fetchMe()
  }

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'Login' }
  }

  // Block non-admins from admin routes
  if (to.meta.requiresAdmin && !auth.isSuperAdmin) {
    return { name: 'Studio' }
  }

  // Block superadmins from studio routes
  if (to.path.startsWith('/studio') && auth.isSuperAdmin) {
    return { name: 'AdminDashboard' }
  }

  // Redirect logged-in users away from /login and /register
  if (to.meta.guest && auth.isLoggedIn) {
    return auth.isSuperAdmin ? { name: 'AdminDashboard' } : { name: 'Studio' }
  }
})

export default router
