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

interface AbonneRow {
  id: number
  email: string
  prenom_complet: string
  status: string
  status_label: string
  confirme: boolean
  source_inscription?: string | null
  date_inscription?: string | null
}

interface Paginated {
  data: AbonneRow[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

const props = defineProps<{
  abonnes: Paginated
  filters: { search: string; status: string }
  stats: {
    total: number
    actif: number
    inactif: number
    desabonne: number
    taux_actifs: number
    new_this_month: number
  }
}>()

const filters = reactive({ ...props.filters })
const search = ref(filters.search)
const debouncedSearch = useDebounce(search, 300)

watch(debouncedSearch, (v) => { filters.search = v; push() })
watch(() => filters.status, () => push())

function push() {
  router.get(window.location.pathname, cleanFilters(), {
    preserveScroll: true, preserveState: true, replace: true,
  })
}
function cleanFilters() {
  const out: Record<string, string> = {}
  if (filters.search) out.search = filters.search
  if (filters.status) out.status = filters.status
  return out
}

const statusOptions = computed<SelectOption[]>(() => [
  { value: '', label: 'Tous statuts' },
  { value: 'actif', label: 'Actif' },
  { value: 'inactif', label: 'En attente' },
  { value: 'desabonne', label: 'Désabonné' },
])

const selected = ref<number[]>([])
const allChecked = computed(() =>
  props.abonnes.data.length > 0 &&
  props.abonnes.data.every((a) => selected.value.includes(a.id)),
)
function toggleAll() {
  if (allChecked.value) selected.value = []
  else selected.value = props.abonnes.data.map((a) => a.id)
}
function toggleOne(id: number) {
  const idx = selected.value.indexOf(id)
  if (idx === -1) selected.value.push(id)
  else selected.value.splice(idx, 1)
}
function bulkAction(action: string) {
  if (!selected.value.length) return
  router.post('/admin/newsletter/bulk-action', { action, abonnes: selected.value }, {
    onSuccess: () => { selected.value = [] },
  })
}
function activate(a: AbonneRow) {
  router.post(`/admin/newsletter/${a.id}/activate`, {}, { preserveScroll: true })
}
function deactivate(a: AbonneRow) {
  router.post(`/admin/newsletter/${a.id}/deactivate`, {}, { preserveScroll: true })
}
function unsubscribe(a: AbonneRow) {
  if (!confirm(`Désabonner ${a.email} ?`)) return
  router.delete(`/admin/newsletter/${a.id}`, { preserveScroll: true })
}

const statusColors: Record<string, string> = {
  actif: 'bg-emerald-100 text-emerald-800',
  inactif: 'bg-yellow-100 text-yellow-800',
  desabonne: 'bg-slate-100 text-slate-600',
}
useAdminTitle('Newsletter — Abonnés')
</script>

<template>
  <Head title="Newsletter — Abonnés" />

  <div class="space-y-5">
    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-6">
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-900">{{ stats.total }}</div>
        <div class="text-xs text-slate-500">Total</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-emerald-600">{{ stats.actif }}</div>
        <div class="text-xs text-slate-500">Actifs</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-yellow-600">{{ stats.inactif }}</div>
        <div class="text-xs text-slate-500">En attente</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-500">{{ stats.desabonne }}</div>
        <div class="text-xs text-slate-500">Désabonnés</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-brand-600">{{ stats.taux_actifs }}%</div>
        <div class="text-xs text-slate-500">Taux actifs</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ stats.new_this_month }}</div>
        <div class="text-xs text-slate-500">Ce mois</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-end gap-3">
      <Input v-model="search" placeholder="Email, prénom…" class="w-48" type="search" />
      <Select v-model="filters.status" :options="statusOptions" class="w-44" />
      <div class="ml-auto flex gap-2">
        <a
          href="/admin/newsletter/export"
          class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >Export CSV</a>
      </div>
    </div>

    <!-- Bulk bar -->
    <div v-if="selected.length" class="flex items-center gap-3 rounded-lg bg-brand-50 px-4 py-2 text-sm">
      <span class="font-medium text-brand-700">{{ selected.length }} sélectionné(s)</span>
      <button type="button" class="rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700" @click="bulkAction('activate')">Activer</button>
      <button type="button" class="rounded bg-yellow-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-yellow-600" @click="bulkAction('deactivate')">Désactiver</button>
      <button type="button" class="rounded bg-slate-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-slate-700" @click="bulkAction('delete')">Désabonner</button>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="w-10 py-3 pl-4"><input type="checkbox" :checked="allChecked" @change="toggleAll" /></th>
            <th class="py-3 pl-3">Email</th>
            <th class="py-3">Nom</th>
            <th class="py-3">Statut</th>
            <th class="py-3">Source</th>
            <th class="py-3">Inscription</th>
            <th class="py-3 pr-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="a in abonnes.data" :key="a.id" class="hover:bg-slate-50">
            <td class="py-3 pl-4"><input type="checkbox" :checked="selected.includes(a.id)" @change="toggleOne(a.id)" /></td>
            <td class="py-3 pl-3 font-mono text-xs text-slate-800">{{ a.email }}</td>
            <td class="py-3 text-slate-600">{{ a.prenom_complet }}</td>
            <td class="py-3">
              <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusColors[a.status] ?? 'bg-slate-100 text-slate-700'">
                {{ a.status_label }}
              </span>
            </td>
            <td class="py-3 text-xs text-slate-500">{{ a.source_inscription ?? '—' }}</td>
            <td class="py-3 text-slate-500 text-xs">
              {{ a.date_inscription ? new Date(a.date_inscription).toLocaleDateString('fr-FR') : '—' }}
            </td>
            <td class="py-3 pr-4 text-right">
              <div class="flex justify-end gap-1">
                <button
                  v-if="a.status !== 'actif'"
                  type="button"
                  class="rounded px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50"
                  @click="activate(a)"
                >Activer</button>
                <button
                  v-else
                  type="button"
                  class="rounded px-2 py-1 text-xs font-medium text-yellow-600 hover:bg-yellow-50"
                  @click="deactivate(a)"
                >Désactiver</button>
                <button
                  v-if="a.status !== 'desabonne'"
                  type="button"
                  class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                  @click="unsubscribe(a)"
                >Désabonner</button>
              </div>
            </td>
          </tr>
          <tr v-if="!abonnes.data.length">
            <td colspan="7" class="py-12 text-center text-slate-400">Aucun abonné trouvé.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="abonnes.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>Page {{ abonnes.current_page }} / {{ abonnes.last_page }} — {{ abonnes.total }} résultat(s)</span>
      <div class="flex gap-1">
        <template v-for="link in abonnes.links" :key="link.label">
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
