<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import RichTextEditor from '@/components/ui/RichTextEditor.vue'
import MediaUploader from '@/components/ui/MediaUploader.vue'
import VideoPlayer from '@/components/domain/VideoPlayer.vue'
import type { SelectOption } from '@/components/ui/Select.vue'

defineOptions({ layout: AdminLayout })

interface EpisodeForm {
  id?: number
  titre: string
  slug?: string | null
  description?: string | null
  contenu?: string | null
  transcript?: string | null
  invite_nom?: string | null
  invite_bio?: string | null
  youtube_url?: string | null
  facebook_url?: string | null
  audio_url?: string | null
  duree?: number | null
  type: string
  statut: string
  date_publication?: string | null
  category_id?: number | null
  tags: number[]
  thumbnail_url?: string | null
  thumbnail_thumb_url?: string | null
  has_uploaded_thumbnail?: boolean
}

const props = defineProps<{
  episode: EpisodeForm | null
  options: {
    types: SelectOption[]
    statuses: SelectOption[]
    categories: SelectOption[]
    tags: SelectOption[]
  }
}>()

const isEdit = computed(() => !!props.episode?.id)

const form = useForm({
  titre: props.episode?.titre ?? '',
  slug: props.episode?.slug ?? '',
  description: props.episode?.description ?? '',
  contenu: props.episode?.contenu ?? '',
  transcript: props.episode?.transcript ?? '',
  invite_nom: props.episode?.invite_nom ?? '',
  invite_bio: props.episode?.invite_bio ?? '',
  youtube_url: props.episode?.youtube_url ?? '',
  facebook_url: props.episode?.facebook_url ?? '',
  audio_url: props.episode?.audio_url ?? '',
  duree: props.episode?.duree ?? null,
  type: props.episode?.type ?? 'episode',
  statut: props.episode?.statut ?? 'draft',
  date_publication: props.episode?.date_publication ?? '',
  category_id: props.episode?.category_id ?? null,
  tags: props.episode?.tags ?? ([] as number[]),
  thumbnail: null as File | null,
  remove_thumbnail: false as boolean,
})

function submit() {
  if (isEdit.value && props.episode) {
    // Spoof PUT pour permettre l'upload de fichier (FormData ne supporte
    // pas la méthode PUT en native — Laravel gère via _method).
    form
      .transform((data) => ({ ...data, _method: 'put' }))
      .post(`/admin/episodes/${props.episode.slug}`, { forceFormData: true })
  } else {
    form.post('/admin/episodes', { forceFormData: true })
  }
}

function detectProvider(url: string | null | undefined): 'youtube' | 'facebook' | null {
  if (!url) return null
  if (/youtu\.be|youtube\.com/i.test(url)) return 'youtube'
  if (/facebook\.com|fb\.watch/i.test(url)) return 'facebook'
  return null
}

const previewProvider = computed(
  () => detectProvider(form.youtube_url) ?? detectProvider(form.facebook_url),
)
const previewSourceUrl = computed(() =>
  previewProvider.value === 'youtube' ? form.youtube_url : form.facebook_url,
)
function buildEmbedUrl(provider: 'youtube' | 'facebook' | null, url: string | null | undefined): string | null {
  if (!url || !provider) return null
  if (provider === 'youtube') {
    const m = url.match(/(?:youtu\.be\/|v=|embed\/|shorts\/|live\/)([A-Za-z0-9_-]{11})/)
    return m ? `https://www.youtube-nocookie.com/embed/${m[1]}` : null
  }
  return `https://www.facebook.com/plugins/video.php?href=${encodeURIComponent(url)}&show_text=false`
}
const previewUrl = computed(() => buildEmbedUrl(previewProvider.value, previewSourceUrl.value))

const tagOptions = computed(() => props.options.tags)
const categoryOptions = computed<SelectOption[]>(() => [
  { value: '', label: '— Aucune —' },
  ...props.options.categories,
])

function toggleTag(id: number) {
  const idx = form.tags.indexOf(id)
  if (idx === -1) form.tags.push(id)
  else form.tags.splice(idx, 1)
}
useAdminTitle(() => (isEdit.value ? 'Éditer un épisode' : 'Nouvel épisode'))
</script>

