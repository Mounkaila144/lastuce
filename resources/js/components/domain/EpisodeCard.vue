<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import type { EpisodeListItem } from '@/types/domain'

const props = defineProps<{
  episode: EpisodeListItem
}>()

const cardRef = ref<HTMLElement | null>(null)

const typeBadge = computed(() => {
  const type = props.episode.type
  return {
    episode: { label: props.episode.type_label ?? 'Épisode', class: 'bg-brand-100 text-brand-800' },
    coulisse: { label: props.episode.type_label ?? 'Coulisse', class: 'bg-accent-100 text-accent-800' },
    bonus: { label: props.episode.type_label ?? 'Bonus', class: 'bg-amber-100 text-amber-800' },
    special: { label: props.episode.type_label ?? 'Spécial', class: 'bg-emerald-100 text-emerald-800' },
  }[type]
})

const dureeFormat = computed(() => {
  const d = props.episode.duree
  if (!d) return null
  const minutes = Math.floor(d / 60)
  const seconds = d % 60
  return minutes > 0 ? `${minutes}:${String(seconds).padStart(2, '0')}` : `0:${String(seconds).padStart(2, '0')}`
})

const datePublishedFormat = computed(() => {
  const iso = props.episode.date_publication
  if (!iso) return null
  try {
    return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return null
  }
})

const thumbnail = computed(
  () => props.episode.thumbnail_url ?? props.episode.video_thumbnail ?? null,
)

const href = computed(() => props.episode.url ?? `/episodes/${props.episode.slug}`)

onMounted(async () => {
  if (!cardRef.value) return
  // GSAP est déjà dans le bundle (cf. package.json). On l'importe à la demande
  // pour éviter de le charger sur les pages qui n'ont pas de cards.
  const { gsap } = await import('gsap')
  const card = cardRef.value
  const onEnter = () =>
    gsap.to(card, { y: -6, scale: 1.02, duration: 0.25, ease: 'power2.out' })
  const onLeave = () =>
    gsap.to(card, { y: 0, scale: 1, duration: 0.25, ease: 'power2.out' })
  card.addEventListener('mouseenter', onEnter)
  card.addEventListener('mouseleave', onLeave)
  card.addEventListener('focusin', onEnter)
  card.addEventListener('focusout', onLeave)
})
</script>

<template>
  <article
    ref="cardRef"
    class="group overflow-hidden rounded-xl border border-surface-border bg-surface-0 shadow-sm transition-shadow hover:shadow-lg"
  >
    <Link :href="href" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 rounded-xl">
      <div class="relative aspect-video overflow-hidden bg-surface-2">
        <img
          v-if="thumbnail"
          :src="thumbnail"
          :alt="episode.titre"
          loading="lazy"
          class="h-full w-full object-cover transition group-hover:brightness-90"
        />
        <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-700 to-slate-900 text-white/40" />
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center bg-gradient-to-t from-black/40 via-transparent to-transparent">
          <span
            class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-600/90 text-white shadow-lg ring-2 ring-white/50 backdrop-blur-sm transition group-hover:scale-110 group-hover:bg-brand-500"
            aria-hidden="true"
          >
            <svg class="ml-1 h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
              <path d="M6.5 4.5a1 1 0 0 1 1.5-.866l8 5a1 1 0 0 1 0 1.732l-8 5A1 1 0 0 1 6.5 14.5v-10Z" />
            </svg>
          </span>
        </div>
        <span
          :class="['absolute left-3 top-3 rounded-full px-2.5 py-0.5 text-xs font-semibold', typeBadge.class]"
        >
          {{ typeBadge.label }}
        </span>
        <span
          v-if="dureeFormat"
          class="absolute bottom-3 right-3 rounded bg-slate-900/80 px-2 py-0.5 text-xs font-medium text-white"
        >
          {{ dureeFormat }}
        </span>
      </div>
      <div class="space-y-2 p-4">
        <h3 class="line-clamp-2 text-base font-semibold text-surface-fg group-hover:text-brand-700">
          {{ episode.titre }}
        </h3>
        <p class="flex items-center gap-2 text-xs text-surface-fg-muted">
          <time v-if="datePublishedFormat" :datetime="episode.date_publication ?? undefined">{{ datePublishedFormat }}</time>
          <span v-if="datePublishedFormat && episode.vues !== undefined" aria-hidden="true">·</span>
          <span v-if="episode.vues !== undefined">{{ episode.vues.toLocaleString('fr-FR') }} vues</span>
        </p>
      </div>
    </Link>
  </article>
</template>
