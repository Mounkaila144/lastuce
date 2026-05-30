<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import MediaUploader from '@/components/ui/MediaUploader.vue'

defineOptions({ layout: AdminLayout })

interface PartnerForm {
  id?: number
  nom: string
  site_web?: string | null
  is_visible: boolean
  ordre: number
  logo_url?: string | null
  has_logo?: boolean
}

const props = defineProps<{
  partner: PartnerForm | null
}>()

const isEdit = computed(() => !!props.partner?.id)

const form = useForm({
  nom: props.partner?.nom ?? '',
  site_web: props.partner?.site_web ?? '',
  is_visible: props.partner?.is_visible ?? true,
  ordre: props.partner?.ordre ?? 0,
  logo: null as File | null,
})

function submit() {
  if (isEdit.value && props.partner) {
    form
      .transform((data) => ({ ...data, _method: 'put' }))
      .post(`/admin/partners/${props.partner.id}`, { forceFormData: true })
  } else {
    form.post('/admin/partners', { forceFormData: true })
  }
}

useAdminTitle(() => (isEdit.value ? 'Éditer un partenaire' : 'Nouveau partenaire'))
</script>

<template>
  <Head :title="isEdit ? 'Éditer un partenaire' : 'Nouveau partenaire'" />

  <form class="space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
      <!-- Colonne principale -->
      <div class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <Input
            v-model="form.nom"
            label="Nom du partenaire *"
            required
            :error="form.errors.nom"
          />
          <Input
            v-model="form.site_web"
            type="url"
            label="Site web"
            placeholder="https://…"
            :error="form.errors.site_web"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <MediaUploader
            v-model="form.logo"
            :label="isEdit ? 'Remplacer le logo' : 'Logo *'"
            helper="JPEG / PNG / WebP, 5 Mo max. Fond transparent recommandé."
            :existing-url="partner?.logo_url ?? null"
            :error="form.errors.logo"
          />
        </div>
      </div>

      <!-- Colonne latérale -->
      <aside class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Affichage
          </h2>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_visible" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
            Visible dans le bandeau d'accueil
          </label>
          <Input
            v-model.number="form.ordre"
            type="number"
            min="0"
            label="Ordre d'affichage"
            helper="Plus petit = affiché en premier."
            :error="form.errors.ordre"
          />
        </div>

        <div class="flex flex-col gap-2">
          <Button type="submit" variant="primary" :loading="form.processing" class="w-full">
            {{ isEdit ? 'Mettre à jour' : 'Ajouter le partenaire' }}
          </Button>
          <Link href="/admin/partners" class="text-center text-sm font-medium text-slate-600 hover:text-slate-900">
            Annuler
          </Link>
        </div>
      </aside>
    </div>
  </form>
</template>
