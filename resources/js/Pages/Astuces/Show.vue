<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import ShareButtons from '@/components/domain/ShareButtons.vue'
import AstuceCard, { type AstuceCardItem } from '@/components/domain/AstuceCard.vue'
import { useUiStore } from '@/stores/ui'

interface AstuceShow extends AstuceCardItem {
  description: string
  materiel_requis?: string | null
  etapes: string[]
  conseils?: string | null
  images: string[]
}

const props = defineProps<{
  astuce: AstuceShow
  similaires: AstuceCardItem[]
}>()

const ui = useUiStore()

const fullUrl = computed(() => {
  if (typeof window !== 'undefined') return window.location.href
  return props.astuce.url
})

const dateText = computed(() => {
  if (!props.astuce.date) return null
  return new Date(props.astuce.date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
})
</script>

<template>
  <Head>
    <title>{{ astuce.titre }}</title>
    <meta name="description" :content="astuce.extrait" />
    <meta property="og:title" :content="astuce.titre" />
    <meta property="og:description" :content="astuce.extrait" />
    <meta property="og:type" content="article" />
    <meta v-if="astuce.images[0]" property="og:image" :content="astuce.images[0]" />
  </Head>

  <article class="bg-surface-1 py-10">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
      <nav class="mb-3 text-xs text-surface-fg-muted">
        <Link :href="`/${ui.locale}/astuces`" class="hover:text-brand-700">{{ $t('nav.tips') }}</Link>
        <span class="mx-1" aria-hidden="true">/</span>
        <span class="text-surface-fg">{{ astuce.titre }}</span>
      </nav>

      <header class="space-y-3">
        <div class="flex flex-wrap items-center gap-2 text-xs">
          <span class="rounded-full bg-brand-100 px-2.5 py-0.5 font-semibold text-brand-800">{{ astuce.categorie_label }}</span>
          <span class="rounded-full bg-surface-2 px-2.5 py-0.5 font-medium text-surface-fg">{{ astuce.difficulte_label }}</span>
          <span v-if="astuce.temps_estime" class="text-surface-fg-muted">⏱ {{ astuce.temps_estime }} min</span>
        </div>
        <h1 class="text-3xl font-bold leading-tight">{{ astuce.titre }}</h1>
        <p class="text-sm text-surface-fg-muted">
          <span v-if="astuce.auteur">par {{ astuce.auteur }}</span>
          <span v-if="astuce.auteur && dateText" aria-hidden="true"> · </span>
          <time v-if="dateText" :datetime="astuce.date ?? undefined">{{ dateText }}</time>
        </p>
      </header>

      <p class="mt-6 text-base leading-relaxed text-surface-fg whitespace-pre-line">
        {{ astuce.description }}
      </p>

      <figure v-if="astuce.images.length" class="mt-6 grid gap-3" :class="astuce.images.length > 1 ? 'sm:grid-cols-2' : ''">
        <img
          v-for="(src, idx) in astuce.images"
          :key="src"
          :src="src"
          :alt="`Illustration ${idx + 1} de l'astuce ${astuce.titre}`"
          loading="lazy"
          class="rounded-lg border border-surface-border object-cover"
        />
      </figure>

      <section v-if="astuce.materiel_requis" class="mt-8 rounded-xl border border-surface-border bg-surface-0 p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-surface-fg-muted">Matériel requis</h2>
        <p class="mt-2 whitespace-pre-line text-sm text-surface-fg">{{ astuce.materiel_requis }}</p>
      </section>

      <section v-if="astuce.etapes.length" class="mt-8">
        <h2 class="text-xl font-bold">Étapes</h2>
        <ol class="mt-3 space-y-3">
          <li
            v-for="(etape, idx) in astuce.etapes"
            :key="idx"
            class="flex items-start gap-3 rounded-lg border border-surface-border bg-surface-0 p-4"
          >
            <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
              {{ idx + 1 }}
            </span>
            <p class="text-sm text-surface-fg">{{ etape }}</p>
          </li>
        </ol>
      </section>

      <section v-if="astuce.conseils" class="mt-8 rounded-xl border-l-4 border-amber-400 bg-amber-50 p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-amber-800">Conseils</h2>
        <p class="mt-2 whitespace-pre-line text-sm text-amber-900">{{ astuce.conseils }}</p>
      </section>

      <div class="mt-8 border-t border-surface-border pt-6">
        <ShareButtons :url="fullUrl" :title="astuce.titre" />
      </div>
    </div>
  </article>

  <section v-if="similaires.length" class="border-t border-surface-border py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="mb-5 text-xl font-bold">Astuces similaires</h2>
      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <AstuceCard v-for="a in similaires" :key="a.id" :astuce="a" />
      </div>
    </div>
  </section>
</template>
