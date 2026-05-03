<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Modal from '@/components/ui/Modal.vue'
import { useDebounce } from '@/composables/useDebounce'
import type { SelectOption } from '@/components/ui/Select.vue'

defineOptions({ layout: AdminLayout })

interface AstuceRow {
  id: number
  titre_astuce: string
  nom: string
  email: string
  categorie?: string | null
  difficulte?: string | null
  status: string
  status_label: string
  created_at?: string | null
  extrait?: string | null
  show_url: string
}

interface Filters {
  search: string
  status: string
  difficulte: string
  sort_by: string
  sort_order: string
}

interface Paginated {
  data: AstuceRow[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

const props = defineProps<{
  astuces: Paginated
  filters: Filters
  options: { statuses: SelectOption[]; difficultes: SelectOption[] }
  stats: { total: number; en_attente: number; approuve: number; rejete: number }
}>()

const filters = reactive<Filters>({ ...props.filters })
const search = ref(filters.search)
const debouncedSearch = useDebounce(search, 300)

watch(debouncedSearch, (v) => {
  filters.search = v
  push()
})
watch(() => [filters.status, filters.difficulte], () => push())

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
  if (filters.difficulte) out.difficulte = filters.difficulte
  return out
}

const statusOptions = computed<SelectOption[]>(() => [
  { value: '', label: 'Tous statuts' },
  ...props.options.statuses,
])
const difficulteOptions = computed<SelectOption[]>(() => [
  { value: '', label: 'Toutes difficultés' },
  ...props.options.difficultes,
])

const selected = ref<number[]>([])
const allSelected = computed(
  () =>
    props.astuces.data.length > 0 &&
    props.astuces.data.every((a) => selected.value.includes(a.id)),
)
function toggleAll() {
  selected.value = allSelected.value ? [] : props.astuces.data.map((a) => a.id)
}

const bulkAction = ref<string>('')
const bulkComment = ref('')
const bulkProcessing = ref(false)
const showRejectModal = ref(false)

function runBulk() {
  if (!bulkAction.value || selected.value.length === 0) return
  if (bulkAction.value === 'reject' && !showRejectModal.value) {
    showRejectModal.value = true
    return
  }
  if (bulkAction.value === 'delete') {
    if (!confirm(`Supprimer ${selected.value.length} astuce(s) ?`)) return
  }
  bulkProcessing.value = true
  router.post(
    '/admin/astuces/bulk-action',
    {
      action: bulkAction.value,
      astuces: selected.value,
      commentaire_admin: bulkComment.value || null,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        bulkProcessing.value = false
        selected.value = []
        bulkAction.value = ''
        bulkComment.value = ''
        showRejectModal.value = false
      },
    },
  )
}

const statusBadge: Record<string, string> = {
  en_attente: 'bg-amber-100 text-amber-800',
  approuve: 'bg-emerald-100 text-emerald-800',
  rejete: 'bg-red-100 text-red-800',
}
useAdminTitle('Astuces communautaires')
</script>

