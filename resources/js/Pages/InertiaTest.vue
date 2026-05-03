<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

defineProps<{
  message: string
  serverTime: string
}>()
</script>

<template>
  <div>
    <Head title="Inertia Test" />
    <section class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
      <p class="text-xs font-medium uppercase tracking-wider text-astuce-600">
        Stack check — S1.1
      </p>
      <h1 class="mt-2 text-3xl font-bold tracking-tight">
        {{ message }}
      </h1>
      <p class="mt-2 text-sm text-gray-500">
        Inertia + Vue 3 + TypeScript fonctionnent. Serveur :
        <span class="font-mono">{{ serverTime }}</span>
      </p>
      <a
        href="/"
        class="mt-6 inline-flex items-center rounded-lg bg-astuce-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-astuce-700"
      >
        Retour
      </a>
    </section>
  </div>
</template>
