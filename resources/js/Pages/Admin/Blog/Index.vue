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

interface ArticleRow {
  id: number
  titre: string
  slug: string
  statut: string
  statut_color: string
  categorie?: string | null
  date_publication?: string | null
  vues: number
  reading_time: number
  featured_url?: string | null
  edit_url: string
  show_url: string
}

interface Filters {
  search: string
  status: string
  sort_by: string
  sort_order: string
}

interface Paginated {
  data: ArticleRow[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

const props = defineProps<{
  articles: Paginated
  filters: Filters
  stats: { total: number; published: number; draft: number; scheduled: number }
}>()

const filters = reactive<Filters>({ ...props.filters })
const search = ref(filters.search)
const debouncedSearch = useDebounce(search, 300)

watch(debouncedSearch, (v) => { filters.search = v; push() })
watch(() => [filters.status, filters.sort_by, filters.sort_order], () => push())

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
  if (filters.sort_by !== 'created_at') out.sort_by = filters.sort_by
  if (filters.sort_order !== 'desc') out.sort_order = filters.sort_order
  return out
}

const statusOptions = computed<SelectOption[]>(() => [
  { value: '', label: 'Tous statuts' },
  { value: 'published', label: 'Publié' },
  { value: 'scheduled', label: 'Programmé' },
  { value: 'draft', label: 'Brouillon' },
])

const sortOptions = computed<SelectOption[]>(() => [
  { value: 'created_at', label: 'Date de création' },
  { value: 'date_publication', label: 'Date de publication' },
  { value: 'titre', label: 'Titre' },
  { value: 'vues', label: 'Vues' },
])

/* ---- Bulk actions ---- */
const selected = ref<number[]>([])
const allChecked = computed(() =>
  props.articles.data.length > 0 && props.articles.data.every((a) => selected.value.includes(a.id)),
)

function toggleAll() {
  if (allChecked.value) selected.value = []
  else selected.value = props.articles.data.map((a) => a.id)
}

function toggleOne(id: number) {
  const idx = selected.value.indexOf(id)
  if (idx === -1) selected.value.push(id)
  else selected.value.splice(idx, 1)
}

function bulkAction(action: string) {
  if (!selected.value.length) return
  if (action === 'delete' && !confirm(`Supprimer ${selected.value.length} article(s) ?`)) return
  router.post('/admin/blog/bulk-action', { action, articles: selected.value }, {
    onSuccess: () => { selected.value = [] },
  })
}

function destroy(article: ArticleRow) {
  if (!confirm(`Supprimer l'article « ${article.titre} » ?`)) return
  router.delete(`/admin/blog/${article.id}`)
}

const statusColorClass: Record<string, string> = {
  green: 'bg-emerald-100 text-emerald-800',
  yellow: 'bg-yellow-100 text-yellow-800',
  gray: 'bg-slate-100 text-slate-700',
}
useAdminTitle('Articles — Blog')
</script>

<template>
  <Head title="Articles — Blog" />


  <div class="space-y-5">
    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-900">{{ stats.total }}</div>
        <div class="text-xs text-slate-500">Total</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-emerald-600">{{ stats.published }}</div>
        <div class="text-xs text-slate-500">Publiés</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-yellow-600">{{ stats.scheduled }}</div>
        <div class="text-xs text-slate-500">Programmés</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-500">{{ stats.draft }}</div>
        <div class="text-xs text-slate-500">Brouillons</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-end gap-3">
      <Input
        v-model="search"
        placeholder="Rechercher…"
        class="w-48"
        type="search"
      />
      <Select v-model="filters.status" :options="statusOptions" class="w-40" />
      <Select v-model="filters.sort_by" :options="sortOptions" class="w-44" />
      <button
        type="button"
        class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-600 hover:bg-slate-50"
        @click="filters.sort_order = filters.sort_order === 'asc' ? 'desc' : 'asc'"
      >
        {{ filters.sort_order === 'asc' ? '↑ Croissant' : '↓ Décroissant' }}
      </button>
      <div class="ml-auto flex items-center gap-2">
        <a
          href="/admin/blog/export"
          class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Export CSV
        </a>
        <Link
          href="/admin/blog/create"
          class="inline-flex h-9 items-center rounded-md bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700"
        >
          Nouvel article
        </Link>
      </div>
    </div>

