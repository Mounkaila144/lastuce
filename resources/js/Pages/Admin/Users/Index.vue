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

interface UserRow {
  id: number
  name: string
  email: string
  role: string
  is_admin: boolean
  is_locked: boolean
  locked_until?: string | null
  created_at: string
  edit_url: string
}

interface Paginated {
  data: UserRow[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

const props = defineProps<{
  users: Paginated
  filters: { search: string; role: string; status: string }
  stats: { total: number; admins: number; locked: number; recent: number }
  options: { roles: SelectOption[]; permissions: SelectOption[] }
}>()

const filters = reactive({ ...props.filters })
const search = ref(filters.search)
const debouncedSearch = useDebounce(search, 300)

watch(debouncedSearch, (v) => { filters.search = v; push() })
watch(() => [filters.role, filters.status], () => push())

function push() {
  router.get(window.location.pathname, cleanFilters(), {
    preserveScroll: true, preserveState: true, replace: true,
  })
}
function cleanFilters() {
  const out: Record<string, string> = {}
  if (filters.search) out.search = filters.search
  if (filters.role) out.role = filters.role
  if (filters.status) out.status = filters.status
  return out
}

const roleOptions = computed<SelectOption[]>(() => [
  { value: '', label: 'Tous rôles' },
  ...props.options.roles,
])
const statusOptions: SelectOption[] = [
  { value: '', label: 'Tous statuts' },
  { value: 'active', label: 'Actif' },
  { value: 'locked', label: 'Verrouillé' },
]

const selected = ref<number[]>([])
const allChecked = computed(() =>
  props.users.data.length > 0 && props.users.data.every((u) => selected.value.includes(u.id)),
)
function toggleAll() {
  if (allChecked.value) selected.value = []
  else selected.value = props.users.data.map((u) => u.id)
}
function toggleOne(id: number) {
  const idx = selected.value.indexOf(id)
  if (idx === -1) selected.value.push(id)
  else selected.value.splice(idx, 1)
}
function bulkAction(action: string) {
  if (!selected.value.length) return
  if (action === 'delete' && !confirm(`Supprimer ${selected.value.length} utilisateur(s) ?`)) return
  router.post('/admin/users/bulk-action', { action, users: selected.value }, {
    onSuccess: () => { selected.value = [] },
  })
}

function lockUser(u: UserRow) {
  router.post(`/admin/users/${u.id}/lock`, {}, { preserveScroll: true })
}
function unlockUser(u: UserRow) {
  router.post(`/admin/users/${u.id}/unlock`, {}, { preserveScroll: true })
}
function destroyUser(u: UserRow) {
  if (!confirm(`Supprimer « ${u.name} » ?`)) return
  router.delete(`/admin/users/${u.id}`)
}
useAdminTitle('Gestion des admins')
</script>

<template>
  <Head title="Gestion des admins" />

  <div class="space-y-5">
    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-900">{{ stats.total }}</div>
        <div class="text-xs text-slate-500">Total</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-brand-600">{{ stats.admins }}</div>
        <div class="text-xs text-slate-500">Admins</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-red-600">{{ stats.locked }}</div>
        <div class="text-xs text-slate-500">Verrouillés</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-emerald-600">{{ stats.recent }}</div>
        <div class="text-xs text-slate-500">Cette semaine</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-end gap-3">
      <Input v-model="search" placeholder="Nom, email…" class="w-48" type="search" />
      <Select v-model="filters.role" :options="roleOptions" class="w-40" />
      <Select v-model="filters.status" :options="statusOptions" class="w-40" />
      <div class="ml-auto">
        <Link href="/admin/users/create" class="inline-flex h-9 items-center rounded-md bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">
          Nouvel admin
        </Link>
      </div>
    </div>

    <!-- Bulk bar -->
    <div v-if="selected.length" class="flex items-center gap-3 rounded-lg bg-brand-50 px-4 py-2 text-sm">
      <span class="font-medium text-brand-700">{{ selected.length }} sélectionné(s)</span>
      <button type="button" class="rounded bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-700" @click="bulkAction('lock')">Verrouiller</button>
      <button type="button" class="rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700" @click="bulkAction('unlock')">Déverrouiller</button>
      <button type="button" class="rounded bg-slate-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-slate-700" @click="bulkAction('delete')">Supprimer</button>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="w-10 py-3 pl-4"><input type="checkbox" :checked="allChecked" @change="toggleAll" /></th>
            <th class="py-3 pl-3">Nom</th>
            <th class="py-3">Rôle</th>
            <th class="py-3">Statut</th>
            <th class="py-3">Créé le</th>
            <th class="py-3 pr-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50">
            <td class="py-3 pl-4"><input type="checkbox" :checked="selected.includes(u.id)" @change="toggleOne(u.id)" /></td>
            <td class="py-3 pl-3">
              <div class="font-medium text-slate-900">{{ u.name }}</div>
              <div class="text-xs text-slate-400">{{ u.email }}</div>
            </td>
            <td class="py-3">
              <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="u.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700'">
                {{ u.role }}
              </span>
            </td>
            <td class="py-3">
              <span v-if="u.is_locked" class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">Verrouillé</span>
              <span v-else class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Actif</span>
            </td>
            <td class="py-3 text-slate-500 text-xs">{{ new Date(u.created_at).toLocaleDateString('fr-FR') }}</td>
            <td class="py-3 pr-4 text-right">
              <div class="flex justify-end gap-1">
                <Link :href="u.edit_url" class="rounded px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50">Éditer</Link>
                <button v-if="!u.is_locked" type="button" class="rounded px-2 py-1 text-xs font-medium text-yellow-600 hover:bg-yellow-50" @click="lockUser(u)">Verrouiller</button>
                <button v-else type="button" class="rounded px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50" @click="unlockUser(u)">Déverrouiller</button>
                <button type="button" class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="destroyUser(u)">Suppr.</button>
              </div>
            </td>
          </tr>
          <tr v-if="!users.data.length">
            <td colspan="6" class="py-12 text-center text-slate-400">Aucun utilisateur trouvé.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="users.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>Page {{ users.current_page }} / {{ users.last_page }} — {{ users.total }} résultat(s)</span>
      <div class="flex gap-1">
        <template v-for="link in users.links" :key="link.label">
          <component :is="link.url ? Link : 'span'" :href="link.url ?? undefined"
            class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded border px-2 text-xs"
            :class="link.active ? 'border-brand-600 bg-brand-600 font-semibold text-white' : link.url ? 'border-slate-200 bg-white hover:bg-slate-50' : 'cursor-default border-slate-100 bg-white text-slate-300'"
            v-html="link.label"
          />
        </template>
      </div>
    </div>
  </div>
</template>
