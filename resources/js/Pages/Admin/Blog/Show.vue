<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'

defineOptions({ layout: AdminLayout })

interface ArticleShow {
  id: number
  titre: string
  slug: string
  contenu?: string | null
  extrait?: string | null
  meta_description?: string | null
  categorie?: string | null
  mots_cles?: string[]
  is_published: boolean
  date_publication?: string | null
  statut: string
  statut_color: string
  vues: number
  reading_time: number
  featured_url?: string | null
}

const props = defineProps<{ article: ArticleShow }>()

const statusColorClass: Record<string, string> = {
  green: 'bg-emerald-100 text-emerald-800',
  yellow: 'bg-yellow-100 text-yellow-800',
  gray: 'bg-slate-100 text-slate-700',
}

function publish() {
  router.post(`/admin/blog/${props.article.id}/publish`, {}, { preserveScroll: true })
}

function unpublish() {
  router.post(`/admin/blog/${props.article.id}/unpublish`, {}, { preserveScroll: true })
}

function destroy() {
  if (!confirm(`Supprimer l'article « ${props.article.titre} » ?`)) return
  router.delete(`/admin/blog/${props.article.id}`)
}
useAdminTitle(() => props.article.titre)
</script>

<template>
  <Head :title="article.titre" />


  <div class="space-y-6">
    <!-- Actions bar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <span
          class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
          :class="statusColorClass[article.statut_color] ?? statusColorClass.gray"
        >
          {{ article.statut }}
        </span>
        <span v-if="article.categorie" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
          {{ article.categorie }}
        </span>
        <span class="text-sm text-slate-500">
          {{ article.vues.toLocaleString('fr-FR') }} vues · {{ article.reading_time }} min de lecture
        </span>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="!article.is_published"
          type="button"
          class="inline-flex h-9 items-center rounded-md bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700"
          @click="publish"
        >
          Publier
        </button>
        <button
          v-else
          type="button"
          class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          @click="unpublish"
        >
          Dépublier
        </button>
        <Link
          :href="`/admin/blog/${article.id}/edit`"
          class="inline-flex h-9 items-center rounded-md bg-brand-600 px-3 text-sm font-semibold text-white hover:bg-brand-700"
        >
          Éditer
        </Link>
        <button
          type="button"
          class="inline-flex h-9 items-center rounded-md border border-red-200 bg-white px-3 text-sm font-semibold text-red-700 hover:bg-red-50"
          @click="destroy"
        >
          Supprimer
        </button>
      </div>
    </div>

    <!-- Featured image -->
    <div v-if="article.featured_url" class="overflow-hidden rounded-xl border border-slate-200">
      <img
        :src="article.featured_url"
        :alt="article.titre"
        class="h-64 w-full object-cover"
      />
    </div>

    <!-- Meta -->
    <div class="grid gap-4 sm:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Infos</h2>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-slate-500">Slug</dt>
            <dd class="font-mono text-slate-800">{{ article.slug }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-slate-500">Publication</dt>
            <dd class="text-slate-800">
              {{ article.date_publication
                ? new Date(article.date_publication).toLocaleString('fr-FR')
                : '—' }}
            </dd>
          </div>
        </dl>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">SEO</h2>
        <p v-if="article.meta_description" class="text-sm text-slate-700">
          {{ article.meta_description }}
        </p>
        <p v-else class="text-sm italic text-slate-400">Aucune meta description.</p>
        <div v-if="article.mots_cles?.length" class="mt-2 flex flex-wrap gap-1.5">
          <span
            v-for="tag in article.mots_cles"
            :key="tag"
            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
          >
            {{ tag }}
          </span>
        </div>
      </div>
    </div>

    <!-- Extrait -->
    <div v-if="article.extrait" class="rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Extrait</h2>
      <p class="text-sm text-slate-700">{{ article.extrait }}</p>
    </div>

    <!-- Contenu -->
    <div v-if="article.contenu" class="rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Contenu</h2>
      <div class="prose prose-sm max-w-none text-slate-800" v-html="article.contenu" />
    </div>

    <div class="flex justify-end">
      <Link href="/admin/blog" class="text-sm text-slate-500 hover:text-slate-800">
        ← Retour à la liste
      </Link>
    </div>
  </div>
</template>