<template>
  <Head :title="isEdit ? 'Éditer un épisode' : 'Nouvel épisode'" />


  <form class="space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
      <!-- Colonne principale -->
      <div class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <Input
            v-model="form.titre"
            label="Titre *"
            required
            :error="form.errors.titre"
          />
          <Input
            v-model="form.slug"
            label="Slug"
            helper="Laisser vide pour auto-génération depuis le titre."
            :error="form.errors.slug"
            class="mt-4"
          />
          <Textarea
            v-model="form.description"
            label="Description courte"
            rows="2"
            :error="form.errors.description"
            class="mt-4"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Vidéo
          </h2>
          <Input
            v-model="form.youtube_url"
            label="URL YouTube"
            placeholder="https://youtu.be/…"
            :error="form.errors.youtube_url"
          />
          <Input
            v-model="form.facebook_url"
            label="URL Facebook"
            placeholder="https://www.facebook.com/…/videos/…"
            :error="form.errors.facebook_url"
          />
          <p class="text-xs text-slate-500">
            Au moins une URL est requise (YouTube ou Facebook).
          </p>

          <div v-if="previewUrl" class="mt-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
              Aperçu en temps réel
            </p>
            <VideoPlayer
              :url="previewUrl"
              :provider="previewProvider"
              :title="form.titre"
            />
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <RichTextEditor
            v-model="form.contenu"
            label="Contenu / description longue"
            :error="form.errors.contenu"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Invité
          </h2>
          <Input
            v-model="form.invite_nom"
            label="Nom de l'invité"
            :error="form.errors.invite_nom"
          />
          <Textarea
            v-model="form.invite_bio"
            label="Bio courte"
            rows="3"
            :error="form.errors.invite_bio"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <Textarea
            v-model="form.transcript"
            label="Transcription (optionnel)"
            rows="6"
            :error="form.errors.transcript"
          />
        </div>
      </div>

      <!-- Colonne latérale -->
      <aside class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Publication
          </h2>
          <Select
            v-model="form.statut"
            :options="options.statuses"
            label="Statut"
            :error="form.errors.statut"
          />
          <Select
            v-model="form.type"
            :options="options.types"
            label="Type *"
            :error="form.errors.type"
          />
          <Input
            v-model="form.date_publication"
            type="datetime-local"
            label="Date de publication"
            :error="form.errors.date_publication"
          />
          <Input
            v-model.number="form.duree"
            type="number"
            min="0"
            label="Durée (secondes)"
            :error="form.errors.duree"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <MediaUploader
            v-model="form.thumbnail"
            label="Vignette"
            helper="JPEG / PNG / WebP, 5 Mo max."
            :existing-url="episode?.thumbnail_thumb_url ?? null"
            :removable="episode?.has_uploaded_thumbnail ?? false"
            :error="form.errors.thumbnail"
            @remove="form.remove_thumbnail = true"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
          <Select
            v-model="form.category_id"
            :options="categoryOptions"
            label="Catégorie"
            :error="form.errors.category_id"
          />
          <div>
            <p class="mb-2 text-sm font-medium text-slate-700">Tags</p>
            <div v-if="tagOptions.length" class="flex flex-wrap gap-2">
              <button
                v-for="t in tagOptions"
                :key="t.value"
                type="button"
                :class="[
                  'rounded-full border px-3 py-1 text-xs transition',
                  form.tags.includes(Number(t.value))
                    ? 'border-brand-600 bg-brand-600 text-white'
                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100',
                ]"
                @click="toggleTag(Number(t.value))"
              >
                #{{ t.label }}
              </button>
            </div>
            <p v-else class="text-xs text-slate-500">
              Aucun tag défini. Créer des tags depuis le module Catégories (v1.1).
            </p>
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <Button type="submit" variant="primary" :loading="form.processing" class="w-full">
            {{ isEdit ? 'Mettre à jour' : 'Créer l\'épisode' }}
          </Button>
          <Link
            href="/admin/episodes"
            class="text-center text-sm font-medium text-slate-600 hover:text-slate-900"
          >
            Annuler
          </Link>
        </div>
      </aside>
    </div>
  </form>
</template>
