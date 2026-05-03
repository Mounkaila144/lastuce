<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import { useUiStore } from '@/stores/ui'
import { useToast } from '@/composables/useToast'
import type { SelectOption } from '@/components/ui/Select.vue'

interface Options {
  frequences: SelectOption[]
  interets: SelectOption[]
}

const props = defineProps<{
  options: Options
  stats: { subscribers: number }
}>()

const ui = useUiStore()
const toast = useToast()

const form = useForm({
  email: '',
  prenom: '',
  nom: '',
  frequence_envoi: 'hebdomadaire',
  interets: [] as string[],
  source: 'subscribe_page',
  cgv: false,
  website: '',
})

function toggleInteret(value: string) {
  const idx = form.interets.indexOf(value)
  if (idx === -1) form.interets.push(value)
  else form.interets.splice(idx, 1)
}

function submit() {
  form.post(`/${ui.locale}/newsletter`, {
    preserveScroll: true,
    onError: () => toast.error('Le formulaire contient des erreurs. Vérifiez vos saisies.'),
  })
}
</script>

<template>
  <Head title="Inscription à la newsletter" />

  <section class="bg-surface-1 py-10">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold">S'inscrire à la newsletter</h1>
      <p class="mt-2 text-surface-fg-muted">
        Rejoignez les <strong>{{ stats.subscribers }}</strong> abonnés qui reçoivent les meilleures astuces de la semaine.
        Confirmation par email — vous pouvez vous désabonner à tout moment.
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
          v-model="form.prenom"
          label="Prénom (optionnel)"
          autocomplete="given-name"
          :error="form.errors.prenom"
        />
        <Input
          v-model="form.nom"
          label="Nom (optionnel)"
          autocomplete="family-name"
          :error="form.errors.nom"
        />
      </div>

      <Input
        v-model="form.email"
        type="email"
        label="Adresse email"
        required
        autocomplete="email"
        :error="form.errors.email"
        helper="Nous ne partagerons jamais votre adresse."
      />

      <Select
        v-model="form.frequence_envoi"
        :options="options.frequences"
        label="Fréquence souhaitée"
        :error="form.errors.frequence_envoi"
      />

      <fieldset class="space-y-2">
        <legend class="block text-sm font-medium text-surface-fg">Centres d'intérêt (optionnel)</legend>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="opt in options.interets"
            :key="opt.value"
            type="button"
            :aria-pressed="form.interets.includes(String(opt.value))"
            :class="[
              'inline-flex items-center rounded-full border px-3 py-1.5 text-sm transition',
              form.interets.includes(String(opt.value))
                ? 'border-brand-600 bg-brand-50 text-brand-700'
                : 'border-surface-border bg-surface-0 text-surface-fg hover:bg-surface-2',
            ]"
            @click="toggleInteret(String(opt.value))"
          >
            {{ opt.label }}
          </button>
        </div>
        <p v-if="form.errors.interets" class="text-xs text-red-600" role="alert">{{ form.errors.interets }}</p>
      </fieldset>

      <label class="flex items-start gap-3">
        <input v-model="form.cgv" type="checkbox" class="mt-1 h-4 w-4 rounded border-surface-border text-brand-600 focus:ring-brand-500" />
        <span class="text-sm text-surface-fg">
          J'accepte de recevoir la newsletter de L'Astuce et que mon email soit traité conformément à la
          <a href="/fr/privacy" class="font-medium text-brand-700 underline">politique de confidentialité</a>.
        </span>
      </label>
      <p v-if="form.errors.cgv" class="text-xs text-red-600" role="alert">{{ form.errors.cgv }}</p>
      <p v-if="(form.errors as Record<string, string>).throttle" class="text-xs text-red-600" role="alert">
        {{ (form.errors as Record<string, string>).throttle }}
      </p>

      <div class="flex justify-end border-t border-surface-border pt-5">
        <Button type="submit" :loading="form.processing">M'inscrire</Button>
      </div>
    </div>
  </form>
</template>
