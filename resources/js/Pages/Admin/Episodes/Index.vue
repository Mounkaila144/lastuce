<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import { useDebounce } from '@/composables/useDebounce'
import type { SelectOption } from '@/components/ui/Select.vue'

defineOptions({ layout: AdminLayout })

interface EpisodeRow {
  id: number
  titre: string
  slug: string
  type: string
  type_label: string
  statut: string
  statut_label: string
  categorie?: string | null
  date_publication?: string | null
  vues: number
  thumbnail_url?: string | null
  edit_url: string
  show_url: string
}

interface Filters {
  search: string
  status: string
  type: string
  sort_by: string
  sort_order: string
}

interface Paginated {
  data: EpisodeRow[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

const props = defineProps<{
  episodes: Paginated
  filters: Filters
  options: { statuses: SelectOption[]; types: SelectOption[] }
  stats: { total: number; published: number; draft: number; scheduled: number }
}>()

const filters = reactive<Filters>({ ...props.filters })
const search = ref(filters.search)
const debouncedSearch = useDebounce(search, 300)

watch(debouncedSearch, (v) => {
  filters.search = v
  push()
})
watch(() => [filters.status, filters.type, filters.sort_by, filters.sort_order], () => push())

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
  if (filters.status) out.status = filters.status
  if (filters.type) out.type = filters.type
  if (filters.sort_by !== 'created_at') out.sort_by = filters.sort_by
  if (filters.sort_order !== 'desc') out.sort_order = filters.sort_order
  return out
}

const statusOptions = computed<SelectOption[]>(() => [
  { value: '', label: 'Tous statuts' },
  ...props.options.statuses,
])
const typeOptions = computed<SelectOption[]>(() => [
  { value: '', label: 'Tous types' },
  ...props.options.types,
])

// Bulk selection
const selected = ref<number[]>([])
const allSelected = computed(
  () =>
    props.episodes.data.length > 0 &&
    props.episodes.data.every((e) => selected.value.includes(e.id)),
)

function toggleAll() {
  if (allSelected.value) {
    selected.value = []
  } else {
    selected.value = props.episodes.data.map((e) => e.id)
  }
}

const bulkAction = ref<string>('')
const bulkProcessing = ref(false)

function runBulk() {
  if (!bulkAction.value || selected.value.length === 0) return
  if (bulkAction.value === 'delete') {
    if (!confirm(`Supprimer ${selected.value.length} épisode(s) ? Cette action est irréversible.`))
      return
  }
  bulkProcessing.value = true
  router.post(
    '/admin/episodes/bulk-action',
    { action: bulkAction.value, episodes: selected.value },
    {
      preserveScroll: true,
      onFinish: () => {
        bulkProcessing.value = false
        selected.value = []
        bulkAction.value = ''
      },
    },
  )
}

function destroy(episode: EpisodeRow) {
  if (!confirm(`Supprimer l'épisode « ${episode.titre} » ?`)) return
  router.delete(`/admin/episodes/${episode.slug}`, { preserveScroll: true })
}

const statusBadge: Record<string, string> = {
  published: 'bg-emerald-100 text-emerald-800',
  draft: 'bg-slate-100 text-slate-700',
  scheduled: 'bg-amber-100 text-amber-800',
  archived: 'bg-slate-200 text-slate-600',
}
useAdminTitle('Épisodes')
</script>

<template>
  <Head title="Épisodes — Admin" />


  <div class="space-y-5">
    <!-- Stats compactes -->
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-lg border border-slate-200 bg-white p-3">
        <p class="text-xs text-slate-500">Total</p>
        <p class="text-2xl font-bold">{{ stats.total }}</p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-white p-3">
        <p class="text-xs text-slate-500">Publiés</p>
        <p class="text-2xl font-bold text-emerald-700">{{ stats.published }}</p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-white p-3">
        <p class="text-xs text-slate-500">Brouillons</p>
        <p class="text-2xl font-bold text-slate-700">{{ stats.draft }}</p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-white p-3">
        <p class="text-xs text-slate-500">Programmés</p>
        <p class="text-2xl font-bold text-amber-700">{{ stats.scheduled }}</p>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3">
      <div class="grid w-full grid-cols-2 gap-2 md:flex md:flex-1 md:items-center md:gap-3">
        <Input v-model="search" placeholder="Rechercher un épisode…" class="md:max-w-md" />
        <Select v-model="filters.status" :options="statusOptions" class="md:w-44" />
        <Select v-model="filters.type" :options="typeOptions" class="md:w-44" />
      </div>
      <div class="flex flex-wrap gap-2">
        <a
          href="/admin/episodes/export"
          class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
          Export CSV
        </a>
        <Link
          href="/admin/episodes/create"
          class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white hover:bg-brand-700"
        >
          + Nouvel épisode
        </Link>
      </div>
    </div>

