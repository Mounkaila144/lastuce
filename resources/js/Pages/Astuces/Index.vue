<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import AstuceCard, { type AstuceCardItem } from '@/components/domain/AstuceCard.vue'
import { useDebounce } from '@/composables/useDebounce'
import { useUiStore } from '@/stores/ui'
import type { SelectOption } from '@/components/ui/Select.vue'

interface Filters {
  category: string
  difficulte: string
  search: string
}

interface PaginatedAstuces {
  data: AstuceCardItem[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number }
}

const props = defineProps<{
  astuces: PaginatedAstuces
  filters: Filters
  options: { categories: SelectOption[]; difficultes: SelectOption[] }
}>()

const ui = useUiStore()
const filters = reactive<Filters>({ ...props.filters })
const search = ref(filters.search)
const debounced = useDebounce(search, 300)

watch(debounced, (value) => {
  filters.search = value
  push()
})
watch(() => [filters.category, filters.difficulte], () => push())

function push() {
  const out: Record<string, string> = {}
  for (const [k, v] of Object.entries(filters)) if (v) out[k] = String(v)
  router.get(window.location.pathname, out, { preserveScroll: true, preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Astuces de la communauté" />

  <section class="bg-surface-1 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="text-3xl font-bold">Astuces de la communauté</h1>
          <p class="text-sm text-surface-fg-muted">
            {{ astuces.meta.total.toLocaleString('fr-FR') }} astuce{{ astuces.meta.total > 1 ? 's' : '' }} approuvée{{ astuces.meta.total > 1 ? 's' : '' }}.
          </p>
        </div>
        <Link
          :href="`/${ui.locale}/astuces/create`"
          class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700"
        >
          Proposer la mienne
        </Link>
      </div>

      <div class="mt-6 grid gap-3 rounded-xl border border-surface-border bg-surface-0 p-4 md:grid-cols-12">
        <div class="md:col-span-6">
          <Input v-model="search" placeholder="Rechercher une astuce…" />
        </div>
        <div class="md:col-span-3">
          <Select v-model="filters.category" :options="[{ value: '', label: 'Toutes catégories' }, ...options.categories]" />
        </div>
        <div class="md:col-span-3">
          <Select v-model="filters.difficulte" :options="[{ value: '', label: 'Toutes difficultés' }, ...options.difficultes]" />
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div v-if="astuces.data.length === 0" class="rounded-xl border border-dashed border-surface-border bg-surface-1 p-12 text-center">
        <p class="text-base font-semibold">Aucune astuce ne correspond à vos critères.</p>
        <p class="mt-1 text-sm text-surface-fg-muted">Essayez de modifier vos filtres.</p>
      </div>
      <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <AstuceCard v-for="a in astuces.data" :key="a.id" :astuce="a" />
      </div>

      <nav v-if="astuces.meta.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-2" aria-label="Pagination">
        <Link
          v-for="link in astuces.links"
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
