<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import VideoPlayer from '@/components/domain/VideoPlayer.vue'
import EpisodeCard from '@/components/domain/EpisodeCard.vue'
import ShareButtons from '@/components/domain/ShareButtons.vue'
import { useUiStore } from '@/stores/ui'
import type { EpisodeListItem } from '@/types/domain'

interface EpisodeShow extends EpisodeListItem {
  contenu?: string | null
  transcript?: string | null
  invite_bio?: string | null
  category_label?: string | null
  category_slug?: string | null
  tags: { slug: string; nom: string }[]
}

interface SeoData {
  title: string
  description?: string | null
  image?: string | null
  video_url?: string | null
  video_provider?: string | null
  published_time?: string | null
  duration_iso?: string | null
}

const props = defineProps<{
  episode: EpisodeShow
  similar: EpisodeListItem[]
  previous: { slug: string; titre: string } | null
  next: { slug: string; titre: string } | null
  seo: SeoData
}>()

const ui = useUiStore()

const fullUrl = computed(() => {
  if (typeof window !== 'undefined') return window.location.href
  return props.episode.url ?? ''
})

const formattedDate = computed(() => {
  const iso = props.episode.date_publication
  if (!iso) return null
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
})

const videoSchema = computed(() => {
  const s = props.seo
  if (!s.video_url) return null
  return JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'VideoObject',
    name: s.title,
    description: s.description,
    thumbnailUrl: s.image,
    uploadDate: s.published_time,
    duration: s.duration_iso,
    embedUrl: s.video_url,
  })
})
</script>

<template>
  <Head>
    <title>{{ seo.title }}</title>
    <meta name="description" :content="seo.description ?? ''" />
    <meta property="og:title" :content="seo.title" />
    <meta property="og:description" :content="seo.description ?? ''" />
    <meta property="og:type" content="video.other" />
    <meta v-if="seo.image" property="og:image" :content="seo.image" />
    <meta v-if="seo.video_url" property="og:video" :content="seo.video_url" />
    <meta name="twitter:card" content="summary_large_image" />
    <component :is="'script'" v-if="videoSchema" type="application/ld+json" v-html="videoSchema" />
  </Head>

  <article class="bg-surface-1 py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
      <nav class="mb-3 text-xs text-surface-fg-muted">
        <Link :href="`/${ui.locale}/episodes`" class="hover:text-brand-700">{{ $t('nav.episodes') }}</Link>
        <span class="mx-1" aria-hidden="true">/</span>
        <span class="text-surface-fg">{{ episode.titre }}</span>
      </nav>

      <VideoPlayer
        v-if="episode.video_embed_url"
        :url="episode.video_embed_url"
        :provider="episode.video_provider"
        :thumbnail="episode.thumbnail_url ?? episode.video_thumbnail"
        :title="episode.titre"
      />
      <div v-else class="aspect-video rounded-xl bg-slate-900 text-white" aria-hidden="true" />

      <header class="mt-6 space-y-2">
        <div class="flex flex-wrap items-center gap-2 text-xs">
          <span class="rounded-full bg-brand-100 px-2.5 py-0.5 font-semibold text-brand-800">{{ episode.type_label }}</span>
          <Link
            v-if="episode.category_slug"
            :href="`/${ui.locale}/episodes?category=${episode.category_slug}`"
            class="rounded-full bg-surface-2 px-2.5 py-0.5 font-medium text-surface-fg hover:bg-surface-3"
          >
            {{ episode.category_label }}
          </Link>
          <span v-if="formattedDate" class="text-surface-fg-muted">{{ formattedDate }}</span>
          <span v-if="episode.vues !== undefined" class="text-surface-fg-muted">· {{ episode.vues.toLocaleString('fr-FR') }} vues</span>
        </div>
        <h1 class="text-3xl font-bold leading-tight">{{ episode.titre }}</h1>
        <p v-if="episode.description" class="text-base text-surface-fg-muted">{{ episode.description }}</p>
      </header>

      <div v-if="episode.tags.length" class="mt-4 flex flex-wrap gap-2">
        <Link
          v-for="tag in episode.tags"
          :key="tag.slug"
          :href="`/${ui.locale}/episodes?tag=${tag.slug}`"
          class="rounded-full border border-surface-border bg-surface-0 px-3 py-1 text-xs text-surface-fg hover:bg-surface-2"
        >
          #{{ tag.nom }}
        </Link>
      </div>

      <div class="mt-5 flex flex-wrap items-center gap-3">
        <ShareButtons :url="fullUrl" :title="episode.titre" />
      </div>
    </div>
  </article>

  <section v-if="episode.invite_nom || episode.contenu" class="py-10">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 grid gap-8 md:grid-cols-3">
      <div class="md:col-span-2 space-y-4">
        <h2 v-if="episode.contenu" class="text-xl font-bold">À propos de cet épisode</h2>
        <div v-if="episode.contenu" class="prose prose-sm max-w-none text-surface-fg" v-html="episode.contenu" />
        <details v-if="episode.transcript" class="rounded-lg border border-surface-border bg-surface-1 p-4">
          <summary class="cursor-pointer text-sm font-semibold">Transcription</summary>
          <div class="mt-3 whitespace-pre-line text-sm text-surface-fg-muted">{{ episode.transcript }}</div>
        </details>
      </div>
      <aside v-if="episode.invite_nom" class="rounded-xl border border-surface-border bg-surface-0 p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-surface-fg-muted">Invité</h2>
        <p class="mt-1 text-base font-bold">{{ episode.invite_nom }}</p>
        <p v-if="episode.invite_bio" class="mt-2 text-sm text-surface-fg-muted">{{ episode.invite_bio }}</p>
      </aside>
    </div>
  </section>

  <nav v-if="previous || next" class="border-t border-surface-border bg-surface-1 py-6" aria-label="Navigation entre épisodes">
    <div class="mx-auto flex max-w-5xl items-stretch justify-between gap-4 px-4 sm:px-6 lg:px-8">
      <Link
        v-if="previous"
        :href="`/${ui.locale}/episodes/${previous.slug}`"
        class="group flex-1 rounded-lg border border-surface-border bg-surface-0 p-4 transition hover:bg-surface-2"
      >
        <span class="text-xs uppercase tracking-wide text-surface-fg-muted">← Précédent</span>
        <p class="mt-1 line-clamp-2 text-sm font-semibold group-hover:text-brand-700">{{ previous.titre }}</p>
      </Link>
      <span v-else class="flex-1" />
      <Link
        v-if="next"
        :href="`/${ui.locale}/episodes/${next.slug}`"
        class="group flex-1 rounded-lg border border-surface-border bg-surface-0 p-4 text-right transition hover:bg-surface-2"
      >
        <span class="text-xs uppercase tracking-wide text-surface-fg-muted">Suivant →</span>
        <p class="mt-1 line-clamp-2 text-sm font-semibold group-hover:text-brand-700">{{ next.titre }}</p>
      </Link>
    </div>
  </nav>

  <section v-if="similar.length" class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="mb-5 text-xl font-bold">Dans le même style</h2>
      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <EpisodeCard v-for="ep in similar" :key="ep.id" :episode="ep" />
      </div>
    </div>
  </section>
</template>
