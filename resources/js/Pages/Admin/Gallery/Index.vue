<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import { useDebounce } from '@/composables/useDebounce'

defineOptions({ layout: AdminLayout })

interface ImageRow {
  id: number
  titre: string | null
  description: string | null
  is_visible: boolean
  ordre: number
  thumb_url: string | null
  created_at: string
  edit_url: string
}

interface Paginated<T> {
  data: T[]
  links: { url: string | null; label: string; active: boolean }[]
  current_page: number
  last_page: number
  total: number
  per_page: number
}

const props = defineProps<{
  images: Paginated<ImageRow>
  filters: { search: string }
  stats: { total: number; visible: number; hidden: number }
}>()

const filters = reactive({ ...props.filters })
const search = ref(filters.search)
const debouncedSearch = useDebounce(search, 300)

watch(debouncedSearch, (v) => {
  filters.search = v
  router.get(window.location.pathname, v ? { search: v } : {}, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  })
})

function destroy(image: ImageRow) {
  if (!confirm(`Supprimer cette image « ${image.titre ?? 'sans titre'} » ?`)) return
  router.delete(`/admin/gallery/${image.id}`)
}

useAdminTitle('Galerie')
</script>

<template>
  <Head title="Galerie" />

  <div class="space-y-5">
    <!-- Stats -->
    <div class="grid grid-cols-3 gap-3">
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-900">{{ stats.total }}</div>
        <div class="text-xs text-slate-500">Total</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-emerald-600">{{ stats.visible }}</div>
        <div class="text-xs text-slate-500">Visibles</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
        <div class="text-2xl font-bold text-slate-400">{{ stats.hidden }}</div>
        <div class="text-xs text-slate-500">Masquées</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-end gap-3">
      <Input v-model="search" placeholder="Rechercher…" class="w-56" type="search" />
      <Link
        href="/admin/gallery/create"
        class="ml-auto inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
      >
        + Ajouter une image
      </Link>
    </div>

    <!-- Grid -->
    <div v-if="images.data.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <div
        v-for="image in images.data"
        :key="image.id"
        class="group overflow-hidden rounded-xl border border-slate-200 bg-white"
      >
        <div class="relative aspect-square bg-slate-100">
          <img
            v-if="image.thumb_url"
            :src="image.thumb_url"
            :alt="image.titre ?? ''"
            class="h-full w-full object-cover"
          />
          <div v-else class="flex h-full items-center justify-center text-xs text-slate-400">
            Aucune image
          </div>
          <span
            v-if="!image.is_visible"
            class="absolute left-2 top-2 rounded-full bg-slate-900/70 px-2 py-0.5 text-xs font-semibold text-white"
          >
            Masquée
          </span>
        </div>
        <div class="p-3">
          <p class="truncate text-sm font-medium text-slate-900">{{ image.titre ?? 'Sans titre' }}</p>
          <div class="mt-2 flex justify-end gap-1">
            <Link :href="image.edit_url" class="rounded px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50">Éditer</Link>
            <button type="button" class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="destroy(image)">Suppr.</button>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center text-slate-400">
      Aucune image. Cliquez sur « Ajouter une image » pour commencer.
    </div>

    <!-- Pagination -->
    <div v-if="images.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>Page {{ images.current_page }} / {{ images.last_page }} — {{ images.total }} image(s)</span>
      <div class="flex gap-1">
        <template v-for="link in images.links" :key="link.label">
          <component
            :is="link.url ? Link : 'span'"
            :href="link.url ?? undefined"
            class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded border px-2 text-xs"
            :class="link.active ? 'border-brand-600 bg-brand-600 font-semibold text-white' : link.url ? 'border-slate-200 bg-white hover:bg-slate-50' : 'cursor-default border-slate-100 bg-white text-slate-300'"
            v-html="link.label"
          />
        </template>
      </div>
    </div>
  </div>
</template>
