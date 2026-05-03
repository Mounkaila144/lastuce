<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Dropdown from '@/components/ui/Dropdown.vue'
import { useUiStore } from '@/stores/ui'
import type { SupportedLocale } from '@/types/inertia'

const ui = useUiStore()
const page = usePage()

const available = computed<SupportedLocale[]>(() => (page.props.availableLocales as SupportedLocale[] | undefined) ?? ['fr', 'en'])
const current = computed<SupportedLocale>(() => ui.locale ?? (page.props.locale as SupportedLocale | undefined) ?? 'fr')

const labels: Record<SupportedLocale, string> = {
  fr: 'FR',
  en: 'EN',
}

function switchLocale(locale: SupportedLocale) {
  if (locale === current.value) return
  // Le préfixe locale est dans l'URL — on remplace le 1er segment.
  const path = window.location.pathname
  const next = path.replace(/^\/(fr|en)(\/|$)/, `/${locale}$2`)
  const target = next === path ? `/${locale}${path}` : next
  router.visit(target + window.location.search, { preserveScroll: true })
}
</script>

<template>
  <Dropdown align="right">
    <template #trigger>
      <span class="inline-flex items-center gap-1.5 text-sm font-medium">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path d="M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16Zm0-1.5a6.5 6.5 0 0 0 4.74-2.05A12.6 12.6 0 0 1 10 13.5a12.6 12.6 0 0 1-4.74.95A6.5 6.5 0 0 0 10 16.5Zm-5.94-3.95A11.06 11.06 0 0 0 10 12c2.04 0 4.04.2 5.94.55a6.5 6.5 0 0 0-.5-4.55A11.06 11.06 0 0 0 10 8c-1.91 0-3.78.27-5.45.99a6.5 6.5 0 0 0-.5 3.55ZM10 3.5A6.5 6.5 0 0 0 5.26 5.55 12.6 12.6 0 0 1 10 6.5c1.61 0 3.21-.32 4.74-.95A6.5 6.5 0 0 0 10 3.5Z" />
        </svg>
        {{ labels[current] }}
      </span>
    </template>
    <button
      v-for="loc in available"
      :key="loc"
      type="button"
      role="menuitem"
      :aria-current="loc === current ? 'true' : undefined"
      :class="[
        'flex w-full items-center gap-2 px-3 py-2 text-sm',
        loc === current ? 'bg-surface-2 font-semibold text-brand-700' : 'text-surface-fg hover:bg-surface-2',
      ]"
      @click="switchLocale(loc)"
    >
      {{ labels[loc] }}
    </button>
  </Dropdown>
</template>