    <!-- Bulk bar -->
    <div
      v-if="selected.length"
      class="flex flex-wrap items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm"
    >
      <span class="font-semibold">{{ selected.length }} épisode(s) sélectionné(s)</span>
      <Select
        v-model="bulkAction"
        :options="[
          { value: '', label: 'Action…' },
          { value: 'publish', label: 'Publier' },
          { value: 'draft', label: 'Mettre en brouillon' },
          { value: 'archive', label: 'Archiver' },
          { value: 'delete', label: 'Supprimer' },
        ]"
        class="w-48"
      />
      <button
        type="button"
        class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
        :disabled="!bulkAction || bulkProcessing"
        @click="runBulk"
      >
        Appliquer
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700"
        @click="selected = []"
      >
        Annuler
      </button>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-600">
          <tr>
            <th class="w-10 px-4 py-2 text-left">
              <input type="checkbox" :checked="allSelected" @change="toggleAll" />
            </th>
            <th class="px-4 py-2 text-left">Titre</th>
            <th class="px-4 py-2 text-left">Type</th>
            <th class="px-4 py-2 text-left">Statut</th>
            <th class="px-4 py-2 text-left">Date</th>
            <th class="px-4 py-2 text-right">Vues</th>
            <th class="w-32 px-4 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="ep in episodes.data" :key="ep.id" class="hover:bg-slate-50">
            <td class="px-4 py-2">
              <input type="checkbox" :value="ep.id" v-model="selected" />
            </td>
            <td class="px-4 py-2">
              <div class="flex items-center gap-3">
                <div class="h-10 w-16 shrink-0 overflow-hidden rounded bg-slate-100">
                  <img
                    v-if="ep.thumbnail_url"
                    :src="ep.thumbnail_url"
                    :alt="ep.titre"
                    loading="lazy"
                    class="h-full w-full object-cover"
                  />
                </div>
                <div>
                  <p class="font-medium text-slate-900">{{ ep.titre }}</p>
                  <p class="text-xs text-slate-500">{{ ep.slug }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-2 text-slate-700">{{ ep.type_label }}</td>
            <td class="px-4 py-2">
              <span
                :class="['rounded-full px-2 py-0.5 text-xs font-semibold', statusBadge[ep.statut] ?? 'bg-slate-100 text-slate-700']"
              >
                {{ ep.statut_label }}
              </span>
            </td>
            <td class="px-4 py-2 text-slate-600">
              {{ ep.date_publication ? new Date(ep.date_publication).toLocaleDateString('fr-FR') : '—' }}
            </td>
            <td class="px-4 py-2 text-right text-slate-700">{{ ep.vues.toLocaleString('fr-FR') }}</td>
            <td class="px-4 py-2 text-right">
              <Link :href="ep.edit_url" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                Éditer
              </Link>
              <button
                type="button"
                class="ml-3 text-sm font-medium text-red-600 hover:text-red-800"
                @click="destroy(ep)"
              >
                Suppr.
              </button>
            </td>
          </tr>
          <tr v-if="!episodes.data.length">
            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
              Aucun épisode ne correspond aux filtres.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="episodes.last_page > 1" class="flex flex-wrap justify-center gap-2" aria-label="Pagination">
      <Link
        v-for="link in episodes.links"
        :key="link.label + link.url"
        :href="link.url ?? '#'"
        v-html="link.label"
        :class="[
          'rounded-md border px-3 py-1.5 text-sm transition',
          link.active
            ? 'border-brand-600 bg-brand-600 text-white'
            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
          !link.url ? 'pointer-events-none opacity-40' : '',
        ]"
        preserve-scroll
      />
    </nav>
  </div>
</template>
