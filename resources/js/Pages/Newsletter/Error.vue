<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useUiStore } from '@/stores/ui'

defineProps<{ reason: string }>()

const ui = useUiStore()
</script>

<template>
  <Head title="Lien invalide" />

  <section class="py-16">
    <div class="mx-auto max-w-2xl px-4 text-center sm:px-6 lg:px-8">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-700">
        <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-1-9a1 1 0 0 0-1 1v4a1 1 0 1 0 2 0V6a1 1 0 0 0-1-1Z" clip-rule="evenodd" />
        </svg>
      </div>
      <h1 class="mt-4 text-3xl font-bold">Lien invalide ou expiré</h1>
      <p class="mt-2 text-surface-fg-muted">
        Le lien que vous avez suivi n'est plus valide. Il a pu être régénéré ou avoir déjà été utilisé.
      </p>

      <div class="mt-6">
        <Link
          :href="`/${ui.locale}/newsletter`"
          class="inline-flex h-11 items-center rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700"
        >
          Recommencer l'inscription
        </Link>
      </div>
    </div>
  </section>
</template>
