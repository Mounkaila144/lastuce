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

interface PartenaRow {
  id: number
  nom_entreprise: string
  contact: string
  email: string
  type_partenariat?: string | null
  budget_envisage?: string | null
  status: string
  created_at: string
  show_url: string
  edit_url: string
}

interface Paginated {
  data: PartenaRow[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

const props = defineProps<{
  partenariats: Paginated
  filters: { search: string; status: string; sort_by: string; sort_order: string }
  stats: { total: number; nouveau: number; en_cours: number; accepte: number; refuse: number }
}>()

const filters = reactive({ ...props.filters })
const search = ref(filters.search)
const debouncedSearch = useDebounce(search, 300)

watch(debouncedSearch, (v) => { filters.search = v; push() })
watch(() => [filters.status, filters.sort_by, filters.sort_order], () => push())

function push() {
  router.get(window.location.pathname, cleanFilters(), {
    preserveScroll: true, preserveState: true, replace: true,
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
  { value: 'nouveau', label: 'Nouveau' },
  { value: 'en_cours', label: 'En cours' },
  { value: 'accepte', label: 'Accepté' },
  { value: 'refuse', label: 'Refusé' },
])

const selected = ref<number[]>([])
const allChecked = computed(() =>
  props.partenariats.data.length > 0 &&
  props.partenariats.data.every((p) => selected.value.includes(p.id)),
)
function toggleAll() {
  if (allChecked.value) selected.value = []
  else selected.value = props.partenariats.data.map((p) => p.id)
}
function toggleOne(id: number) {
  const idx = selected.value.indexOf(id)
  if (idx === -1) selected.value.push(id)
  else selected.value.splice(idx, 1)
}
function bulkAction(action: string) {
  if (!selected.value.length) return
  if (action === 'delete' && !confirm(`Supprimer ${selected.value.length} partenariat(s) ?`)) return
  router.post('/admin/partenariats/bulk-action', { action, partenariats: selected.value }, {
    onSuccess: () => { selected.value = [] },
  })
}
function destroy(p: PartenaRow) {
  if (!confirm(`Supprimer le partenariat « ${p.nom_entreprise} » ?`)) return
  router.delete(`/admin/partenariats/${p.id}`)
}

const statusColors: Record<string, string> = {
  nouveau: 'bg-blue-100 text-blue-800',
  en_cours: 'bg-yellow-100 text-yellow-800',
  accepte: 'bg-emerald-100 text-emerald-800',
  refuse: 'bg-red-100 text-red-800',
}
const statusLabels: Record<string, string> = {
  nouveau: 'Nouveau',
  en_cours: 'En cours',
  accepte: 'Accepté',
  refuse: 'Refusé',
}
useAdminTitle('Partenariats')
</script>

<template>
  <Head title="Partenariats" />

  <div class="space-y-5">
    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-900">{{ stats.total }}</div>
        <div class="text-xs text-slate-500">Total</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ stats.nouveau }}</div>
        <div class="text-xs text-slate-500">Nouveaux</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-yellow-600">{{ stats.en_cours }}</div>
        <div class="text-xs text-slate-500">En cours</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-emerald-600">{{ stats.accepte }}</div>
        <div class="text-xs text-slate-500">Acceptés</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-red-600">{{ stats.refuse }}</div>
        <div class="text-xs text-slate-500">Refusés</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-end gap-3">
      <Input v-model="search" placeholder="Rechercher…" class="w-48" type="search" />
      <Select v-model="filters.status" :options="statusOptions" class="w-40" />
      <div class="ml-auto flex gap-2">
        <a
          href="/admin/partenariats/export"
          class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >Export CSV</a>
      </div>
    </div>

    <!-- Bulk bar -->
    <div v-if="selected.length" class="flex items-center gap-3 rounded-lg bg-brand-50 px-4 py-2 text-sm">
      <span class="font-medium text-brand-700">{{ selected.length }} sélectionné(s)</span>
      <button type="button" class="rounded bg-yellow-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-yellow-700" @click="bulkAction('en_cours')">En cours</button>
      <button type="button" class="rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700" @click="bulkAction('approve')">Accepter</button>
      <button type="button" class="rounded bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-700" @click="bulkAction('reject')">Refuser</button>
      <button type="button" class="rounded bg-slate-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-slate-700" @click="bulkAction('delete')">Supprimer</button>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="w-10 py-3 pl-4"><input type="checkbox" :checked="allChecked" @change="toggleAll" /></th>
            <th class="py-3 pl-3">Entreprise</th>
            <th class="py-3">Contact</th>
            <th class="py-3">Type</th>
            <th class="py-3">Statut</th>
            <th class="py-3">Date</th>
            <th class="py-3 pr-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="p in partenariats.data" :key="p.id" class="hover:bg-slate-50">
            <td class="py-3 pl-4"><input type="checkbox" :checked="selected.includes(p.id)" @change="toggleOne(p.id)" /></td>
            <td class="py-3 pl-3">
              <Link :href="p.show_url" class="font-medium text-slate-900 hover:text-brand-600">{{ p.nom_entreprise }}</Link>
            </td>
            <td class="py-3">
              <div class="text-slate-700">{{ p.contact }}</div>
              <div class="text-xs text-slate-400">{{ p.email }}</div>
            </td>
            <td class="py-3 text-slate-600">{{ p.type_partenariat ?? '—' }}</td>
            <td class="py-3">
              <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusColors[p.status] ?? 'bg-slate-100 text-slate-700'">
                {{ statusLabels[p.status] ?? p.status }}
              </span>
            </td>
            <td class="py-3 text-slate-500">{{ new Date(p.created_at).toLocaleDateString('fr-FR') }}</td>
            <td class="py-3 pr-4 text-right">
              <div class="flex justify-end gap-1">
                <Link :href="p.edit_url" class="rounded px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50">Éditer</Link>
                <button type="button" class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="destroy(p)">Suppr.</button>
              </div>
            </td>
          </tr>
          <tr v-if="!partenariats.data.length">
            <td colspan="7" class="py-12 text-center text-slate-400">Aucun partenariat trouvé.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="partenariats.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>Page {{ partenariats.current_page }} / {{ partenariats.last_page }} — {{ partenariats.total }} résultat(s)</span>
      <div class="flex gap-1">
        <template v-for="link in partenariats.links" :key="link.label">
          <component
            :is="link.url ? Link : 'span'"
            :href="link.url ?? undefined"
            class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded border px-2 text-xs"
            :class="link.active ? 'border-brand-600 bg-brand-600 font-semibold text-white' : link.url ? 'border-slate-200 bg-white hover:bg-slate-50' : 'cursor-default border-slate-100 bg-white text-slate-300'"
            v-html="link.label"
          />
        </template>
      </div>
    </div>
  </div>
</template>
