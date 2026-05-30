<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLogo from '@/components/layout/AppLogo.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'
import { adminPageTitle } from '@/composables/useAdminTitle'

interface AdminUser {
  id: number
  name: string
  email: string
  role?: string | null
}

interface AdminSharedProps {
  admin?: {
    user: AdminUser | null
    pending_counts?: {
      astuces?: number
      partenariats?: number
      newsletter?: number
    }
  }
}

const page = usePage()
const adminProps = computed<AdminSharedProps['admin']>(() => {
  const props = page.props as Record<string, unknown>
  return (props.admin as AdminSharedProps['admin']) ?? undefined
})

const user = computed(() => adminProps.value?.user ?? null)
const pendingCounts = computed(() => adminProps.value?.pending_counts ?? {})

const sidebarOpen = ref(false)

interface NavItem {
  label: string
  href: string
  exact?: boolean
  badge?: number
}
interface NavSection {
  label: string
  items: NavItem[]
}

const navSections = computed<NavSection[]>(() => [
  {
    label: 'Pilotage',
    items: [
      { label: 'Tableau de bord', href: '/admin/dashboard', exact: true },
    ],
  },
  {
    label: 'Contenus',
    items: [
      { label: 'Épisodes', href: '/admin/episodes' },
      {
        label: 'Astuces',
        href: '/admin/astuces',
        badge: pendingCounts.value.astuces ?? 0,
      },
      { label: 'Articles blog', href: '/admin/blog' },
      { label: 'Galerie', href: '/admin/gallery' },
    ],
  },
  {
    label: 'Communauté',
    items: [
      { label: 'Partenaires (logos)', href: '/admin/partners' },
      {
        label: 'Partenariats',
        href: '/admin/partenariats',
        badge: pendingCounts.value.partenariats ?? 0,
      },
      {
        label: 'Newsletter',
        href: '/admin/newsletter',
        badge: pendingCounts.value.newsletter ?? 0,
      },
    ],
  },
  {
    label: 'Administration',
    items: [
      { label: 'Utilisateurs', href: '/admin/users' },
      { label: 'Logs & sécurité', href: '/admin/security/logs' },
      { label: 'Paramètres', href: '/admin/settings' },
    ],
  },
])

const currentPath = computed(() => {
  const url = (page.url as string | undefined) ?? ''
  return url.split('?')[0]
})

function isActive(href: string, exact = false): boolean {
  if (exact) return currentPath.value === href
  return currentPath.value === href || currentPath.value.startsWith(`${href}/`)
}

function logout() {
  router.post(
    '/admin/logout',
    {},
    {
      preserveScroll: false,
      onSuccess: () => {
        // Redirect handled server-side.
      },
    },
  )
}
</script>

<template>
  <div class="flex min-h-screen bg-slate-50 text-slate-900 antialiased">
    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform lg:static lg:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
      ]"
      aria-label="Navigation administration"
    >
      <div class="flex h-16 items-center border-b border-slate-200 px-5">
        <Link href="/admin/dashboard" class="flex items-center gap-2">
          <AppLogo compact />
          <span class="text-sm font-bold text-brand-700">Admin</span>
        </Link>
      </div>

      <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5 text-sm">
        <div v-for="section in navSections" :key="section.label">
          <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            {{ section.label }}
          </p>
          <ul class="space-y-0.5">
            <li v-for="item in section.items" :key="item.href">
              <Link
                :href="item.href"
                :class="[
                  'flex items-center justify-between gap-2 rounded-md px-3 py-2 transition',
                  isActive(item.href, item.exact)
                    ? 'bg-brand-50 font-semibold text-brand-700'
                    : 'text-slate-700 hover:bg-slate-100',
                ]"
              >
                <span>{{ item.label }}</span>
                <span
                  v-if="item.badge && item.badge > 0"
                  class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-amber-100 px-1.5 text-xs font-semibold text-amber-800"
                >
                  {{ item.badge }}
                </span>
              </Link>
            </li>
          </ul>
        </div>
      </nav>

      <div class="border-t border-slate-200 p-4">
        <div v-if="user" class="mb-3 text-xs text-slate-500">
          Connecté en tant que
          <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ user.name }}</p>
          <p class="truncate">{{ user.email }}</p>
        </div>
        <button
          type="button"
          class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
          @click="logout"
        >
          Se déconnecter
        </button>
      </div>
    </aside>

    <!-- Backdrop mobile -->
    <button
      v-if="sidebarOpen"
      type="button"
      class="fixed inset-0 z-20 bg-slate-900/50 lg:hidden"
      aria-label="Fermer la navigation"
      @click="sidebarOpen = false"
    />

    <!-- Main column -->
    <div class="flex min-h-screen flex-1 flex-col">
      <header class="flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:px-8">
        <button
          type="button"
          class="rounded-md border border-slate-200 p-2 text-slate-700 hover:bg-slate-100 lg:hidden"
          aria-label="Ouvrir la navigation"
          :aria-expanded="sidebarOpen"
          @click="sidebarOpen = !sidebarOpen"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
          </svg>
        </button>
        <h1 class="flex-1 text-base font-semibold text-slate-800">
          {{ adminPageTitle }}
        </h1>
        <Link
          href="/fr"
          class="hidden text-xs font-medium text-slate-500 hover:text-brand-700 sm:block"
          target="_blank"
        >
          Voir le site →
        </Link>
      </header>

      <main class="flex-1 px-4 py-6 lg:px-8">
        <slot />
      </main>
    </div>

    <ToastContainer />
  </div>
</template>
