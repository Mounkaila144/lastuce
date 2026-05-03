<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { useUiStore } from '@/stores/ui'

interface PartStatus {
  id: number
  nom_entreprise: string
  status: 'nouveau' | 'en_cours' | 'accepte' | 'refuse'
  status_label: string
  created_at?: string | null
  updated_at?: string | null
}

const props = defineProps<{ partenariat: PartStatus }>()
const ui = useUiStore()

const visual = computed(() => ({
  nouveau: { color: 'bg-blue-100 text-blue-800 border-blue-200', icon: '✉', message: 'Demande reçue, en attente d\'analyse.' },
  en_cours: { color: 'bg-amber-100 text-amber-800 border-amber-200', icon: '⏳', message: 'Notre équipe étudie actuellement votre proposition.' },
  accepte: { color: 'bg-emerald-100 text-emerald-800 border-emerald-200', icon: '✓', message: 'Demande acceptée — vous serez recontacté·e.' },
  refuse: { color: 'bg-red-100 text-red-800 border-red-200', icon: '×', message: 'Demande non retenue cette fois.' },
}[props.partenariat.status]))
</script>

<template>
  <Head :title="`Suivi partenariat #${partenariat.id}`" />

  <section class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
      <p class="text-xs uppercase tracking-wider text-surface-fg-muted">Suivi de demande</p>
      <h1 class="mt-1 text-3xl font-bold">{{ partenariat.nom_entreprise }}</h1>
      <p class="mt-1 text-sm text-surface-fg-muted">Référence #{{ partenariat.id }}</p>

      <div :class="['mt-6 flex items-start gap-4 rounded-xl border p-5', visual.color]">
        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-white text-xl font-bold">
          {{ visual.icon }}
        </span>
        <div>
          <p class="text-base font-semibold">{{ partenariat.status_label }}</p>
          <p class="mt-1 text-sm">{{ visual.message }}</p>
        </div>
      </div>

      <div class="mt-8">
        <Link
          :href="`/${ui.locale}/partenariats`"
          class="inline-flex h-10 items-center rounded-lg border border-surface-border bg-surface-0 px-5 text-sm font-semibold text-surface-fg hover:bg-surface-2"
        >
          Retour à la page partenariats
        </Link>
      </div>
    </div>
  </section>
</template>
