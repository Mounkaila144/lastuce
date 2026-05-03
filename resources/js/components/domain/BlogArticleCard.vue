<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import type { BlogArticleListItem } from '@/types/domain'

const props = defineProps<{
  article: BlogArticleListItem
}>()

const formattedDate = computed(() => {
  if (!props.article.date_publication) return null
  return new Date(props.article.date_publication).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
})
</script>

<template>
  <article
    class="group overflow-hidden rounded-xl border border-surface-border bg-surface-0 shadow-sm transition-shadow hover:shadow-lg"
  >
    <Link
      :href="article.url"
      class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 rounded-xl"
    >
      <div class="aspect-video bg-surface-2">
        <img
          v-if="article.image"
          :src="article.image"
          :alt="article.titre"
          loading="lazy"
          class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
        />
      </div>
      <div class="space-y-2 p-4">
        <div v-if="article.categorie || formattedDate" class="flex flex-wrap items-center gap-2 text-xs text-surface-fg-muted">
          <span
            v-if="article.categorie"
            class="rounded-full bg-brand-100 px-2.5 py-0.5 font-semibold text-brand-800"
          >
            {{ article.categorie }}
          </span>
          <span v-if="formattedDate">{{ formattedDate }}</span>
          <span v-if="article.reading_time">· {{ article.reading_time }} min</span>
        </div>
        <h3 class="line-clamp-2 text-base font-semibold group-hover:text-brand-700">
          {{ article.titre }}
        </h3>
        <p v-if="article.extrait" class="line-clamp-3 text-sm text-surface-fg-muted">
          {{ article.extrait }}
        </p>
      </div>
    </Link>
  </article>
</template>
