<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useUiStore } from '@/stores/ui'

interface RecentItem {
  slug: string
  titre: string
  date_publication?: string | null
}

interface PopularItem {
  slug: string
  titre: string
  vues: number
}

interface CategoryItem {
  slug: string
  name: string
  count: number
}

interface ArchiveItem {
  value: string
  year: number
  month: number
  count: number
}

defineProps<{
  recent: RecentItem[]
  popular: PopularItem[]
  categories: CategoryItem[]
  archives: ArchiveItem[]
}>()

const ui = useUiStore()

const monthLabel = (month: number) =>
  new Date(2000, month - 1, 1).toLocaleDateString('fr-FR', { month: 'long' })

const blogPath = computed(() => `/${ui.locale}/blog`)
</script>

<template>
  <aside class="space-y-6">
    <section v-if="recent.length" class="rounded-xl border border-surface-border bg-surface-0 p-4">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-surface-fg-muted">
        Articles récents
      </h2>
      <ul class="space-y-2">
        <li v-for="item in recent" :key="item.slug">
          <Link
            :href="`${blogPath}/${item.slug}`"
            class="block rounded text-sm text-surface-fg hover:text-brand-700"
          >
            {{ item.titre }}
          </Link>
        </li>
      </ul>
    </section>

    <section v-if="popular.length" class="rounded-xl border border-surface-border bg-surface-0 p-4">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-surface-fg-muted">
        Populaires
      </h2>
      <ul class="space-y-2">
        <li v-for="item in popular" :key="item.slug" class="flex items-baseline justify-between gap-3">
          <Link
            :href="`${blogPath}/${item.slug}`"
            class="line-clamp-2 flex-1 rounded text-sm text-surface-fg hover:text-brand-700"
          >
            {{ item.titre }}
          </Link>
          <span class="shrink-0 text-xs text-surface-fg-muted">{{ item.vues.toLocaleString('fr-FR') }} vues</span>
        </li>
      </ul>
    </section>

    <section v-if="categories.length" class="rounded-xl border border-surface-border bg-surface-0 p-4">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-surface-fg-muted">
        Catégories
      </h2>
      <ul class="space-y-1">
        <li v-for="cat in categories" :key="cat.slug">
          <Link
            :href="`${blogPath}/category/${cat.slug}`"
            class="flex items-center justify-between rounded px-2 py-1 text-sm text-surface-fg hover:bg-surface-2"
          >
            <span>{{ cat.name }}</span>
            <span class="text-xs text-surface-fg-muted">{{ cat.count }}</span>
          </Link>
        </li>
      </ul>
    </section>

    <section v-if="archives.length" class="rounded-xl border border-surface-border bg-surface-0 p-4">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-surface-fg-muted">
        Archives
      </h2>
      <ul class="space-y-1">
        <li v-for="arch in archives" :key="arch.value">
          <Link
            :href="`${blogPath}/archive/${arch.year}/${arch.month}`"
            class="flex items-center justify-between rounded px-2 py-1 text-sm text-surface-fg hover:bg-surface-2"
          >
            <span class="capitalize">{{ monthLabel(arch.month) }} {{ arch.year }}</span>
            <span class="text-xs text-surface-fg-muted">{{ arch.count }}</span>
          </Link>
        </li>
      </ul>
    </section>
  </aside>
</template>
