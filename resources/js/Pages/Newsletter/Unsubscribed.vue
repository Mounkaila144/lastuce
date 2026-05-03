<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useUiStore } from '@/stores/ui'

defineProps<{ email: string }>()

const ui = useUiStore()
</script>

<template>
  <Head title="Désabonnement confirmé" />

  <section class="py-16">
    <div class="mx-auto max-w-2xl px-4 text-center sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold">À bientôt !</h1>
      <p class="mt-2 text-surface-fg-muted">
        L'adresse <strong class="text-surface-fg">{{ email }}</strong> a été désabonnée. Vous ne recevrez plus la newsletter.
      </p>
      <p class="mt-3 text-sm text-surface-fg-muted">
        Si c'était une erreur, vous pouvez
        <Link :href="`/${ui.locale}/newsletter`" class="font-medium text-brand-700 underline">vous réinscrire</Link>.
      </p>

      <div class="mt-8 flex flex-wrap justify-center gap-3">
        <Link
          :href="`/${ui.locale}/episodes`"
          class="inline-flex h-11 items-center rounded-lg border border-surface-border bg-surface-0 px-5 text-sm font-semibold text-surface-fg hover:bg-surface-2"
        >
          Continuer la visite
        </Link>
      </div>
    </div>
  </section>
</template>
