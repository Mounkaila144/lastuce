<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'

defineOptions({ layout: AdminLayout })

const props = defineProps<{ is_maintenance: boolean }>()

function toggle() {
  const action = props.is_maintenance ? 'désactiver' : 'activer'
  if (!confirm(`Êtes-vous sûr de vouloir ${action} le mode maintenance ?`)) return
  router.post('/admin/settings/maintenance', {}, { preserveScroll: true })
}
useAdminTitle('Mode maintenance')
</script>

<template>
  <Head title="Mode maintenance" />

  <div class="max-w-lg space-y-5">
    <div
      class="rounded-xl border p-6 text-center"
      :class="is_maintenance ? 'border-orange-300 bg-orange-50' : 'border-slate-200 bg-white'"
    >
      <div class="text-4xl mb-3">{{ is_maintenance ? '🔧' : '✅' }}</div>
      <h2 class="text-lg font-semibold" :class="is_maintenance ? 'text-orange-800' : 'text-slate-900'">
        {{ is_maintenance ? 'Mode maintenance actif' : 'Site en ligne' }}
      </h2>
      <p class="mt-2 text-sm text-slate-500">
        {{ is_maintenance
          ? 'Le site affiche une page de maintenance pour les visiteurs.'
          : 'Le site est accessible normalement pour tous les visiteurs.' }}
      </p>
      <button
        type="button"
        class="mt-4 inline-flex h-10 items-center rounded-md px-5 text-sm font-semibold text-white"
        :class="is_maintenance ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-orange-600 hover:bg-orange-700'"
        @click="toggle"
      >
        {{ is_maintenance ? 'Remettre en ligne' : 'Activer la maintenance' }}
      </button>
    </div>
  </div>
</template>
