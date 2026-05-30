<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Button from '@/components/ui/Button.vue'
import MediaUploader from '@/components/ui/MediaUploader.vue'

defineOptions({ layout: AdminLayout })

interface GalleryImageForm {
  id?: number
  titre?: string | null
  description?: string | null
  is_visible: boolean
  ordre: number
  image_url?: string | null
  has_image?: boolean
}

const props = defineProps<{
  image: GalleryImageForm | null
}>()

const isEdit = computed(() => !!props.image?.id)

const form = useForm({
  titre: props.image?.titre ?? '',
  description: props.image?.description ?? '',
  is_visible: props.image?.is_visible ?? true,
  ordre: props.image?.ordre ?? 0,
  image: null as File | null,
})

function submit() {
  if (isEdit.value && props.image) {
    form
      .transform((data) => ({ ...data, _method: 'put' }))
      .post(`/admin/gallery/${props.image.id}`, { forceFormData: true })
  } else {
    form.post('/admin/gallery', { forceFormData: true })
  }
}

useAdminTitle(() => (isEdit.value ? 'Éditer une image' : 'Nouvelle image'))
</script>

<template>
  <Head :title="isEdit ? 'Éditer une image' : 'Nouvelle image'" />

  <form class="space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
      <!-- Colonne principale -->
      <div class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <Input
            v-model="form.titre"
            label="Titre"
            helper="Optionnel — affiché en légende sous l'image."
            :error="form.errors.titre"
          />
          <Textarea
            v-model="form.description"
            label="Description"
            :rows="4"
            :error="form.errors.description"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <MediaUploader
            v-model="form.image"
            :label="isEdit ? 'Remplacer l\'image' : 'Image *'"
            helper="JPEG / PNG / WebP, 5 Mo max."
            :existing-url="image?.image_url ?? null"
            :error="form.errors.image"
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
            Visible sur le site
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
            {{ isEdit ? 'Mettre à jour' : 'Ajouter l\'image' }}
          </Button>
          <Link href="/admin/gallery" class="text-center text-sm font-medium text-slate-600 hover:text-slate-900">
            Annuler
          </Link>
        </div>
      </aside>
    </div>
  </form>
</template>
