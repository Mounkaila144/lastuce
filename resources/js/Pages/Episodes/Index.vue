<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import EpisodeCard from '@/components/domain/EpisodeCard.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import { useDebounce } from '@/composables/useDebounce'
import { ref } from 'vue'
import type { EpisodeListItem } from '@/types/domain'
import type { SelectOption } from '@/components/ui/Select.vue'

interface Filters {
  sort: string
  type: string
  category: string
  month: string
  search: string
}

interface PaginatorMeta {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

interface PaginatedEpisodes {
  data: EpisodeListItem[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: PaginatorMeta
}

interface Options {
  types: SelectOption[]
  sorts: SelectOption[]
  categories: SelectOption[]
  months: SelectOption[]
}

const props = defineProps<{
  episodes: PaginatedEpisodes
  filters: Filters
  options: Options
}>()

const filters = reactive<Filters>({ ...props.filters })

const search = ref(filters.search)
const debouncedSearch = useDebounce(search, 300)

watch(debouncedSearch, (value) => {
  filters.search = value
  push()
})

watch(
  () => [filters.sort, filters.type, filters.category, filters.month],
  () => push(),
)

function push() {
  router.get(window.location.pathname, cleanFilters(), {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  })
}

function cleanFilters(): Record<string, string> {
  const out: Record<string, string> = {}
  for (const [key, val] of Object.entries(filters)) {
    if (val && val !== 'all') out[key] = String(val)
  }
  return out
}

function reset() {
  filters.sort = 'recent'
  filters.type = 'all'
  filters.category = ''
  filters.month = ''
  filters.search = ''
  search.value = ''
  push()
}

const typeOptions = computed(() => [
  { value: 'all', label: 'Tous' },
  ...props.options.types,
])

const empty = computed(() => props.episodes.data.length === 0)
</script>

<template>
  <Head title="Épisodes" />

  <section class="bg-surface-1 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <header class="mb-6">
        <h1 class="text-3xl font-bold">Tous les épisodes</h1>
        <p class="text-sm text-surface-fg-muted">
          {{ episodes.meta.total.toLocaleString('fr-FR') }} épisode{{ episodes.meta.total > 1 ? 's' : '' }} disponible{{ episodes.meta.total > 1 ? 's' : '' }}.
        </p>
      </header>

      <div class="grid gap-3 rounded-xl border border-surface-border bg-surface-0 p-4 md:grid-cols-12">
        <div class="md:col-span-4">
          <Input v-model="search" :placeholder="$t('header.searchPlaceholder')" :aria-label="$t('common.search')" />
        </div>
        <div class="md:col-span-2">
          <Select v-model="filters.type" :options="typeOptions" :aria-label="'Type'" />
        </div>
        <div v-if="options.categories.length" class="md:col-span-2">
          <Select v-model="filters.category" :options="[{ value: '', label: 'Toutes catégories' }, ...options.categories]" />
        </div>
        <div v-if="options.months.length" class="md:col-span-2">
          <Select v-model="filters.month" :options="[{ value: '', label: 'Toutes dates' }, ...options.months]" />
        </div>
        <div class="md:col-span-2">
          <Select v-model="filters.sort" :options="options.sorts" />
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div v-if="empty" class="rounded-xl border border-dashed border-surface-border bg-surface-1 p-12 text-center">
        <p class="text-base font-semibold">Aucun épisode ne correspond à vos critères.</p>
        <p class="mt-1 text-sm text-surface-fg-muted">Essayez de modifier vos filtres ou de réinitialiser la recherche.</p>
        <button
          type="button"
          class="mt-4 inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700"
          @click="reset"
        >
          Réinitialiser
        </button>
      </div>
      <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <EpisodeCard v-for="ep in episodes.data" :key="ep.id" :episode="ep" />
      </div>

      <nav v-if="!empty && episodes.meta.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-2" aria-label="Pagination">
        <Link
          v-for="link in episodes.links"
          :key="link.label + link.url"
          :href="link.url ?? '#'"
          v-html="link.label"
          :class="[
            'rounded-md border px-3 py-1.5 text-sm transition',
            link.active ? 'border-brand-600 bg-brand-600 text-white' : 'border-surface-border bg-surface-0 text-surface-fg hover:bg-surface-2',
            !link.url ? 'pointer-events-none opacity-40' : '',
          ]"
          preserve-scroll
        />
      </nav>
    </div>
  </section>
</template>