    <!-- Bulk bar -->
    <div v-if="selected.length" class="flex items-center gap-3 rounded-lg bg-brand-50 px-4 py-2 text-sm">
      <span class="font-medium text-brand-700">{{ selected.length }} sélectionné(s)</span>
      <button
        type="button"
        class="rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
        @click="bulkAction('publish')"
      >Publier</button>
      <button
        type="button"
        class="rounded bg-slate-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-slate-700"
        @click="bulkAction('draft')"
      >Brouillon</button>
      <button
        type="button"
        class="rounded bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-700"
        @click="bulkAction('delete')"
      >Supprimer</button>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="w-10 py-3 pl-4">
              <input type="checkbox" :checked="allChecked" @change="toggleAll" />
            </th>
            <th class="py-3 pl-2">Image</th>
            <th class="py-3 pl-3">Titre</th>
            <th class="py-3">Catégorie</th>
            <th class="py-3">Statut</th>
            <th class="py-3">Publication</th>
            <th class="py-3">Vues</th>
            <th class="py-3 pr-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="article in articles.data"
            :key="article.id"
            class="hover:bg-slate-50"
          >
            <td class="py-3 pl-4">
              <input
                type="checkbox"
                :checked="selected.includes(article.id)"
                @change="toggleOne(article.id)"
              />
            </td>
            <td class="py-3 pl-2">
              <img
                v-if="article.featured_url"
                :src="article.featured_url"
                :alt="article.titre"
                class="h-10 w-16 rounded object-cover"
              />
              <div v-else class="h-10 w-16 rounded bg-slate-100" />
            </td>
            <td class="max-w-xs py-3 pl-3">
              <Link :href="article.show_url" class="font-medium text-slate-900 hover:text-brand-600 line-clamp-2">
                {{ article.titre }}
              </Link>
              <span class="text-xs text-slate-400">{{ article.slug }}</span>
            </td>
            <td class="py-3 text-slate-600">{{ article.categorie ?? '—' }}</td>
            <td class="py-3">
              <span
                class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="statusColorClass[article.statut_color] ?? statusColorClass.gray"
              >
                {{ article.statut }}
              </span>
            </td>
            <td class="py-3 text-slate-500">
              {{ article.date_publication
                ? new Date(article.date_publication).toLocaleDateString('fr-FR')
                : '—' }}
            </td>
            <td class="py-3 text-slate-600">{{ article.vues.toLocaleString('fr-FR') }}</td>
            <td class="py-3 pr-4 text-right">
              <div class="flex justify-end gap-1">
                <Link
                  :href="article.edit_url"
                  class="rounded px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50"
                >Éditer</Link>
                <button
                  type="button"
                  class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                  @click="destroy(article)"
                >Suppr.</button>
              </div>
            </td>
          </tr>
          <tr v-if="!articles.data.length">
            <td colspan="8" class="py-12 text-center text-slate-400">
              Aucun article trouvé.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="articles.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>
        Page {{ articles.current_page }} / {{ articles.last_page }}
        — {{ articles.total }} résultat(s)
      </span>
      <div class="flex gap-1">
        <template v-for="link in articles.links" :key="link.label">
          <component
            :is="link.url ? Link : 'span'"
            :href="link.url ?? undefined"
            class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded border px-2 text-xs"
            :class="link.active
              ? 'border-brand-600 bg-brand-600 font-semibold text-white'
              : link.url
                ? 'border-slate-200 bg-white hover:bg-slate-50'
                : 'cursor-default border-slate-100 bg-white text-slate-300'"
            v-html="link.label"
          />
        </template>
      </div>
    </div>
  </div>
</template>
