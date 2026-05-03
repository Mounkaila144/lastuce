<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'

defineOptions({ layout: AdminLayout })

interface BackupFile {
  name: string
  size: number
  date: number
}

defineProps<{ backups: BackupFile[] }>()

function createBackup() {
  if (!confirm('Lancer une nouvelle sauvegarde ?')) return
  router.post('/admin/settings/backup', {}, { preserveScroll: true })
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return bytes + ' o'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko'
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo'
}
useAdminTitle('Sauvegardes')
</script>

<template>
  <Head title="Sauvegardes" />

  <div class="space-y-5 max-w-2xl">
    <div class="flex justify-end">
      <button
        type="button"
        class="inline-flex h-9 items-center rounded-md bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700"
        @click="createBackup"
      >Nouvelle sauvegarde</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="py-3 pl-4">Fichier</th>
            <th class="py-3">Taille</th>
            <th class="py-3 pr-4">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="b in backups" :key="b.name" class="hover:bg-slate-50">
            <td class="py-3 pl-4 font-mono text-xs text-slate-800">{{ b.name }}</td>
            <td class="py-3 text-slate-600">{{ formatBytes(b.size) }}</td>
            <td class="py-3 pr-4 text-xs text-slate-500">
              {{ new Date(b.date * 1000).toLocaleString('fr-FR') }}
            </td>
          </tr>
          <tr v-if="!backups.length">
            <td colspan="3" class="py-12 text-center text-slate-400">Aucune sauvegarde disponible.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
