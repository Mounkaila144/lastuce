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
  sujets: SelectOption[]
}

defineProps<{ options: Options }>()

const ui = useUiStore()
const toast = useToast()

const form = useForm({
  nom: '',
  email: '',
  sujet: 'general',
  message: '',
  cgv: false,
  website: '',
})

function submit() {
  form.post(`/${ui.locale}/contact`, {
    preserveScroll: true,
    onError: () => toast.error('Le formulaire contient des erreurs.'),
  })
}
</script>

<template>
  <Head title="Nous contacter" />

  <section class="bg-surface-1 py-10">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold">Nous contacter</h1>
      <p class="mt-2 text-surface-fg-muted">
        Une question, une remarque, une opportunité ? Écrivez-nous, l'équipe vous répond sous 24 à 48 h.
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

      <div class="grid gap-4 sm:grid-cols-2">
        <Input
          v-model="form.nom"
          label="Votre nom"
          required
          autocomplete="name"
          :error="form.errors.nom"
        />
        <Input
          v-model="form.email"
          type="email"
          label="Votre email"
          required
          autocomplete="email"
          :error="form.errors.email"
        />
      </div>

      <Select
        v-model="form.sujet"
        :options="options.sujets"
        label="Sujet"
        required
        :error="form.errors.sujet"
      />

      <Textarea
        v-model="form.message"
        label="Votre message"
        required
        :rows="6"
        helper="10 caractères minimum, 3000 maximum."
        :error="form.errors.message"
      />

      <label class="flex items-start gap-3">
        <input v-model="form.cgv" type="checkbox" class="mt-1 h-4 w-4 rounded border-surface-border text-brand-600 focus:ring-brand-500" />
        <span class="text-sm text-surface-fg">
          J'accepte que mes informations soient utilisées pour traiter ma demande, conformément à la
          <a href="/fr/privacy" class="font-medium text-brand-700 underline">politique de confidentialité</a>.
        </span>
      </label>
      <p v-if="form.errors.cgv" class="text-xs text-red-600" role="alert">{{ form.errors.cgv }}</p>
      <p v-if="(form.errors as Record<string, string>).throttle" class="text-xs text-red-600" role="alert">
        {{ (form.errors as Record<string, string>).throttle }}
      </p>

      <div class="flex justify-end border-t border-surface-border pt-5">
        <Button type="submit" :loading="form.processing">Envoyer</Button>
      </div>
    </div>
  </form>
</template>
