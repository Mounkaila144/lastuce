<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import BlogArticleCard from '@/components/domain/BlogArticleCard.vue'
import BlogSidebar from '@/components/domain/BlogSidebar.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import { useDebounce } from '@/composables/useDebounce'
import type { BlogArticleListItem } from '@/types/domain'
import type { SelectOption } from '@/components/ui/Select.vue'

interface Filters {
  sort: string
  categorie: string
  search: string
  year: number | null
  month: number | null
}

interface PaginatorMeta {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

interface PaginatedArticles {
  data: BlogArticleListItem[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: PaginatorMeta
}

interface CategoryOption {
  slug: string
  name: string
  count: number
}

interface ArchiveOption {
  value: string
  year: number
  month: number
  count: number
}

interface Sidebar {
  recent: { slug: string; titre: string; date_publication?: string | null }[]
  popular: { slug: string; titre: string; vues: number }[]
  categories: CategoryOption[]
  archives: ArchiveOption[]
}

const props = defineProps<{
  articles: PaginatedArticles
  filters: Filters
  options: {
    sorts: SelectOption[]
    categories: CategoryOption[]
    archives: ArchiveOption[]
  }
  sidebar: Sidebar
}>()

const filters = reactive<Filters>({ ...props.filters })

const search = ref(filters.search)
const debouncedSearch = useDebounce(search, 300)

watch(debouncedSearch, (value) => {
  filters.search = value
  push()
})

watch(() => [filters.sort, filters.categorie], () => push())

function push() {
  router.get(window.location.pathname, cleanFilters(), {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  })
}

function cleanFilters(): Record<string, string> {
  const out: Record<string, string> = {}
  if (filters.search) out.search = filters.search
  if (filters.sort && filters.sort !== 'recent') out.sort = filters.sort
  if (filters.categorie) out.categorie = filters.categorie
  return out
}

function reset() {
  filters.sort = 'recent'
  filters.categorie = ''
  filters.search = ''
  search.value = ''
  push()
}

const categoryOptions = computed<SelectOption[]>(() => [
  { value: '', label: 'Toutes catégories' },
  ...props.options.categories.map((c) => ({ value: c.slug, label: c.name })),
])

const empty = computed(() => props.articles.data.length === 0)

const monthLabel = (month: number) =>
  new Date(2000, month - 1, 1).toLocaleDateString('fr-FR', { month: 'long' })

const contextHeader = computed(() => {
  if (props.filters.year && props.filters.month) {
    return `Articles de ${monthLabel(props.filters.month)} ${props.filters.year}`
  }
  if (props.filters.year) {
    return `Articles de ${props.filters.year}`
  }
  if (props.filters.search) {
    return `Résultats pour « ${props.filters.search} »`
  }
  return null
})
</script>

<template>
  <Head title="Blog" />

  <section class="bg-surface-1 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <header class="mb-6">
        <h1 class="text-3xl font-bold">{{ contextHeader ?? 'Blog' }}</h1>
        <p class="text-sm text-surface-fg-muted">
          {{ articles.meta.total.toLocaleString('fr-FR') }}
          article{{ articles.meta.total > 1 ? 's' : '' }} publié{{ articles.meta.total > 1 ? 's' : '' }}.
        </p>
      </header>

      <div class="grid gap-3 rounded-xl border border-surface-border bg-surface-0 p-4 md:grid-cols-12">
        <div class="md:col-span-6">
          <Input
            v-model="search"
            :placeholder="$t('header.searchPlaceholder')"
            :aria-label="$t('common.search')"
          />
        </div>
        <div v-if="options.categories.length" class="md:col-span-3">
          <Select
            v-model="filters.categorie"
            :options="categoryOptions"
            aria-label="Catégorie"
          />
        </div>
        <div class="md:col-span-3">
          <Select v-model="filters.sort" :options="options.sorts" aria-label="Tri" />
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:px-8">
      <div>
        <div
          v-if="empty"
          class="rounded-xl border border-dashed border-surface-border bg-surface-1 p-12 text-center"
        >
          <p class="text-base font-semibold">Aucun article ne correspond à votre recherche.</p>
          <p class="mt-1 text-sm text-surface-fg-muted">
            Essayez de modifier vos filtres ou de réinitialiser la recherche.
          </p>
          <button
            type="button"
            class="mt-4 inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700"
            @click="reset"
          >
            Réinitialiser
          </button>
        </div>

        <div v-else class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
          <BlogArticleCard
            v-for="article in articles.data"
            :key="article.id"
            :article="article"
          />
        </div>

        <nav
          v-if="!empty && articles.meta.last_page > 1"
          class="mt-8 flex flex-wrap justify-center gap-2"
          aria-label="Pagination"
        >
          <Link
            v-for="link in articles.links"
            :key="link.label + link.url"
            :href="link.url ?? '#'"
            v-html="link.label"
            :class="[
              'rounded-md border px-3 py-1.5 text-sm transition',
              link.active
                ? 'border-brand-600 bg-brand-600 text-white'
                : 'border-surface-border bg-surface-0 text-surface-fg hover:bg-surface-2',
              !link.url ? 'pointer-events-none opacity-40' : '',
            ]"
            preserve-scroll
          />
        </nav>
      </div>

      <BlogSidebar
        :recent="sidebar.recent"
        :popular="sidebar.popular"
        :categories="sidebar.categories"
        :archives="sidebar.archives"
      />
    </div>
  </section>
</template>
