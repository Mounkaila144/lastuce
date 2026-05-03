<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useUiStore } from '@/stores/ui'

defineProps<{ partenariatId: number | null }>()

const ui = useUiStore()
</script>

<template>
  <Head title="Demande envoyée" />

  <section class="py-16">
    <div class="mx-auto max-w-2xl px-4 text-center sm:px-6 lg:px-8">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
        <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4L8.5 12l6.8-6.7a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
        </svg>
      </div>
      <h1 class="mt-4 text-3xl font-bold">Merci, c'est envoyé !</h1>
      <p class="mt-2 text-surface-fg-muted">
        Notre équipe étudie votre demande et reviendra vers vous sous 5 jours ouvrés.
      </p>
      <p v-if="partenariatId" class="mt-3 text-sm">
        Référence de suivi : <span class="font-mono font-semibold">#{{ partenariatId }}</span>
      </p>

      <div class="mt-6 flex flex-wrap justify-center gap-3">
        <Link
          :href="`/${ui.locale}/episodes`"
          class="inline-flex h-11 items-center rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700"
        >
          Découvrir les épisodes
        </Link>
        <Link
          :href="`/${ui.locale}/partenariats`"
          class="inline-flex h-11 items-center rounded-lg border border-surface-border bg-surface-0 px-5 text-sm font-semibold text-surface-fg hover:bg-surface-2"
        >
          Retour à la page partenariats
        </Link>
      </div>
    </div>
  </section>
</template>
