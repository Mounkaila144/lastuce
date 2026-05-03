<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import VideoPlayer from '@/components/domain/VideoPlayer.vue'

defineOptions({ layout: AdminLayout })

interface EpisodeShow {
  id: number
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
  vues: number
  thumbnail_url?: string | null
  tags_detail: { id: number; slug: string; nom: string }[]
}

const props = defineProps<{ episode: EpisodeShow }>()

const previewProvider = computed<'youtube' | 'facebook' | null>(() => {
  if (props.episode.youtube_url) return 'youtube'
  if (props.episode.facebook_url) return 'facebook'
  return null
})
const previewUrl = computed(() =>
  previewProvider.value === 'youtube' ? props.episode.youtube_url : props.episode.facebook_url,
)

function publish() {
  router.post(`/admin/episodes/${props.episode.slug}/publish`, {}, { preserveScroll: true })
}
function unpublish() {
  router.post(`/admin/episodes/${props.episode.slug}/unpublish`, {}, { preserveScroll: true })
}
function destroy() {
  if (!confirm(`Supprimer l'épisode « ${props.episode.titre} » ?`)) return
  router.delete(`/admin/episodes/${props.episode.slug}`)
}
useAdminTitle(() => props.episode.titre)
</script>

<template>
  <Head :title="episode.titre" />


  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2 text-sm">
        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
          {{ episode.statut }}
        </span>
        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
          {{ episode.type }}
        </span>
        <span class="text-slate-500">{{ episode.vues.toLocaleString('fr-FR') }} vues</span>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="episode.statut !== 'published'"
          type="button"
          class="inline-flex h-9 items-center rounded-md bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700"
          @click="publish"
        >
          Publier
        </button>
        <button
          v-else
          type="button"
          class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          @click="unpublish"
        >
          Dépublier
        </button>
        <Link
          :href="`/admin/episodes/${episode.slug}/edit`"
          class="inline-flex h-9 items-center rounded-md bg-brand-600 px-3 text-sm font-semibold text-white hover:bg-brand-700"
        >
          Éditer
        </Link>
        <button
          type="button"
          class="inline-flex h-9 items-center rounded-md border border-red-200 bg-white px-3 text-sm font-semibold text-red-700 hover:bg-red-50"
          @click="destroy"
        >
          Supprimer
        </button>
      </div>
    </div>

    <div v-if="previewUrl" class="rounded-xl border border-slate-200 bg-white p-4">
      <VideoPlayer
        :url="previewUrl"
        :provider="previewProvider"
        :thumbnail="episode.thumbnail_url ?? null"
        :title="episode.titre"
      />
    </div>

    <div v-if="episode.description" class="rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        Description
      </h2>
      <p class="text-sm text-slate-700">{{ episode.description }}</p>
    </div>

    <div v-if="episode.contenu" class="rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        Contenu
      </h2>
      <div class="prose prose-sm max-w-none text-slate-800" v-html="episode.contenu" />
    </div>

    <div v-if="episode.invite_nom" class="rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        Invité
      </h2>
      <p class="text-base font-semibold text-slate-900">{{ episode.invite_nom }}</p>
      <p v-if="episode.invite_bio" class="mt-1 text-sm text-slate-600">{{ episode.invite_bio }}</p>
    </div>

    <div v-if="episode.transcript" class="rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        Transcription
      </h2>
      <div class="whitespace-pre-line text-sm text-slate-700">{{ episode.transcript }}</div>
    </div>
  </div>
</template>
