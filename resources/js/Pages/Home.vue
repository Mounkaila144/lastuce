<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import EpisodeCard from '@/components/domain/EpisodeCard.vue'
import VideoPlayer from '@/components/domain/VideoPlayer.vue'
import NewsletterForm from '@/components/domain/NewsletterForm.vue'
import { useUiStore } from '@/stores/ui'
import type { EpisodeListItem } from '@/types/domain'

interface BlogTeaser {
  id: number
  slug: string
  titre: string
  extrait?: string | null
  image?: string | null
  date_publication?: string | null
  url: string
}

interface Testimonial {
  name: string
  location: string
  comment: string
  rating: number
}

interface Partner {
  id: number
  nom: string
  site_web?: string | null
  logo_url?: string | null
}

interface HomeStats {
  episodes: number
  newsletter: number
  astuces: number
}

const props = defineProps<{
  featuredEpisode: EpisodeListItem | null
  latestEpisodes: EpisodeListItem[]
  latestBlogArticles: BlogTeaser[]
  partners: Partner[]
  testimonials: Testimonial[]
  stats: HomeStats
}>()

const ui = useUiStore()

const heroSubtitle = computed(() => {
  const total = props.stats.episodes
  return `${total.toLocaleString('fr-FR')} épisodes pour transformer votre quotidien.`
})

// Bandeau défilant : on duplique la liste pour un défilement continu sans
// rupture visuelle (l'animation translate de -50 %).
const marqueePartners = computed(() => [...props.partners, ...props.partners])

</script>

