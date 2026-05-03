<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'

defineOptions({ layout: AdminLayout })

interface BlockedIpRow {
  ip_address: string
  attempts: number
  last_attempt?: string | null
}

interface Paginated {
  data: BlockedIpRow[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

defineProps<{ blocked_ips: Paginated }>()

function unblock(ip: string) {
  if (!confirm(`Débloquer l'IP ${ip} ?`)) return
  router.post('/admin/security/unblock-ip', { ip_address: ip }, { preserveScroll: true })
}
useAdminTitle('IPs bloquées')
</script>

<template>
  <Head title="IPs bloquées" />

  <div class="space-y-5">
    <p class="text-sm text-slate-500">
      IPs ayant effectué ≥ 5 tentatives de connexion dans la dernière heure.
    </p>

    <div class="flex justify-end">
      <Link href="/admin/security/failed-attempts" class="text-sm text-brand-600 hover:underline">
        ← Toutes les tentatives
      </Link>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="py-3 pl-4">Adresse IP</th>
            <th class="py-3">Tentatives</th>
            <th class="py-3">Dernière tentative</th>
            <th class="py-3 pr-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="ip in blocked_ips.data" :key="ip.ip_address" class="hover:bg-slate-50">
            <td class="py-3 pl-4 font-mono text-slate-800">{{ ip.ip_address }}</td>
            <td class="py-3">
              <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
                {{ ip.attempts }}
              </span>
            </td>
            <td class="py-3 text-xs text-slate-500">
              {{ ip.last_attempt ? new Date(ip.last_attempt).toLocaleString('fr-FR') : '—' }}
            </td>
            <td class="py-3 pr-4 text-right">
              <button
                type="button"
                class="rounded px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50"
                @click="unblock(ip.ip_address)"
              >Débloquer</button>
            </td>
          </tr>
          <tr v-if="!blocked_ips.data.length">
            <td colspan="4" class="py-12 text-center text-slate-400">Aucune IP bloquée actuellement.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
