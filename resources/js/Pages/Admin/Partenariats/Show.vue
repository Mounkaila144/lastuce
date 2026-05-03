<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'

defineOptions({ layout: AdminLayout })

interface Partenariat {
  id: number
  nom_entreprise: string
  contact: string
  email: string
  telephone?: string | null
  site_web?: string | null
  type_partenariat?: string | null
  budget_envisage?: string | null
  message: string
  status: string
  notes_internes?: string | null
  ip_demandeur?: string | null
  created_at: string
  updated_at: string
}

const props = defineProps<{ partenariat: Partenariat }>()

const statusColors: Record<string, string> = {
  nouveau: 'bg-blue-100 text-blue-800',
  en_cours: 'bg-yellow-100 text-yellow-800',
  accepte: 'bg-emerald-100 text-emerald-800',
  refuse: 'bg-red-100 text-red-800',
}
const statusLabels: Record<string, string> = {
  nouveau: 'Nouveau', en_cours: 'En cours', accepte: 'Accepté', refuse: 'Refusé',
}

function setStatus(status: 'en_cours' | 'accepte' | 'refuse') {
  const endpoint = status === 'accepte' ? 'approve' : status === 'refuse' ? 'reject' : null
  if (endpoint) {
    router.post(`/admin/partenariats/${props.partenariat.id}/${endpoint}`, {}, { preserveScroll: true })
  } else {
    router.patch(`/admin/partenariats/${props.partenariat.id}`, { status }, { preserveScroll: true })
  }
}

function destroy() {
  if (!confirm(`Supprimer le partenariat « ${props.partenariat.nom_entreprise} » ?`)) return
  router.delete(`/admin/partenariats/${props.partenariat.id}`)
}
useAdminTitle(() => props.partenariat.nom_entreprise)
</script>

<template>
  <Head :title="partenariat.nom_entreprise" />

  <div class="space-y-6">
    <!-- Actions bar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <span
        class="rounded-full px-3 py-1 text-sm font-semibold"
        :class="statusColors[partenariat.status] ?? 'bg-slate-100 text-slate-700'"
      >
        {{ statusLabels[partenariat.status] ?? partenariat.status }}
      </span>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="partenariat.status !== 'en_cours'"
          type="button"
          class="inline-flex h-9 items-center rounded-md bg-yellow-500 px-3 text-sm font-semibold text-white hover:bg-yellow-600"
          @click="setStatus('en_cours')"
        >En cours</button>
        <button
          v-if="partenariat.status !== 'accepte'"
          type="button"
          class="inline-flex h-9 items-center rounded-md bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700"
          @click="setStatus('accepte')"
        >Accepter</button>
        <button
          v-if="partenariat.status !== 'refuse'"
          type="button"
          class="inline-flex h-9 items-center rounded-md border border-red-200 bg-white px-3 text-sm font-semibold text-red-700 hover:bg-red-50"
          @click="setStatus('refuse')"
        >Refuser</button>
        <Link
          :href="`/admin/partenariats/${partenariat.id}/edit`"
          class="inline-flex h-9 items-center rounded-md bg-brand-600 px-3 text-sm font-semibold text-white hover:bg-brand-700"
        >Éditer</Link>
        <button
          type="button"
          class="inline-flex h-9 items-center rounded-md border border-red-200 bg-white px-3 text-sm font-semibold text-red-700 hover:bg-red-50"
          @click="destroy"
        >Supprimer</button>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <!-- Infos entreprise -->
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Entreprise</h2>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-slate-500">Contact</dt>
            <dd class="text-slate-800">{{ partenariat.contact }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-slate-500">Email</dt>
            <dd><a :href="`mailto:${partenariat.email}`" class="text-brand-600 hover:underline">{{ partenariat.email }}</a></dd>
          </div>
          <div v-if="partenariat.telephone" class="flex justify-between">
            <dt class="text-slate-500">Téléphone</dt>
            <dd class="text-slate-800">{{ partenariat.telephone }}</dd>
          </div>
          <div v-if="partenariat.site_web" class="flex justify-between">
            <dt class="text-slate-500">Site web</dt>
            <dd><a :href="partenariat.site_web" target="_blank" rel="noopener" class="text-brand-600 hover:underline">{{ partenariat.site_web }}</a></dd>
          </div>
          <div v-if="partenariat.type_partenariat" class="flex justify-between">
            <dt class="text-slate-500">Type</dt>
            <dd class="text-slate-800">{{ partenariat.type_partenariat }}</dd>
          </div>
          <div v-if="partenariat.budget_envisage" class="flex justify-between">
            <dt class="text-slate-500">Budget</dt>
            <dd class="text-slate-800">{{ partenariat.budget_envisage }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-slate-500">Reçu le</dt>
            <dd class="text-slate-800">{{ new Date(partenariat.created_at).toLocaleString('fr-FR') }}</dd>
          </div>
        </dl>
      </div>

      <!-- Notes internes -->
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Notes internes</h2>
        <p v-if="partenariat.notes_internes" class="whitespace-pre-line text-sm text-slate-700">
          {{ partenariat.notes_internes }}
        </p>
        <p v-else class="text-sm italic text-slate-400">Aucune note.</p>
        <Link
          :href="`/admin/partenariats/${partenariat.id}/edit`"
          class="mt-3 inline-flex text-xs text-brand-600 hover:underline"
        >Éditer les notes →</Link>
      </div>
    </div>

    <!-- Message -->
    <div class="rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Message</h2>
      <p class="whitespace-pre-line text-sm text-slate-700">{{ partenariat.message }}</p>
    </div>

    <div class="flex justify-end">
      <Link href="/admin/partenariats" class="text-sm text-slate-500 hover:text-slate-800">← Retour à la liste</Link>
    </div>
  </div>
</template>
