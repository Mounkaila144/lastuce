<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useUiStore } from '@/stores/ui'

defineProps<{ email?: string | null }>()

const ui = useUiStore()
</script>

<template>
  <Head title="Inscription envoyée" />

  <section class="py-16">
    <div class="mx-auto max-w-2xl px-4 text-center sm:px-6 lg:px-8">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
        <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M2.94 6.41A2 2 0 0 1 4 6h12a2 2 0 0 1 1.06.41l-7.06 4.41-7.06-4.41Z" clip-rule="evenodd" />
          <path d="M18 8.12 10 13l-8-4.88V14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.12Z" />
        </svg>
      </div>
      <h1 class="mt-4 text-3xl font-bold">Vérifiez votre boîte mail</h1>
      <p class="mt-2 text-surface-fg-muted">
        Un email de confirmation a été envoyé
        <span v-if="email">à <strong class="text-surface-fg">{{ email }}</strong></span>.
        Cliquez sur le lien à l'intérieur pour valider votre inscription.
      </p>
      <p class="mt-3 text-sm text-surface-fg-muted">
        Pas d'email reçu après quelques minutes ? Vérifiez vos courriers indésirables ou
        <Link :href="`/${ui.locale}/newsletter`" class="font-medium text-brand-700 underline">recommencez l'inscription</Link>.
      </p>

      <div class="mt-8 flex flex-wrap justify-center gap-3">
        <Link
          :href="`/${ui.locale}/episodes`"
          class="inline-flex h-11 items-center rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700"
        >
          Découvrir les épisodes
        </Link>
        <Link
          :href="`/${ui.locale}/blog`"
          class="inline-flex h-11 items-center rounded-lg border border-surface-border bg-surface-0 px-5 text-sm font-semibold text-surface-fg hover:bg-surface-2"
        >
          Lire le blog
        </Link>
      </div>
    </div>
  </section>
</template>
