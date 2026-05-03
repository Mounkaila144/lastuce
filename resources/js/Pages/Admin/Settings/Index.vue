<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
  settings: {
    site_name: string
    site_description: string
    contact_email: string
    ga_id?: string | null
    maintenance_mode?: boolean
  }
  is_maintenance: boolean
}>()

const form = useForm({
  site_name: props.settings.site_name ?? '',
  site_description: props.settings.site_description ?? '',
  contact_email: props.settings.contact_email ?? '',
  ga_id: props.settings.ga_id ?? '',
})

function submit() {
  form.post('/admin/settings')
}
useAdminTitle('Paramètres')
</script>

<template>
  <Head title="Paramètres" />

  <div class="space-y-6 max-w-2xl">
    <!-- Mode maintenance banner -->
    <div
      v-if="is_maintenance"
      class="rounded-lg bg-orange-100 border border-orange-300 px-4 py-3 text-sm font-medium text-orange-800"
    >
      Le site est actuellement en mode maintenance.
      <a href="/admin/settings/maintenance" class="ml-2 underline">Désactiver</a>
    </div>

    <form class="space-y-5" @submit.prevent="submit">
      <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Général</h2>
        <Input v-model="form.site_name" label="Nom du site *" required :error="form.errors.site_name" />
        <Input v-model="form.site_description" label="Description" :error="form.errors.site_description" />
        <Input v-model="form.contact_email" type="email" label="Email de contact *" required :error="form.errors.contact_email" />
        <Input v-model="form.ga_id" label="Google Analytics ID" placeholder="G-XXXXXXXXXX" :error="form.errors.ga_id" />
      </div>

      <Button type="submit" variant="primary" :loading="form.processing">
        Enregistrer les paramètres
      </Button>
    </form>

    <!-- Liens rapides -->
    <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Actions système</h2>
      <div class="flex flex-wrap gap-3">
        <a
          href="/admin/settings/backup"
          class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >Sauvegardes</a>
        <a
          href="/admin/settings/maintenance"
          class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >Mode maintenance</a>
      </div>
    </div>
  </div>
</template>
