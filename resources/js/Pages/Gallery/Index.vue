<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'

interface GalleryImage {
  id: number
  titre: string | null
  description: string | null
  thumb_url: string | null
  card_url: string | null
  full_url: string | null
}

defineProps<{
  images: GalleryImage[]
}>()

const lightbox = ref<GalleryImage | null>(null)

function open(image: GalleryImage) {
  lightbox.value = image
  if (typeof document !== 'undefined') document.body.style.overflow = 'hidden'
}

function close() {
  lightbox.value = null
  if (typeof document !== 'undefined') document.body.style.overflow = ''
}
</script>

<template>
  <Head title="Galerie" />

  <section class="bg-gradient-to-br from-brand-700 via-brand-600 to-accent-600 text-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 md:py-16 lg:px-8">
      <h1 class="font-display text-3xl font-bold sm:text-4xl">{{ $t('gallery.title') }}</h1>
      <p class="mt-3 max-w-2xl text-base text-white/90">
        {{ $t('gallery.subtitle') }}
      </p>
    </div>
  </section>

  <section class="py-10 md:py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div
        v-if="images.length"
        class="columns-2 gap-3 sm:columns-3 lg:columns-4 [&>*]:mb-3"
      >
        <button
          v-for="image in images"
          :key="image.id"
          type="button"
          class="group block w-full overflow-hidden rounded-xl border border-surface-border bg-surface-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
          @click="open(image)"
        >
          <img
            :src="image.card_url ?? image.full_url ?? ''"
            :alt="image.titre ?? ''"
            loading="lazy"
            class="w-full object-cover transition duration-300 group-hover:scale-[1.03]"
          />
          <span v-if="image.titre" class="block px-3 py-2 text-left text-sm font-medium text-surface-fg">
            {{ image.titre }}
          </span>
        </button>
      </div>

      <p v-else class="py-16 text-center text-surface-fg-muted">
        {{ $t('gallery.empty') }}
      </p>
    </div>
  </section>

  <!-- Lightbox -->
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="lightbox"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/90 p-4"
        role="dialog"
        aria-modal="true"
        @click.self="close"
      >
        <button
          type="button"
          class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
          :aria-label="$t('gallery.close')"
          @click="close"
        >
          <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
          </svg>
        </button>
        <figure class="max-h-[90vh] max-w-5xl">
          <img
            :src="lightbox.full_url ?? ''"
            :alt="lightbox.titre ?? ''"
            class="max-h-[80vh] w-auto rounded-lg object-contain"
          />
          <figcaption v-if="lightbox.titre || lightbox.description" class="mt-3 text-center text-white">
            <p v-if="lightbox.titre" class="font-semibold">{{ lightbox.titre }}</p>
            <p v-if="lightbox.description" class="mt-1 text-sm text-white/80">{{ lightbox.description }}</p>
          </figcaption>
        </figure>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
