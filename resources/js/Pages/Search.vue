<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import EpisodeCard from '@/components/domain/EpisodeCard.vue'
import { useUiStore } from '@/stores/ui'
import type { EpisodeListItem } from '@/types/domain'

interface ArticleResult {
  id: number
  slug: string
  titre: string
  extrait?: string | null
  url: string
}

interface AstuceResult {
  id: number
  titre: string
}

const props = defineProps<{
  query: string
  results: {
    episodes: EpisodeListItem[]
    articles: ArticleResult[]
    astuces: AstuceResult[]
  }
}>()

const ui = useUiStore()

const totalCount = computed(
  () => props.results.episodes.length + props.results.articles.length + props.results.astuces.length,
)
</script>

<template>
  <Head :title="`Recherche : ${query}`" />

  <section class="bg-surface-1 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h1 class="text-2xl font-bold">Résultats pour « {{ query }} »</h1>
      <p class="mt-1 text-sm text-surface-fg-muted">{{ totalCount }} résultat{{ totalCount > 1 ? 's' : '' }}.</p>
    </div>
  </section>

  <section v-if="results.episodes.length" class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="mb-4 text-xl font-bold">Épisodes</h2>
      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <EpisodeCard v-for="ep in results.episodes" :key="ep.id" :episode="ep" />
      </div>
    </div>
  </section>

  <section v-if="results.articles.length" class="border-t border-surface-border py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="mb-4 text-xl font-bold">Articles</h2>
      <ul class="space-y-3">
        <li v-for="art in results.articles" :key="art.id" class="rounded-lg border border-surface-border bg-surface-0 p-4 hover:bg-surface-2">
          <Link :href="art.url" class="block">
            <p class="font-semibold">{{ art.titre }}</p>
            <p v-if="art.extrait" class="mt-1 line-clamp-2 text-sm text-surface-fg-muted">{{ art.extrait }}</p>
          </Link>
        </li>
      </ul>
    </div>
  </section>

  <section v-if="results.astuces.length" class="border-t border-surface-border py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="mb-4 text-xl font-bold">Astuces</h2>
      <ul class="grid gap-3 sm:grid-cols-2">
        <li v-for="a in results.astuces" :key="a.id">
          <Link :href="`/${ui.locale}/astuces/${a.id}`" class="block rounded-lg border border-surface-border bg-surface-0 p-3 hover:bg-surface-2">
            {{ a.titre }}
          </Link>
        </li>
      </ul>
    </div>
  </section>

  <section v-if="totalCount === 0" class="py-16">
    <div class="mx-auto max-w-2xl px-4 text-center sm:px-6 lg:px-8">
      <p class="text-base font-semibold">Aucun résultat ne correspond à votre recherche.</p>
      <p class="mt-1 text-sm text-surface-fg-muted">Essayez un autre terme ou élargissez vos critères.</p>
    </div>
  </section>
</template>
