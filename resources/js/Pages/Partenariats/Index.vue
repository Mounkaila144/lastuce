<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

interface OffreItem {
  key: string
  title: string
  description: string
  bullets: string[]
}

interface AudienceItem {
  label: string
  value: string
}

interface ExampleItem {
  name: string
  sector: string
  result: string
}

defineProps<{
  offre: OffreItem[]
  audience: AudienceItem[]
  examples: ExampleItem[]
  ctaUrl: string
}>()
</script>

<template>
  <Head title="Partenariats" />

  <section class="bg-surface-1 py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
      <p class="text-xs uppercase tracking-wider text-brand-700">L'Astuce × votre marque</p>
      <h1 class="mt-2 text-4xl font-bold sm:text-5xl">Devenez partenaire de L'Astuce</h1>
      <p class="mt-4 max-w-2xl text-lg text-surface-fg-muted">
        Touchez une audience qualifiée et engagée autour des astuces du quotidien.
        Nous co-créons des formats authentiques qui valorisent votre marque sans casser
        la relation de confiance avec notre communauté.
      </p>
      <div class="mt-6">
        <Link
          :href="ctaUrl"
          class="inline-flex h-12 items-center rounded-lg bg-brand-600 px-6 text-sm font-semibold text-white hover:bg-brand-700"
        >
          Lancer une discussion
        </Link>
      </div>
    </div>
  </section>

  <section class="py-12">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
      <h2 class="text-2xl font-bold">Nos formats</h2>
      <div class="mt-6 grid gap-6 md:grid-cols-3">
        <article
          v-for="item in offre"
          :key="item.key"
          class="rounded-xl border border-surface-border bg-surface-0 p-6 shadow-sm"
        >
          <h3 class="text-lg font-semibold text-surface-fg">{{ item.title }}</h3>
          <p class="mt-2 text-sm text-surface-fg-muted">{{ item.description }}</p>
          <ul class="mt-4 space-y-1.5 text-sm text-surface-fg">
            <li v-for="bullet in item.bullets" :key="bullet" class="flex items-start gap-2">
              <span class="mt-1 inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-brand-600" />
              {{ bullet }}
            </li>
          </ul>
        </article>
      </div>
    </div>
  </section>

  <section class="bg-surface-1 py-12">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
      <h2 class="text-2xl font-bold">Notre audience</h2>
      <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="kpi in audience"
          :key="kpi.label"
          class="rounded-xl border border-surface-border bg-surface-0 p-5 text-center shadow-sm"
        >
          <dt class="text-xs uppercase tracking-wide text-surface-fg-muted">{{ kpi.label }}</dt>
          <dd class="mt-2 text-2xl font-bold text-brand-700">{{ kpi.value }}</dd>
        </div>
      </dl>
    </div>
  </section>

  <section class="py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
      <h2 class="text-2xl font-bold">Quelques exemples récents</h2>
      <ul class="mt-6 divide-y divide-surface-border rounded-xl border border-surface-border bg-surface-0 shadow-sm">
        <li v-for="ex in examples" :key="ex.name" class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="font-semibold text-surface-fg">{{ ex.name }}</p>
            <p class="text-xs text-surface-fg-muted">{{ ex.sector }}</p>
          </div>
          <p class="text-sm text-surface-fg-muted">{{ ex.result }}</p>
        </li>
      </ul>
    </div>
  </section>

  <section class="py-12">
    <div class="mx-auto max-w-3xl rounded-xl bg-brand-600 px-8 py-10 text-center text-white shadow-lg">
      <h2 class="text-2xl font-bold sm:text-3xl">Prêt à explorer un partenariat ?</h2>
      <p class="mt-2 text-brand-50">
        Décrivez-nous votre projet en quelques lignes — nous revenons vers vous sous 5 jours ouvrés.
      </p>
      <div class="mt-6">
        <Link
          :href="ctaUrl"
          class="inline-flex h-12 items-center rounded-lg bg-white px-6 text-sm font-semibold text-brand-700 hover:bg-brand-50"
        >
          Soumettre une demande
        </Link>
      </div>
    </div>
  </section>
</template>
