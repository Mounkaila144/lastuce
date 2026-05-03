<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import BlogArticleCard from '@/components/domain/BlogArticleCard.vue'
import BlogSidebar from '@/components/domain/BlogSidebar.vue'
import ShareButtons from '@/components/domain/ShareButtons.vue'
import { useUiStore } from '@/stores/ui'
import type { BlogArticleListItem, BlogArticleShow } from '@/types/domain'

interface Sidebar {
  recent: { slug: string; titre: string; date_publication?: string | null }[]
  popular: { slug: string; titre: string; vues: number }[]
  categories: { slug: string; name: string; count: number }[]
  archives: { value: string; year: number; month: number; count: number }[]
}

interface Seo {
  title: string
  description?: string | null
  image?: string | null
  published_time?: string | null
  modified_time?: string | null
  canonical: string
}

const props = defineProps<{
  article: BlogArticleShow
  related: { data: BlogArticleListItem[] }
  previous: { slug: string; titre: string } | null
  next: { slug: string; titre: string } | null
  seo: Seo
  sidebar: Sidebar
}>()

const ui = useUiStore()

const fullUrl = computed(() => {
  if (typeof window !== 'undefined') return window.location.href
  return props.article.url
})

const formattedDate = computed(() => {
  if (!props.article.date_publication) return null
  return new Date(props.article.date_publication).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
})

const articleSchema = computed(() =>
  JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: props.seo.title,
    description: props.seo.description,
    image: props.seo.image,
    datePublished: props.seo.published_time,
    dateModified: props.seo.modified_time,
    author: { '@type': 'Organization', name: "L'Astuce" },
    publisher: { '@type': 'Organization', name: "L'Astuce" },
    mainEntityOfPage: props.seo.canonical,
  }),
)

const related = computed(() => props.related.data)
</script>

<template>
  <Head>
    <title>{{ seo.title }}</title>
    <meta name="description" :content="seo.description ?? ''" />
    <link rel="canonical" :href="seo.canonical" />
    <meta property="og:title" :content="seo.title" />
    <meta property="og:description" :content="seo.description ?? ''" />
    <meta property="og:type" content="article" />
    <meta v-if="seo.image" property="og:image" :content="seo.image" />
    <meta v-if="seo.published_time" property="article:published_time" :content="seo.published_time" />
    <meta v-if="seo.modified_time" property="article:modified_time" :content="seo.modified_time" />
    <meta name="twitter:card" content="summary_large_image" />
    <component :is="'script'" type="application/ld+json" v-html="articleSchema" />
  </Head>

  <article class="bg-surface-1 py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
      <nav class="mb-3 text-xs text-surface-fg-muted" aria-label="Fil d'Ariane">
        <Link :href="`/${ui.locale}`" class="hover:text-brand-700">{{ $t('nav.home') }}</Link>
        <span class="mx-1" aria-hidden="true">/</span>
        <Link :href="`/${ui.locale}/blog`" class="hover:text-brand-700">Blog</Link>
        <span class="mx-1" aria-hidden="true">/</span>
        <span class="text-surface-fg">{{ article.titre }}</span>
      </nav>

      <div v-if="article.image" class="mb-6 overflow-hidden rounded-xl bg-surface-2">
        <img
          :src="article.image"
          :alt="article.titre"
          class="aspect-video w-full object-cover"
          loading="eager"
        />
      </div>

      <header class="space-y-3">
        <div class="flex flex-wrap items-center gap-2 text-xs">
          <Link
            v-if="article.categorie"
            :href="`/${ui.locale}/blog/category/${article.categorie}`"
            class="rounded-full bg-brand-100 px-2.5 py-0.5 font-semibold text-brand-800 hover:bg-brand-200"
          >
            {{ article.categorie }}
          </Link>
          <span v-if="formattedDate" class="text-surface-fg-muted">{{ formattedDate }}</span>
          <span class="text-surface-fg-muted">· {{ article.reading_time }} min de lecture</span>
          <span class="text-surface-fg-muted">· {{ article.vues.toLocaleString('fr-FR') }} vues</span>
        </div>
        <h1 class="text-3xl font-bold leading-tight md:text-4xl">{{ article.titre }}</h1>
        <p v-if="article.extrait" class="text-base text-surface-fg-muted">{{ article.extrait }}</p>
      </header>

      <div v-if="article.mots_cles.length" class="mt-4 flex flex-wrap gap-2">
        <Link
          v-for="tag in article.mots_cles"
          :key="tag"
          :href="`/${ui.locale}/blog/tag/${tag}`"
          class="rounded-full border border-surface-border bg-surface-0 px-3 py-1 text-xs text-surface-fg hover:bg-surface-2"
        >
          #{{ tag }}
        </Link>
      </div>

      <div class="mt-5">
        <ShareButtons :url="fullUrl" :title="article.titre" />
      </div>
    </div>
  </article>

  <section class="py-10">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:px-8">
      <div
        class="prose prose-base max-w-none text-surface-fg prose-headings:font-bold prose-a:text-brand-700"
        v-html="article.contenu"
      />

      <BlogSidebar
        :recent="sidebar.recent"
        :popular="sidebar.popular"
        :categories="sidebar.categories"
        :archives="sidebar.archives"
      />
    </div>
  </section>

  <nav
    v-if="previous || next"
    class="border-t border-surface-border bg-surface-1 py-6"
    aria-label="Navigation entre articles"
  >
    <div class="mx-auto flex max-w-5xl items-stretch justify-between gap-4 px-4 sm:px-6 lg:px-8">
      <Link
        v-if="previous"
        :href="`/${ui.locale}/blog/${previous.slug}`"
        class="group flex-1 rounded-lg border border-surface-border bg-surface-0 p-4 transition hover:bg-surface-2"
      >
        <span class="text-xs uppercase tracking-wide text-surface-fg-muted">← Précédent</span>
        <p class="mt-1 line-clamp-2 text-sm font-semibold group-hover:text-brand-700">
          {{ previous.titre }}
        </p>
      </Link>
      <span v-else class="flex-1" />
      <Link
        v-if="next"
        :href="`/${ui.locale}/blog/${next.slug}`"
        class="group flex-1 rounded-lg border border-surface-border bg-surface-0 p-4 text-right transition hover:bg-surface-2"
      >
        <span class="text-xs uppercase tracking-wide text-surface-fg-muted">Suivant →</span>
        <p class="mt-1 line-clamp-2 text-sm font-semibold group-hover:text-brand-700">
          {{ next.titre }}
        </p>
      </Link>
    </div>
  </nav>

  <section v-if="related.length" class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="mb-5 text-xl font-bold">À lire également</h2>
      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <BlogArticleCard v-for="art in related" :key="art.id" :article="art" />
      </div>
    </div>
  </section>
</template>
