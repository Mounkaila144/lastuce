<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Button from '@/components/ui/Button.vue'
import RichTextEditor from '@/components/ui/RichTextEditor.vue'
import MediaUploader from '@/components/ui/MediaUploader.vue'

defineOptions({ layout: AdminLayout })

interface ArticleData {
  id?: number
  titre: string
  slug?: string | null
  contenu?: string | null
  extrait?: string | null
  meta_description?: string | null
  categorie?: string | null
  mots_cles?: string[]
  is_published: boolean
  date_publication?: string | null
  featured_url?: string | null
  featured_thumb_url?: string | null
  has_featured_image?: boolean
}

const props = defineProps<{
  article: ArticleData | null
}>()

const isEdit = computed(() => !!props.article?.id)

const form = useForm({
  titre: props.article?.titre ?? '',
  slug: props.article?.slug ?? '',
  contenu: props.article?.contenu ?? '',
  extrait: props.article?.extrait ?? '',
  meta_description: props.article?.meta_description ?? '',
  categorie: props.article?.categorie ?? '',
  mots_cles: [] as string[],
  is_published: props.article?.is_published ?? false,
  date_publication: props.article?.date_publication ?? '',
  featured_image: null as File | null,
  remove_featured_image: false,
})

// Initialize mots_cles from article
if (props.article?.mots_cles) {
  form.mots_cles = [...props.article.mots_cles]
}

const tagInput = ref('')

function addTag() {
  const t = tagInput.value.trim().toLowerCase()
  if (t && !form.mots_cles.includes(t)) {
    form.mots_cles.push(t)
  }
  tagInput.value = ''
}

function removeTag(tag: string) {
  const idx = form.mots_cles.indexOf(tag)
  if (idx !== -1) form.mots_cles.splice(idx, 1)
}

function handleTagKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault()
    addTag()
  }
}

function submit() {
  if (isEdit.value && props.article) {
    form
      .transform((data) => ({ ...data, _method: 'put' }))
      .post(`/admin/blog/${props.article.id}`, { forceFormData: true })
  } else {
    form.post('/admin/blog', { forceFormData: true })
  }
}
useAdminTitle(() => (isEdit.value ? 'Éditer un article' : 'Nouvel article'))
</script>

<template>
  <Head :title="isEdit ? 'Éditer un article' : 'Nouvel article'" />


  <form class="space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
      <!-- Colonne principale -->
      <div class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <Input
            v-model="form.titre"
            label="Titre *"
            required
            :error="form.errors.titre"
          />
          <Input
            v-model="form.slug"
            label="Slug"
            helper="Laisser vide pour auto-génération depuis le titre."
            :error="form.errors.slug"
          />
          <Textarea
            v-model="form.extrait"
            label="Extrait"
            :rows="3"
            helper="Résumé affiché dans les listes. Max 500 caractères."
            :error="form.errors.extrait"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <RichTextEditor
            v-model="form.contenu"
            label="Contenu *"
            :error="form.errors.contenu"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">SEO</h2>
          <Textarea
            v-model="form.meta_description"
            label="Meta description"
            :rows="2"
            helper="Max 160 caractères."
            :error="form.errors.meta_description"
          />
        </div>
      </div>

      <!-- Colonne latérale -->
      <aside class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Publication</h2>

          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input
              v-model="form.is_published"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
            />
            <span class="text-sm font-medium text-slate-700">Publié</span>
          </label>

          <Input
            v-model="form.date_publication"
            type="datetime-local"
            label="Date de publication"
            helper="Laisser vide pour publication immédiate."
            :error="form.errors.date_publication"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <MediaUploader
            v-model="form.featured_image"
            label="Image à la une"
            helper="JPEG / PNG / WebP, 5 Mo max."
            :existing-url="article?.featured_thumb_url ?? null"
            :removable="article?.has_featured_image ?? false"
            :error="form.errors.featured_image"
            @remove="form.remove_featured_image = true"
          />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Taxonomie</h2>
          <Input
            v-model="form.categorie"
            label="Catégorie"
            placeholder="ex: Cuisine, Santé…"
            :error="form.errors.categorie"
          />
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mots-clés</label>
            <div v-if="form.mots_cles.length" class="mb-2 flex flex-wrap gap-1.5">
              <span
                v-for="tag in form.mots_cles"
                :key="tag"
                class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800"
              >
                {{ tag }}
                <button type="button" class="hover:text-brand-600" @click="removeTag(tag)">×</button>
              </span>
            </div>
            <div class="flex gap-1.5">
              <input
                v-model="tagInput"
                type="text"
                placeholder="Ajouter un tag…"
                class="flex-1 rounded-md border border-slate-300 px-3 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                @keydown="handleTagKeydown"
              />
              <button
                type="button"
                class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="addTag"
              >
                +
              </button>
            </div>
            <p class="mt-1 text-xs text-slate-400">Entrée ou virgule pour ajouter.</p>
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <Button type="submit" variant="primary" :loading="form.processing" class="w-full">
            {{ isEdit ? 'Mettre à jour' : 'Créer l\'article' }}
          </Button>
          <Link
            href="/admin/blog"
            class="text-center text-sm font-medium text-slate-600 hover:text-slate-900"
          >
            Annuler
          </Link>
        </div>
      </aside>
    </div>
  </form>
</template>
