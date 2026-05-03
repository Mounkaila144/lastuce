<script setup lang="ts">
import { computed, ref } from 'vue'
import type { VideoProvider } from '@/types/domain'

const props = withDefaults(
  defineProps<{
    /** URL d'embed déjà calculée côté serveur via VideoEmbedService. */
    url: string
    provider?: VideoProvider | null
    /** URL d'image de fallback (poster). YouTube fournit une miniature ; pour Facebook prévoir l'override. */
    thumbnail?: string | null
    title?: string
    aspect?: '16/9' | '9/16' | '1/1'
  }>(),
  {
    aspect: '16/9',
  },
)

const playing = ref(false)

function play() {
  playing.value = true
}

const aspectClass = computed(() => {
  return {
    '16/9': 'aspect-video',
    '9/16': 'aspect-[9/16]',
    '1/1': 'aspect-square',
  }[props.aspect]
})

const detectedProvider = computed<VideoProvider | null>(() => {
  if (props.provider) return props.provider
  if (props.url.includes('facebook') || props.url.includes('fb.watch')) return 'facebook'
  if (props.url.includes('youtube')) return 'youtube'
  return null
})

const iframeAllow = computed(() =>
  detectedProvider.value === 'youtube'
    ? 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
    : 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share',
)

const iframeUrl = computed(() => {
  if (!playing.value) return null
  if (detectedProvider.value === 'youtube' && !props.url.includes('autoplay=')) {
    const sep = props.url.includes('?') ? '&' : '?'
    return `${props.url}${sep}autoplay=1`
  }
  return props.url
})
</script>

<template>
  <div :class="['relative w-full overflow-hidden rounded-xl bg-slate-900', aspectClass]">
    <iframe
      v-if="iframeUrl"
      :src="iframeUrl"
      :title="title ?? 'Vidéo'"
      class="absolute inset-0 h-full w-full"
      :allow="iframeAllow"
      allowfullscreen
      loading="lazy"
      referrerpolicy="strict-origin-when-cross-origin"
    />
    <button
      v-else
      type="button"
      class="group absolute inset-0 flex items-center justify-center text-white focus:outline-none"
      :aria-label="title ? `Lire la vidéo : ${title}` : 'Lire la vidéo'"
      @click="play"
    >
      <img
        v-if="thumbnail"
        :src="thumbnail"
        :alt="title ?? ''"
        class="absolute inset-0 h-full w-full object-cover transition group-hover:brightness-75"
        loading="lazy"
      />
      <span
        v-else
        class="absolute inset-0 bg-gradient-to-br from-slate-700 to-slate-900"
        aria-hidden="true"
      />
      <span class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent" aria-hidden="true" />
      <span class="relative flex h-20 w-20 items-center justify-center rounded-full bg-brand-600 shadow-2xl ring-4 ring-white/60 transition group-hover:scale-110 group-hover:bg-brand-500 group-hover:ring-white/80 sm:h-24 sm:w-24">
        <span class="absolute inset-0 animate-ping rounded-full bg-brand-400/40" aria-hidden="true" />
        <svg class="relative ml-1.5 h-9 w-9 sm:h-10 sm:w-10" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path d="M6.5 4.5a1 1 0 0 1 1.5-.866l8 5a1 1 0 0 1 0 1.732l-8 5A1 1 0 0 1 6.5 14.5v-10Z" />
        </svg>
      </span>
    </button>
  </div>
</template>
