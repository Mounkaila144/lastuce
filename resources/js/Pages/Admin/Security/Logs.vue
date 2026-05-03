<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import type { SelectOption } from '@/components/ui/Select.vue'

defineOptions({ layout: AdminLayout })

interface LogRow {
  id: number
  action: string
  description?: string | null
  severity: string
  user_name: string
  ip_address?: string | null
  created_at: string
}

interface Paginated {
  data: LogRow[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

const props = defineProps<{
  logs: Paginated
  filters: { user_id: string; action: string; severity: string; date_from: string; date_to: string }
  stats: { total: number; today: number; critical: number; errors: number }
  options: { users: SelectOption[]; actions: string[]; severities: string[] }
}>()

const filters = reactive({ ...props.filters })
watch(filters, () => push())

function push() {
  const out: Record<string, string> = {}
  if (filters.user_id) out.user_id = filters.user_id
  if (filters.action) out.action = filters.action
  if (filters.severity) out.severity = filters.severity
  if (filters.date_from) out.date_from = filters.date_from
  if (filters.date_to) out.date_to = filters.date_to
  router.get(window.location.pathname, out, { preserveScroll: true, preserveState: true, replace: true })
}

const showCleanup = ref(false)
const cleanupDays = ref(90)

function cleanup() {
  router.post('/admin/security/logs/cleanup', { days: cleanupDays.value }, {
    onSuccess: () => { showCleanup.value = false },
  })
}

function deleteLog(id: number) {
  if (!confirm('Supprimer ce log ?')) return
  router.delete(`/admin/security/logs/${id}`, { preserveScroll: true })
}

const severityColors: Record<string, string> = {
  info: 'bg-blue-50 text-blue-700',
  warning: 'bg-yellow-50 text-yellow-800',
  error: 'bg-red-50 text-red-700',
  critical: 'bg-red-100 text-red-900 font-semibold',
}

const severityOptions = [
  { value: '', label: 'Toutes sévérités' },
  ...props.options.severities.map((s) => ({ value: s, label: s })),
]
const actionOptions = [
  { value: '', label: 'Toutes actions' },
  ...props.options.actions.map((a) => ({ value: a, label: a })),
]
const userOptions = [
  { value: '', label: 'Tous utilisateurs' },
  ...props.options.users,
]
useAdminTitle("Logs d'administration")
</script>

<template>
  <Head title="Logs d'administration" />

  <div class="space-y-5">
    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-900">{{ stats.total }}</div>
        <div class="text-xs text-slate-500">Total</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-brand-600">{{ stats.today }}</div>
        <div class="text-xs text-slate-500">Aujourd'hui</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-yellow-600">{{ stats.errors }}</div>
        <div class="text-xs text-slate-500">Erreurs</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-red-600">{{ stats.critical }}</div>
        <div class="text-xs text-slate-500">Critiques</div>
      </div>
    </div>

    <!-- Filtres + actions -->
    <div class="flex flex-wrap items-end gap-3">
      <Select v-model="filters.user_id" :options="userOptions" class="w-44" />
      <Select v-model="filters.severity" :options="severityOptions" class="w-40" />
      <Select v-model="filters.action" :options="actionOptions" class="w-44" />
      <Input v-model="filters.date_from" type="date" label="Du" class="w-36" />
      <Input v-model="filters.date_to" type="date" label="Au" class="w-36" />
      <div class="ml-auto flex gap-2">
        <button
          type="button"
          class="inline-flex h-9 items-center rounded-md border border-red-200 bg-white px-3 text-sm font-medium text-red-600 hover:bg-red-50"
          @click="showCleanup = true"
        >Nettoyer</button>
      </div>
    </div>

    <!-- Cleanup modal -->
    <div v-if="showCleanup" class="rounded-xl border border-orange-200 bg-orange-50 p-4 flex items-center gap-4">
      <span class="text-sm text-orange-800">Supprimer les logs de plus de</span>
      <input v-model.number="cleanupDays" type="number" min="7" max="365" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm" />
      <span class="text-sm text-orange-800">jours.</span>
      <button type="button" class="rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700" @click="cleanup">Confirmer</button>
      <button type="button" class="text-xs text-slate-500 hover:underline" @click="showCleanup = false">Annuler</button>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="py-3 pl-4">Sévérité</th>
            <th class="py-3">Action</th>
            <th class="py-3">Description</th>
            <th class="py-3">Utilisateur</th>
            <th class="py-3">IP</th>
            <th class="py-3">Date</th>
            <th class="py-3 pr-4 text-right">—</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50">
            <td class="py-2.5 pl-4">
              <span class="rounded px-2 py-0.5 text-xs" :class="severityColors[log.severity] ?? 'bg-slate-100 text-slate-700'">
                {{ log.severity }}
              </span>
            </td>
            <td class="py-2.5 font-mono text-xs text-slate-700">{{ log.action }}</td>
            <td class="py-2.5 max-w-xs text-xs text-slate-600 truncate">{{ log.description }}</td>
            <td class="py-2.5 text-xs text-slate-600">{{ log.user_name }}</td>
            <td class="py-2.5 font-mono text-xs text-slate-400">{{ log.ip_address ?? '—' }}</td>
            <td class="py-2.5 text-xs text-slate-500">{{ new Date(log.created_at).toLocaleString('fr-FR') }}</td>
            <td class="py-2.5 pr-4 text-right">
              <button type="button" class="text-xs text-red-500 hover:underline" @click="deleteLog(log.id)">Suppr.</button>
            </td>
          </tr>
          <tr v-if="!logs.data.length">
            <td colspan="7" class="py-12 text-center text-slate-400">Aucun log trouvé.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="logs.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>Page {{ logs.current_page }} / {{ logs.last_page }} — {{ logs.total }} résultat(s)</span>
      <div class="flex gap-1">
        <template v-for="link in logs.links" :key="link.label">
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
