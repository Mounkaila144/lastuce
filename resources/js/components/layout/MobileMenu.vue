<script setup lang="ts">
import { computed, ref, toRef, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useFocusTrap } from '@/composables/useFocusTrap'
import { useUiStore } from '@/stores/ui'
import GlobalSearch from './GlobalSearch.vue'
import LocaleSwitcher from './LocaleSwitcher.vue'
import AppLogo from './AppLogo.vue'

const ui = useUiStore()
const drawer = ref<HTMLElement | null>(null)
const open = computed(() => ui.mobileMenuOpen)

useFocusTrap({
  active: toRef(open, 'value'),
  container: drawer,
  onEscape: () => ui.closeMobileMenu(),
})

watch(open, (value) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = value ? 'hidden' : ''
})

const navLinks = computed(() => [
  { to: `/${ui.locale}`, label: 'nav.home' },
  { to: `/${ui.locale}/episodes`, label: 'nav.episodes' },
  { to: `/${ui.locale}/astuces`, label: 'nav.tips' },
  { to: `/${ui.locale}/blog`, label: 'nav.blog' },
  { to: `/${ui.locale}/galerie`, label: 'nav.gallery' },
  { to: `/${ui.locale}/partenariats`, label: 'nav.partnerships' },
  { to: `/${ui.locale}/contact`, label: 'nav.contact' },
])
</script>

<template>
  <Teleport to="body">
    <Transition name="drawer">
      <div
        v-if="open"
        class="fixed inset-0 z-50 lg:hidden"
        role="presentation"
      >
        <div
          class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
          aria-hidden="true"
          @click="ui.closeMobileMenu"
        />
        <aside
          ref="drawer"
          role="dialog"
          aria-modal="true"
          :aria-label="$t('header.openMenu')"
          tabindex="-1"
          class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-surface-0 text-surface-fg shadow-xl"
        >
          <header class="flex items-center justify-between border-b border-surface-border px-4 py-3">
            <AppLogo />
            <button
              type="button"
              :aria-label="$t('header.closeMenu')"
              class="rounded-md p-2 text-surface-fg-muted transition hover:bg-surface-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
              @click="ui.closeMobileMenu"
            >
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
              </svg>
            </button>
          </header>
          <div class="border-b border-surface-border p-4">
            <GlobalSearch :placeholder="$t('header.searchPlaceholder')" />
          </div>
          <nav class="flex-1 overflow-y-auto px-2 py-4" aria-label="Navigation principale">
            <ul class="space-y-1">
              <li v-for="link in navLinks" :key="link.to">
                <Link
                  :href="link.to"
                  class="flex items-center rounded-lg px-3 py-2.5 text-base font-medium text-surface-fg hover:bg-surface-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                  @click="ui.closeMobileMenu"
                >
                  {{ $t(link.label) }}
                </Link>
              </li>
            </ul>
          </nav>
          <footer class="flex items-center justify-between gap-3 border-t border-surface-border px-4 py-3">
            <LocaleSwitcher />
            <Link
              :href="`/${ui.locale}/astuces/create`"
              class="inline-flex h-10 flex-1 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
              @click="ui.closeMobileMenu"
            >
              {{ $t('nav.submitTip') }}
            </Link>
          </footer>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 0.2s ease;
}
.drawer-enter-active aside,
.drawer-leave-active aside {
  transition: transform 0.25s ease;
}
.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
}
.drawer-enter-from aside,
.drawer-leave-to aside {
  transform: translateX(100%);
}
</style>
