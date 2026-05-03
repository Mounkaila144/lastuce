<script setup lang="ts">
import { reactive, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'

defineOptions({ layout: AdminLayout })

interface AttemptRow {
  id: number
  email?: string | null
  ip_address?: string | null
  type?: string | null
  attempted_at?: string | null
}

interface Paginated {
  data: AttemptRow[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

const props = defineProps<{
  attempts: Paginated
  filters: { ip: string; email: string }
  stats: { total: number; today: number; last_hour: number }
}>()

const filters = reactive({ ...props.filters })
watch(filters, () => {
  const out: Record<string, string> = {}
  if (filters.ip) out.ip = filters.ip
  if (filters.email) out.email = filters.email
  router.get(window.location.pathname, out, { preserveScroll: true, preserveState: true, replace: true })
})
useAdminTitle('Tentatives de connexion échouées')
</script>

<template>
  <Head title="Tentatives de connexion échouées" />

  <div class="space-y-5">
    <div class="grid grid-cols-3 gap-3">
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-900">{{ stats.total }}</div>
        <div class="text-xs text-slate-500">Total</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-yellow-600">{{ stats.today }}</div>
        <div class="text-xs text-slate-500">Aujourd'hui</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-red-600">{{ stats.last_hour }}</div>
        <div class="text-xs text-slate-500">Dernière heure</div>
      </div>
    </div>

    <div class="flex flex-wrap gap-3">
      <Input v-model="filters.ip" placeholder="Filtrer par IP…" class="w-44" type="search" />
      <Input v-model="filters.email" placeholder="Filtrer par email…" class="w-48" type="search" />
      <Link
        href="/admin/security/blocked-ips"
        class="ml-auto inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
      >Voir les IPs bloquées →</Link>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="py-3 pl-4">Email</th>
            <th class="py-3">IP</th>
            <th class="py-3">Type</th>
            <th class="py-3 pr-4">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="a in attempts.data" :key="a.id" class="hover:bg-slate-50">
            <td class="py-2.5 pl-4 text-slate-800">{{ a.email ?? '—' }}</td>
            <td class="py-2.5 font-mono text-xs text-slate-600">{{ a.ip_address ?? '—' }}</td>
            <td class="py-2.5 text-xs text-slate-500">{{ a.type ?? '—' }}</td>
            <td class="py-2.5 pr-4 text-xs text-slate-500">
              {{ a.attempted_at ? new Date(a.attempted_at).toLocaleString('fr-FR') : '—' }}
            </td>
          </tr>
          <tr v-if="!attempts.data.length">
            <td colspan="4" class="py-12 text-center text-slate-400">Aucune tentative.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="attempts.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>Page {{ attempts.current_page }} / {{ attempts.last_page }}</span>
      <div class="flex gap-1">
        <template v-for="link in attempts.links" :key="link.label">
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
