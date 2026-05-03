<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import BlogArticleCard from '@/components/domain/BlogArticleCard.vue'
import BlogSidebar from '@/components/domain/BlogSidebar.vue'
import type { BlogArticleListItem } from '@/types/domain'

interface PaginatedArticles {
  data: BlogArticleListItem[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

interface Sidebar {
  recent: { slug: string; titre: string; date_publication?: string | null }[]
  popular: { slug: string; titre: string; vues: number }[]
  categories: { slug: string; name: string; count: number }[]
  archives: { value: string; year: number; month: number; count: number }[]
}

const props = defineProps<{
  articles: PaginatedArticles
  category: { slug: string; name: string; description: string }
  sidebar: Sidebar
}>()

const seoTitle = computed(() => `${props.category.name} — Blog L'Astuce`)
const empty = computed(() => props.articles.data.length === 0)
</script>

<template>
  <Head>
    <title>{{ seoTitle }}</title>
    <meta name="description" :content="category.description" />
    <meta property="og:title" :content="seoTitle" />
    <meta property="og:description" :content="category.description" />
    <meta property="og:type" content="website" />
  </Head>

  <section class="bg-surface-1 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <p class="text-xs uppercase tracking-wider text-brand-700">Catégorie</p>
      <h1 class="mt-1 text-3xl font-bold">{{ category.name }}</h1>
      <p v-if="category.description" class="mt-2 max-w-2xl text-sm text-surface-fg-muted">
        {{ category.description }}
      </p>
      <p class="mt-2 text-sm text-surface-fg-muted">
        {{ articles.meta.total.toLocaleString('fr-FR') }}
        article{{ articles.meta.total > 1 ? 's' : '' }}
      </p>
    </div>
  </section>

  <section class="py-10">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:px-8">
      <div>
        <div
          v-if="empty"
          class="rounded-xl border border-dashed border-surface-border bg-surface-1 p-12 text-center"
        >
          <p class="text-base font-semibold">Aucun article dans cette catégorie pour l'instant.</p>
        </div>
        <div v-else class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
          <BlogArticleCard v-for="article in articles.data" :key="article.id" :article="article" />
        </div>

        <nav
          v-if="!empty && articles.meta.last_page > 1"
          class="mt-8 flex flex-wrap justify-center gap-2"
          aria-label="Pagination"
        >
          <Link
            v-for="link in articles.links"
            :key="link.label + link.url"
            :href="link.url ?? '#'"
            v-html="link.label"
            :class="[
              'rounded-md border px-3 py-1.5 text-sm transition',
              link.active
                ? 'border-brand-600 bg-brand-600 text-white'
                : 'border-surface-border bg-surface-0 text-surface-fg hover:bg-surface-2',
              !link.url ? 'pointer-events-none opacity-40' : '',
            ]"
            preserve-scroll
          />
        </nav>
      </div>

      <BlogSidebar
        :recent="sidebar.recent"
        :popular="sidebar.popular"
        :categories="sidebar.categories"
        :archives="sidebar.archives"
      />
    </div>
  </section>
</template>
