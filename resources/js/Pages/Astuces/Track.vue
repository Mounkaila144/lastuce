<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { useUiStore } from '@/stores/ui'

interface AstuceStatus {
  id: number
  titre: string
  status: 'en_attente' | 'approuve' | 'rejete'
  status_label: string
  commentaire_admin?: string | null
  created_at?: string | null
  updated_at?: string | null
}

const props = defineProps<{ astuce: AstuceStatus }>()
const ui = useUiStore()

const statusVisual = computed(() => {
  return {
    en_attente: { color: 'bg-amber-100 text-amber-800 border-amber-200', icon: '⏳', message: 'Votre astuce est en cours de modération.' },
    approuve: { color: 'bg-emerald-100 text-emerald-800 border-emerald-200', icon: '✓', message: 'Votre astuce a été approuvée et publiée !' },
    rejete: { color: 'bg-red-100 text-red-800 border-red-200', icon: '×', message: 'Votre astuce n\'a pas été retenue cette fois.' },
  }[props.astuce.status] ?? {
    color: 'bg-surface-2 text-surface-fg border-surface-border',
    icon: '?',
    message: '',
  }
})

const dateText = computed(() => {
  const iso = props.astuce.updated_at ?? props.astuce.created_at
  if (!iso) return null
  return new Date(iso).toLocaleString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
})
</script>

<template>
  <Head :title="`Suivi astuce #${astuce.id}`" />

  <section class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
      <p class="text-xs uppercase tracking-wider text-surface-fg-muted">Suivi de soumission</p>
      <h1 class="mt-1 text-3xl font-bold">Astuce #{{ astuce.id }}</h1>
      <p class="mt-1 text-base text-surface-fg-muted">{{ astuce.titre }}</p>

      <div :class="['mt-6 flex items-start gap-4 rounded-xl border p-5', statusVisual.color]">
        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-white text-xl font-bold">
          {{ statusVisual.icon }}
        </span>
        <div class="flex-1">
          <p class="text-base font-semibold">{{ astuce.status_label }}</p>
          <p class="mt-1 text-sm">{{ statusVisual.message }}</p>
          <p v-if="dateText" class="mt-2 text-xs opacity-80">Mise à jour le {{ dateText }}</p>
        </div>
      </div>

      <div v-if="astuce.commentaire_admin" class="mt-6 rounded-xl border border-surface-border bg-surface-1 p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-surface-fg-muted">Commentaire de la modération</h2>
        <p class="mt-2 whitespace-pre-line text-sm text-surface-fg">{{ astuce.commentaire_admin }}</p>
      </div>

      <div class="mt-8 flex flex-wrap gap-3">
        <Link
          v-if="astuce.status === 'approuve'"
          :href="`/${ui.locale}/astuces/${astuce.id}`"
          class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700"
        >
          Voir mon astuce publiée
        </Link>
        <Link
          :href="`/${ui.locale}/astuces/create`"
          class="inline-flex h-10 items-center rounded-lg border border-surface-border bg-surface-0 px-5 text-sm font-semibold text-surface-fg hover:bg-surface-2"
        >
          Soumettre une autre astuce
        </Link>
      </div>
    </div>
  </section>
</template>