<template>
  <Head title="Accueil" />

  <section class="relative overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-accent-600 text-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 md:grid-cols-2 md:items-center md:py-20 lg:px-8">
      <div class="space-y-6">
        <p class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider backdrop-blur">
          L'émission Facebook LightOn
        </p>
        <h1 class="font-display text-4xl font-bold leading-tight sm:text-5xl">
          Les meilleures astuces du quotidien, en vidéo.
        </h1>
        <p class="max-w-xl text-base text-white/90">
          {{ heroSubtitle }}
        </p>
        <div class="flex flex-wrap gap-3">
          <Link
            :href="`/${ui.locale}/episodes`"
            class="inline-flex h-11 items-center rounded-lg bg-white px-5 text-sm font-semibold text-brand-700 shadow-md transition hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
          >
            {{ $t('nav.episodes') }}
          </Link>
          <Link
            :href="`/${ui.locale}/astuces/create`"
            class="inline-flex h-11 items-center rounded-lg border border-white/30 bg-transparent px-5 text-sm font-semibold text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
          >
            {{ $t('nav.submitTip') }}
          </Link>
        </div>
        <dl class="grid grid-cols-3 gap-3 pt-4 text-sm">
          <div>
            <dt class="text-white/70">Épisodes</dt>
            <dd class="text-2xl font-bold">{{ stats.episodes.toLocaleString('fr-FR') }}</dd>
          </div>
          <div>
            <dt class="text-white/70">Abonnés</dt>
            <dd class="text-2xl font-bold">{{ stats.newsletter.toLocaleString('fr-FR') }}</dd>
          </div>
          <div>
            <dt class="text-white/70">Astuces</dt>
            <dd class="text-2xl font-bold">{{ stats.astuces.toLocaleString('fr-FR') }}</dd>
          </div>
        </dl>
      </div>
      <div class="rounded-2xl bg-black/20 p-3 shadow-2xl backdrop-blur">
        <VideoPlayer
          v-if="featuredEpisode?.video_embed_url"
          :url="featuredEpisode.video_embed_url"
          :provider="featuredEpisode.video_provider"
          :thumbnail="featuredEpisode.thumbnail_url ?? featuredEpisode.video_thumbnail"
          :title="featuredEpisode.titre"
        />
        <div v-else class="aspect-video rounded-xl bg-white/10" aria-hidden="true" />
        <p v-if="featuredEpisode" class="mt-3 px-2 text-sm text-white/90">
          <span class="font-semibold">À la une — </span>
          <Link :href="featuredEpisode.url ?? `/${ui.locale}/episodes/${featuredEpisode.slug}`" class="underline-offset-2 hover:underline">
            {{ featuredEpisode.titre }}
          </Link>
        </p>
      </div>
    </div>
  </section>

  <section v-if="latestEpisodes.length" class="bg-surface-1 py-12 md:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <header class="mb-6 flex items-end justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold">Derniers épisodes</h2>
          <p class="text-sm text-surface-fg-muted">Les nouveautés à ne pas manquer.</p>
        </div>
        <Link :href="`/${ui.locale}/episodes`" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
          Tout voir →
        </Link>
      </header>
      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <EpisodeCard v-for="ep in latestEpisodes" :key="ep.id" :episode="ep" />
      </div>
    </div>
  </section>

  <section v-if="latestBlogArticles.length" class="py-12 md:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <header class="mb-6">
        <h2 class="text-2xl font-bold">Sur le blog</h2>
        <p class="text-sm text-surface-fg-muted">Les coulisses, les invités, les analyses.</p>
      </header>
      <div class="grid gap-5 md:grid-cols-3">
        <article
          v-for="article in latestBlogArticles"
          :key="article.id"
          class="group overflow-hidden rounded-xl border border-surface-border bg-surface-0 shadow-sm transition-shadow hover:shadow-lg"
        >
          <Link :href="article.url" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 rounded-xl">
            <div class="aspect-video bg-surface-2">
              <img v-if="article.image" :src="article.image" :alt="article.titre" loading="lazy" class="h-full w-full object-cover" />
            </div>
            <div class="space-y-2 p-4">
              <h3 class="line-clamp-2 text-base font-semibold group-hover:text-brand-700">{{ article.titre }}</h3>
              <p v-if="article.extrait" class="line-clamp-3 text-sm text-surface-fg-muted">{{ article.extrait }}</p>
            </div>
          </Link>
        </article>
      </div>
    </div>
  </section>

  <section v-if="partners.length" class="bg-surface-1 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="mb-6 text-center text-sm font-semibold uppercase tracking-wider text-surface-fg-muted">
        Ils nous font confiance
      </h2>
    </div>
    <div class="marquee group relative overflow-hidden">
      <ul class="marquee-track flex w-max items-center gap-12 px-6">
        <li
          v-for="(p, i) in marqueePartners"
          :key="`${p.id}-${i}`"
          class="flex shrink-0 items-center"
          :aria-hidden="i >= partners.length ? 'true' : undefined"
        >
          <component
            :is="p.site_web ? 'a' : 'div'"
            :href="p.site_web ?? undefined"
            :target="p.site_web ? '_blank' : undefined"
            rel="noopener"
            class="flex items-center"
            :title="p.nom"
          >
            <img
              v-if="p.logo_url"
              :src="p.logo_url"
              :alt="p.nom"
              loading="lazy"
              class="h-12 w-auto max-w-[160px] object-contain transition hover:scale-105"
            />
            <span v-else class="text-base font-semibold text-surface-fg-muted">{{ p.nom }}</span>
          </component>
        </li>
      </ul>
    </div>
  </section>

  <section v-if="testimonials.length" class="py-12 md:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <header class="mb-6 text-center">
        <h2 class="text-2xl font-bold">Ils en parlent</h2>
      </header>
      <div class="grid gap-5 md:grid-cols-3">
        <figure
          v-for="(t, i) in testimonials"
          :key="i"
          class="rounded-xl border border-surface-border bg-surface-0 p-5 shadow-sm"
        >
          <blockquote class="text-sm text-surface-fg">
            &ldquo;{{ t.comment }}&rdquo;
          </blockquote>
          <figcaption class="mt-3 text-xs text-surface-fg-muted">
            <span class="font-semibold text-surface-fg">{{ t.name }}</span> — {{ t.location }}
          </figcaption>
        </figure>
      </div>
    </div>
  </section>

  <section class="bg-gradient-to-r from-brand-600 to-accent-600 py-12 text-white">
    <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
      <h2 class="text-2xl font-bold">Recevez les nouvelles astuces par email</h2>
      <p class="mt-2 text-white/90">Une fois par semaine, sans spam, désabonnement en un clic.</p>
      <div class="mx-auto mt-6 max-w-md">
        <NewsletterForm source="home" />
      </div>
    </div>
  </section>
</template>

<style scoped>
.marquee-track {
  animation: marquee 30s linear infinite;
}
/* Pause au survol pour laisser le temps de cliquer un logo. */
.marquee:hover .marquee-track {
  animation-play-state: paused;
}
@keyframes marquee {
  from {
    transform: translateX(0);
  }
  to {
    /* On défile de la moitié : la liste est dupliquée, donc la boucle est invisible. */
    transform: translateX(-50%);
  }
}
@media (prefers-reduced-motion: reduce) {
  .marquee-track {
    animation: none;
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>
