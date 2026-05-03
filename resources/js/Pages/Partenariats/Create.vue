<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import Input from '@/components/ui/Input.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import { useUiStore } from '@/stores/ui'
import { useToast } from '@/composables/useToast'
import type { SelectOption } from '@/components/ui/Select.vue'

interface Options {
  types: SelectOption[]
  budgets: SelectOption[]
}

defineProps<{ options: Options }>()

const ui = useUiStore()
const toast = useToast()

const form = useForm({
  nom_entreprise: '',
  contact: '',
  email: '',
  telephone: '',
  site_web: '',
  type_partenariat: '',
  budget_envisage: '',
  message: '',
  cgv: false,
  website: '',
})

function submit() {
  form.post(`/${ui.locale}/partenariats`, {
    preserveScroll: true,
    onError: () => toast.error('Le formulaire contient des erreurs.'),
  })
}
</script>

<template>
  <Head title="Demande de partenariat" />

  <section class="bg-surface-1 py-10">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold">Soumettre une demande de partenariat</h1>
      <p class="mt-2 text-surface-fg-muted">
        Présentez-nous votre projet — nous étudions chaque demande et revenons vers vous sous 5 jours ouvrés.
      </p>
    </div>
  </section>

  <form class="py-10" novalidate @submit.prevent="submit">
    <div class="mx-auto max-w-3xl space-y-6 rounded-xl border border-surface-border bg-surface-0 p-6 shadow-sm sm:px-6 lg:px-8">
      <div class="absolute -left-[10000px]" aria-hidden="true">
        <label>Site web (laisser vide)
          <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
        </label>
      </div>

      <fieldset class="space-y-4">
        <legend class="text-lg font-semibold text-surface-fg">Votre entreprise</legend>
        <Input
          v-model="form.nom_entreprise"
          label="Nom de l'entreprise"
          required
          autocomplete="organization"
          :error="form.errors.nom_entreprise"
        />
        <Input
          v-model="form.site_web"
          type="url"
          label="Site web (optionnel)"
          placeholder="https://"
          autocomplete="url"
          :error="form.errors.site_web"
        />
      </fieldset>

      <fieldset class="space-y-4">
        <legend class="text-lg font-semibold text-surface-fg">Votre contact</legend>
        <div class="grid gap-4 sm:grid-cols-2">
          <Input
            v-model="form.contact"
            label="Nom du contact"
            required
            autocomplete="name"
            :error="form.errors.contact"
          />
          <Input
            v-model="form.telephone"
            label="Téléphone (optionnel)"
            autocomplete="tel"
            :error="form.errors.telephone"
          />
        </div>
        <Input
          v-model="form.email"
          type="email"
          label="Email"
          required
          autocomplete="email"
          :error="form.errors.email"
        />
      </fieldset>

      <fieldset class="space-y-4">
        <legend class="text-lg font-semibold text-surface-fg">Votre projet</legend>
        <div class="grid gap-4 sm:grid-cols-2">
          <Select
            v-model="form.type_partenariat"
            :options="options.types"
            label="Type de partenariat"
            placeholder="Choisir…"
            required
            :error="form.errors.type_partenariat"
          />
          <Select
            v-model="form.budget_envisage"
            :options="options.budgets"
            label="Budget envisagé"
            placeholder="Choisir…"
            required
            :error="form.errors.budget_envisage"
          />
        </div>
        <Textarea
          v-model="form.message"
          label="Décrivez votre projet"
          required
          :rows="6"
          helper="Objectifs, audience cible, calendrier, formats envisagés…"
          :error="form.errors.message"
        />
      </fieldset>

      <label class="flex items-start gap-3">
        <input v-model="form.cgv" type="checkbox" class="mt-1 h-4 w-4 rounded border-surface-border text-brand-600 focus:ring-brand-500" />
        <span class="text-sm text-surface-fg">
          J'accepte d'être recontacté·e par L'Astuce à propos de cette demande.
        </span>
      </label>
      <p v-if="form.errors.cgv" class="text-xs text-red-600" role="alert">{{ form.errors.cgv }}</p>
      <p v-if="(form.errors as Record<string, string>).throttle" class="text-xs text-red-600" role="alert">
        {{ (form.errors as Record<string, string>).throttle }}
      </p>

      <div class="flex justify-end border-t border-surface-border pt-5">
        <Button type="submit" :loading="form.processing">Envoyer ma demande</Button>
      </div>
    </div>
  </form>
</template>