<template>
  <Head title="Astuces — Modération" />


  <div class="space-y-5">
    <!-- Stats -->
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <button
        type="button"
        class="rounded-lg border border-slate-200 bg-white p-3 text-left transition hover:border-slate-300"
        @click="filters.status = ''"
      >
        <p class="text-xs text-slate-500">Total</p>
        <p class="text-2xl font-bold">{{ stats.total }}</p>
      </button>
      <button
        type="button"
        class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-left transition hover:border-amber-300"
        @click="filters.status = 'en_attente'"
      >
        <p class="text-xs text-amber-700">En attente</p>
        <p class="text-2xl font-bold text-amber-800">{{ stats.en_attente }}</p>
      </button>
      <button
        type="button"
        class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-left transition hover:border-emerald-300"
        @click="filters.status = 'approuve'"
      >
        <p class="text-xs text-emerald-700">Approuvées</p>
        <p class="text-2xl font-bold text-emerald-800">{{ stats.approuve }}</p>
      </button>
      <button
        type="button"
        class="rounded-lg border border-red-200 bg-red-50 p-3 text-left transition hover:border-red-300"
        @click="filters.status = 'rejete'"
      >
        <p class="text-xs text-red-700">Rejetées</p>
        <p class="text-2xl font-bold text-red-800">{{ stats.rejete }}</p>
      </button>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3">
      <div class="grid w-full grid-cols-2 gap-2 md:flex md:flex-1 md:items-center md:gap-3">
        <Input v-model="search" placeholder="Rechercher (titre, auteur, email)…" class="md:max-w-md" />
        <Select v-model="filters.status" :options="statusOptions" class="md:w-44" />
        <Select v-model="filters.difficulte" :options="difficulteOptions" class="md:w-44" />
      </div>
      <a
        href="/admin/astuces/export"
        class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
      >
        Export CSV
      </a>
    </div>

    <!-- Bulk -->
    <div
      v-if="selected.length"
      class="flex flex-wrap items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm"
    >
      <span class="font-semibold">{{ selected.length }} astuce(s) sélectionnée(s)</span>
      <Select
        v-model="bulkAction"
        :options="[
          { value: '', label: 'Action…' },
          { value: 'approve', label: 'Approuver' },
          { value: 'reject', label: 'Rejeter (commentaire requis)' },
          { value: 'delete', label: 'Supprimer' },
        ]"
        class="w-60"
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

    <Modal
      v-model:open="showRejectModal"
      title="Rejeter les astuces sélectionnées"
      description="Le commentaire sera visible par les soumettants sur la page de suivi."
    >
      <Textarea
        v-model="bulkComment"
        label="Commentaire de rejet"
        rows="4"
        placeholder="Indiquer la raison du rejet…"
      />
      <template #footer>
        <button
          type="button"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700"
          @click="showRejectModal = false; bulkAction = ''"
        >
          Annuler
        </button>
        <button
          type="button"
          class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
          :disabled="bulkComment.trim().length < 10 || bulkProcessing"
          @click="runBulk"
        >
          Rejeter {{ selected.length }} astuce(s)
        </button>
      </template>
    </Modal>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-600">
          <tr>
            <th class="w-10 px-4 py-2 text-left">
              <input type="checkbox" :checked="allSelected" @change="toggleAll" />
            </th>
            <th class="px-4 py-2 text-left">Astuce</th>
            <th class="px-4 py-2 text-left">Auteur</th>
            <th class="px-4 py-2 text-left">Catégorie</th>
            <th class="px-4 py-2 text-left">Difficulté</th>
            <th class="px-4 py-2 text-left">Statut</th>
            <th class="px-4 py-2 text-left">Date</th>
            <th class="w-24 px-4 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="a in astuces.data" :key="a.id" class="hover:bg-slate-50">
            <td class="px-4 py-2">
              <input type="checkbox" :value="a.id" v-model="selected" />
            </td>
            <td class="px-4 py-2">
              <p class="font-medium text-slate-900">{{ a.titre_astuce }}</p>
              <p class="line-clamp-2 text-xs text-slate-500">{{ a.extrait }}</p>
            </td>
            <td class="px-4 py-2">
              <p class="text-slate-700">{{ a.nom }}</p>
              <p class="text-xs text-slate-500">{{ a.email }}</p>
            </td>
            <td class="px-4 py-2 text-slate-700">{{ a.categorie ?? '—' }}</td>
            <td class="px-4 py-2 text-slate-700 capitalize">{{ a.difficulte ?? '—' }}</td>
            <td class="px-4 py-2">
              <span
                :class="['rounded-full px-2 py-0.5 text-xs font-semibold', statusBadge[a.status] ?? 'bg-slate-100 text-slate-700']"
              >
                {{ a.status_label }}
              </span>
            </td>
            <td class="px-4 py-2 text-slate-600">
              {{ a.created_at ? new Date(a.created_at).toLocaleDateString('fr-FR') : '—' }}
            </td>
            <td class="px-4 py-2 text-right">
              <Link :href="a.show_url" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                Détail
              </Link>
            </td>
          </tr>
          <tr v-if="!astuces.data.length">
            <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">
              Aucune astuce ne correspond aux filtres.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="astuces.last_page > 1" class="flex flex-wrap justify-center gap-2" aria-label="Pagination">
      <Link
        v-for="link in astuces.links"
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
