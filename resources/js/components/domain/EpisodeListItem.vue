<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import type { EpisodeListItem as EpisodeListItemType } from '@/types/domain'

const props = defineProps<{ episode: EpisodeListItemType }>()

const thumbnail = computed(
  () => props.episode.thumbnail_url ?? props.episode.video_thumbnail ?? null,
)

const href = computed(() => props.episode.url ?? `/episodes/${props.episode.slug}`)

const date = computed(() => {
  const iso = props.episode.date_publication
  if (!iso) return null
  try {
    return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return null
  }
})
</script>

<template>
  <Link
    :href="href"
    class="group flex gap-4 rounded-xl border border-surface-border bg-surface-0 p-3 transition-shadow hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
  >
    <div class="relative aspect-video w-40 flex-shrink-0 overflow-hidden rounded-lg bg-surface-2">
      <img
        v-if="thumbnail"
        :src="thumbnail"
        :alt="episode.titre"
        loading="lazy"
        class="h-full w-full object-cover transition group-hover:brightness-90"
      />
      <div v-else class="h-full w-full bg-gradient-to-br from-slate-700 to-slate-900" aria-hidden="true" />
      <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
        <span
          class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-600/90 text-white shadow-md ring-2 ring-white/50 transition group-hover:scale-110 group-hover:bg-brand-500"
          aria-hidden="true"
        >
          <svg class="ml-0.5 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
            <path d="M6.5 4.5a1 1 0 0 1 1.5-.866l8 5a1 1 0 0 1 0 1.732l-8 5A1 1 0 0 1 6.5 14.5v-10Z" />
          </svg>
        </span>
      </div>
    </div>
    <div class="min-w-0 flex-1 space-y-1">
      <p class="text-xs font-medium uppercase tracking-wide text-brand-600">
        {{ episode.type_label ?? episode.type }}
      </p>
      <h3 class="line-clamp-2 text-sm font-semibold text-surface-fg group-hover:text-brand-700">
        {{ episode.titre }}
      </h3>
      <p v-if="episode.description" class="line-clamp-2 text-xs text-surface-fg-muted">
        {{ episode.description }}
      </p>
      <p class="flex items-center gap-2 text-xs text-surface-fg-muted">
        <time v-if="date" :datetime="episode.date_publication ?? undefined">{{ date }}</time>
        <span v-if="date && episode.vues !== undefined" aria-hidden="true">·</span>
        <span v-if="episode.vues !== undefined">{{ episode.vues.toLocaleString('fr-FR') }} vues</span>
      </p>
    </div>
  </Link>
</template>
