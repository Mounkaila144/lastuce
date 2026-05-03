<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

export interface AstuceCardItem {
  id: number
  titre: string
  categorie: string
  categorie_label: string
  difficulte: string
  difficulte_label: string
  temps_estime?: number | null
  extrait?: string
  auteur?: string
  date?: string | null
  url: string
}

const props = defineProps<{ astuce: AstuceCardItem }>()

const difficulteClass = computed(() => {
  return {
    facile: 'bg-emerald-100 text-emerald-800',
    moyen: 'bg-amber-100 text-amber-800',
    difficile: 'bg-red-100 text-red-800',
  }[props.astuce.difficulte] ?? 'bg-surface-2 text-surface-fg'
})

const dateText = computed(() => {
  if (!props.astuce.date) return null
  return new Date(props.astuce.date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
})
</script>

<template>
  <Link
    :href="astuce.url"
    class="group flex h-full flex-col rounded-xl border border-surface-border bg-surface-0 p-5 shadow-sm transition-shadow hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
  >
    <div class="flex items-center gap-2">
      <span class="rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-semibold text-brand-800">
        {{ astuce.categorie_label }}
      </span>
      <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', difficulteClass]">
        {{ astuce.difficulte_label }}
      </span>
      <span v-if="astuce.temps_estime" class="ml-auto text-xs text-surface-fg-muted">
        {{ astuce.temps_estime }} min
      </span>
    </div>
    <h3 class="mt-3 line-clamp-2 text-base font-semibold text-surface-fg group-hover:text-brand-700">
      {{ astuce.titre }}
    </h3>
    <p v-if="astuce.extrait" class="mt-1 line-clamp-3 flex-1 text-sm text-surface-fg-muted">
      {{ astuce.extrait }}
    </p>
    <p class="mt-3 flex items-center gap-2 text-xs text-surface-fg-muted">
      <span v-if="astuce.auteur">par {{ astuce.auteur }}</span>
      <span v-if="astuce.auteur && dateText" aria-hidden="true">·</span>
      <time v-if="dateText" :datetime="astuce.date ?? undefined">{{ dateText }}</time>
    </p>
  </Link>
</template>
