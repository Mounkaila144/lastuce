<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useUiStore } from '@/stores/ui'
import AppLogo from './AppLogo.vue'
import NewsletterForm from '@/components/domain/NewsletterForm.vue'

const ui = useUiStore()
const year = new Date().getFullYear()

const explore = computed(() => [
  { to: `/${ui.locale}/episodes`, label: 'nav.episodes' },
  { to: `/${ui.locale}/astuces`, label: 'nav.tips' },
  { to: `/${ui.locale}/blog`, label: 'nav.blog' },
])

const informations = computed(() => [
  { to: `/${ui.locale}/about`, label: 'common.more' },
  { to: `/${ui.locale}/partenariats`, label: 'nav.partnerships' },
  { to: `/${ui.locale}/contact`, label: 'nav.contact' },
])

const social = [
  { href: 'https://www.facebook.com/lastuce', label: 'Facebook' },
  { href: 'https://www.youtube.com/@lastuce', label: 'YouTube' },
  { href: 'https://www.instagram.com/lastuce', label: 'Instagram' },
]
</script>

<template>
  <footer class="border-t border-surface-border bg-surface-1">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
      <div class="space-y-4">
        <AppLogo />
        <p class="max-w-xs text-sm text-surface-fg-muted">
          {{ $t('footer.tagline') }}
        </p>
        <div class="flex gap-3">
          <a
            v-for="item in social"
            :key="item.label"
            :href="item.href"
            target="_blank"
            rel="noopener noreferrer"
            :aria-label="item.label"
            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-surface-border bg-surface-0 text-surface-fg-muted transition hover:bg-surface-2 hover:text-surface-fg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
          >
            {{ item.label.charAt(0) }}
          </a>
        </div>
      </div>

      <nav aria-labelledby="footer-explore">
        <h2 id="footer-explore" class="text-sm font-semibold uppercase tracking-wide text-surface-fg">
          {{ $t('footer.explore') }}
        </h2>
        <ul class="mt-3 space-y-2 text-sm">
          <li v-for="link in explore" :key="link.to">
            <Link :href="link.to" class="text-surface-fg-muted transition hover:text-brand-700">
              {{ $t(link.label) }}
            </Link>
          </li>
        </ul>
      </nav>

      <nav aria-labelledby="footer-info">
        <h2 id="footer-info" class="text-sm font-semibold uppercase tracking-wide text-surface-fg">
          {{ $t('footer.informations') }}
        </h2>
        <ul class="mt-3 space-y-2 text-sm">
          <li v-for="link in informations" :key="link.to">
            <Link :href="link.to" class="text-surface-fg-muted transition hover:text-brand-700">
              {{ $t(link.label) }}
            </Link>
          </li>
        </ul>
      </nav>

      <div class="space-y-3">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-surface-fg">
          {{ $t('newsletter.submit') }}
        </h2>
        <p class="text-sm text-surface-fg-muted">
          {{ $t('newsletter.placeholder') }}.
        </p>
        <NewsletterForm source="footer" variant="stacked" />
      </div>
    </div>

    <div class="border-t border-surface-border">
      <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-3 px-4 py-4 text-xs text-surface-fg-muted sm:flex-row sm:items-center sm:px-6 lg:px-8">
        <p>© {{ year }} L'Astuce. {{ $t('footer.rights') }}</p>
        <div class="flex gap-4">
          <Link :href="`/${ui.locale}/legal`" class="transition hover:text-brand-700">{{ $t('footer.legal') }}</Link>
          <Link :href="`/${ui.locale}/privacy`" class="transition hover:text-brand-700">{{ $t('footer.privacy') }}</Link>
        </div>
      </div>
    </div>
  </footer>
</template>
