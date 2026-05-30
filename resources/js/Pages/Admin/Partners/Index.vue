<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import { useDebounce } from '@/composables/useDebounce'

defineOptions({ layout: AdminLayout })

interface PartnerRow {
  id: number
  nom: string
  site_web: string | null
  is_visible: boolean
  ordre: number
  logo_url: string | null
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
  partners: Paginated<PartnerRow>
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

function destroy(partner: PartnerRow) {
  if (!confirm(`Supprimer le partenaire « ${partner.nom} » ?`)) return
  router.delete(`/admin/partners/${partner.id}`)
}

useAdminTitle('Partenaires')
</script>

<template>
  <Head title="Partenaires" />

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
        <div class="text-xs text-slate-500">Masqués</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-end gap-3">
      <Input v-model="search" placeholder="Rechercher…" class="w-56" type="search" />
      <Link
        href="/admin/partners/create"
        class="ml-auto inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
      >
        + Ajouter un partenaire
      </Link>
    </div>

    <!-- Grid -->
    <div v-if="partners.data.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="partner in partners.data"
        :key="partner.id"
        class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4"
      >
        <div class="flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-100 bg-slate-50">
          <img
            v-if="partner.logo_url"
            :src="partner.logo_url"
            :alt="partner.nom"
            class="max-h-full max-w-full object-contain"
          />
          <span v-else class="text-xs text-slate-400">Pas de logo</span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-slate-900">
            {{ partner.nom }}
            <span v-if="!partner.is_visible" class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-500">masqué</span>
          </p>
          <a v-if="partner.site_web" :href="partner.site_web" target="_blank" rel="noopener" class="truncate text-xs text-brand-600 hover:underline">
            {{ partner.site_web }}
          </a>
          <div class="mt-2 flex gap-1">
            <Link :href="partner.edit_url" class="rounded px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50">Éditer</Link>
            <button type="button" class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="destroy(partner)">Suppr.</button>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center text-slate-400">
      Aucun partenaire. Cliquez sur « Ajouter un partenaire » pour commencer.
    </div>

    <!-- Pagination -->
    <div v-if="partners.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>Page {{ partners.current_page }} / {{ partners.last_page }} — {{ partners.total }} partenaire(s)</span>
      <div class="flex gap-1">
        <template v-for="link in partners.links" :key="link.label">
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
