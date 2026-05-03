<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import Textarea from '@/components/ui/Textarea.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import { useUiStore } from '@/stores/ui'

const props = defineProps<{
  email: string
  token: string
  alreadyDone: boolean
}>()

const ui = useUiStore()

const reasonOptions = [
  { value: '', label: 'Choisir (optionnel)' },
  { value: 'trop_emails', label: 'Trop d\'emails' },
  { value: 'pas_pertinent', label: 'Le contenu ne correspond plus à mes attentes' },
  { value: 'autre_email', label: 'J\'utilise une autre adresse' },
  { value: 'jamais_inscrit', label: 'Je ne me souviens pas de m\'être inscrit·e' },
  { value: 'autre', label: 'Autre raison' },
]

const form = useForm({
  raison: '',
  commentaire: '',
})

function submit() {
  form.post(`/${ui.locale}/newsletter/unsubscribe/${props.token}`, {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Se désabonner" />

  <section class="py-16">
    <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold">Se désabonner de la newsletter</h1>

      <div v-if="alreadyDone" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-800">
        L'adresse <strong>{{ email }}</strong> est déjà désabonnée. Vous ne recevrez plus rien de notre part.
      </div>

      <form v-else class="mt-6 space-y-5 rounded-xl border border-surface-border bg-surface-0 p-6 shadow-sm" novalidate @submit.prevent="submit">
        <p class="text-sm text-surface-fg-muted">
          Vous êtes sur le point de désabonner <strong class="text-surface-fg">{{ email }}</strong>.
          Aidez-nous à comprendre la raison (facultatif).
        </p>

        <Select
          v-model="form.raison"
          :options="reasonOptions"
          label="Raison du désabonnement"
          :error="form.errors.raison"
        />

        <Textarea
          v-model="form.commentaire"
          label="Commentaire (optionnel)"
          :rows="3"
          placeholder="Dites-nous ce que nous pouvons améliorer."
          :error="form.errors.commentaire"
        />

        <div class="flex justify-end gap-3 border-t border-surface-border pt-5">
          <Button variant="danger" type="submit" :loading="form.processing">Confirmer le désabonnement</Button>
        </div>
      </form>
    </div>
  </section>
</template>
