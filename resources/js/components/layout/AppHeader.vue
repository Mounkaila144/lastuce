<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useUiStore } from '@/stores/ui'
import AppLogo from './AppLogo.vue'
import GlobalSearch from './GlobalSearch.vue'
import LocaleSwitcher from './LocaleSwitcher.vue'
import MobileMenu from './MobileMenu.vue'

const ui = useUiStore()

const navLinks = computed(() => [
  { to: `/${ui.locale}/episodes`, label: 'nav.episodes' },
  { to: `/${ui.locale}/astuces`, label: 'nav.tips' },
  { to: `/${ui.locale}/blog`, label: 'nav.blog' },
  { to: `/${ui.locale}/partenariats`, label: 'nav.partnerships' },
  { to: `/${ui.locale}/contact`, label: 'nav.contact' },
])
</script>

<template>
  <header class="sticky top-0 z-40 border-b border-surface-border bg-surface-0/90 backdrop-blur supports-[backdrop-filter]:bg-surface-0/75">
    <a
      href="#main"
      class="absolute left-2 top-2 -translate-y-16 rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white focus:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
    >
      {{ $t('header.skipToContent') }}
    </a>
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
      <Link :href="`/${ui.locale}`" class="flex items-center focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 rounded-lg">
        <AppLogo />
      </Link>

      <nav class="hidden lg:flex" aria-label="Navigation principale">
        <ul class="flex items-center gap-1">
          <li v-for="link in navLinks" :key="link.to">
            <Link
              :href="link.to"
              class="rounded-md px-3 py-2 text-sm font-medium text-surface-fg-muted transition hover:bg-surface-2 hover:text-surface-fg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
            >
              {{ $t(link.label) }}
            </Link>
          </li>
        </ul>
      </nav>

      <div class="ml-auto hidden flex-1 max-w-md lg:block">
        <GlobalSearch :placeholder="$t('header.searchPlaceholder')" />
      </div>

      <div class="ml-auto flex items-center gap-2 lg:ml-0">
        <div class="hidden lg:block">
          <LocaleSwitcher />
        </div>
        <Link
          :href="`/${ui.locale}/astuces/create`"
          class="hidden lg:inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
        >
          {{ $t('nav.submitTip') }}
        </Link>
        <button
          type="button"
          class="inline-flex h-10 w-10 items-center justify-center rounded-md text-surface-fg-muted hover:bg-surface-2 lg:hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
          :aria-label="$t('header.openMenu')"
          :aria-expanded="ui.mobileMenuOpen"
          aria-controls="mobile-menu"
          @click="ui.toggleMobileMenu()"
        >
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M3 5.5A.75.75 0 0 1 3.75 4.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.5Zm0 4A.75.75 0 0 1 3.75 8.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 9.5Zm.75 3.25a.75.75 0 0 0 0 1.5h12.5a.75.75 0 0 0 0-1.5H3.75Z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>
    <MobileMenu />
  </header>
</template>
