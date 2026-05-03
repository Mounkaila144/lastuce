<script setup lang="ts">
import { computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import { useUiStore } from '@/stores/ui'
import type { SelectOption } from '@/components/ui/Select.vue'

interface Abonne {
  email: string
  prenom: string | null
  nom: string | null
  frequence_envoi: string
  interets: string[]
  token: string
  status: string
}

interface Options {
  frequences: SelectOption[]
  interets: SelectOption[]
}

const props = defineProps<{
  abonne: Abonne
  options: Options
}>()

const ui = useUiStore()
const page = usePage()
const flashSuccess = computed(() => (page.props.flash as Record<string, string | null>)?.success ?? null)

const form = useForm({
  prenom: props.abonne.prenom ?? '',
  nom: props.abonne.nom ?? '',
  frequence_envoi: props.abonne.frequence_envoi || 'hebdomadaire',
  interets: [...(props.abonne.interets ?? [])] as string[],
  regenerate_token: false,
})

function toggleInteret(value: string) {
  const idx = form.interets.indexOf(value)
  if (idx === -1) form.interets.push(value)
  else form.interets.splice(idx, 1)
}

function submit() {
  form.post(`/${ui.locale}/newsletter/preferences/${props.abonne.token}`, {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Mes préférences newsletter" />

  <section class="py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold">Mes préférences newsletter</h1>
      <p class="mt-1 text-sm text-surface-fg-muted">{{ abonne.email }}</p>

      <div v-if="flashSuccess" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" role="status">
        {{ flashSuccess }}
      </div>

      <form class="mt-6 space-y-6 rounded-xl border border-surface-border bg-surface-0 p-6 shadow-sm" novalidate @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
          <Input v-model="form.prenom" label="Prénom" :error="form.errors.prenom" />
          <Input v-model="form.nom" label="Nom" :error="form.errors.nom" />
        </div>

        <Select
          v-model="form.frequence_envoi"
          :options="options.frequences"
          label="Fréquence souhaitée"
          required
          :error="form.errors.frequence_envoi"
        />

        <fieldset class="space-y-2">
          <legend class="block text-sm font-medium text-surface-fg">Centres d'intérêt</legend>
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
        </fieldset>

        <label class="flex items-start gap-3">
          <input v-model="form.regenerate_token" type="checkbox" class="mt-1 h-4 w-4 rounded border-surface-border text-brand-600 focus:ring-brand-500" />
          <span class="text-sm text-surface-fg-muted">
            Régénérer mon jeton (le précédent lien sera invalidé).
          </span>
        </label>

        <div class="flex justify-end border-t border-surface-border pt-5">
          <Button type="submit" :loading="form.processing">Enregistrer</Button>
        </div>
      </form>

      <p class="mt-6 text-center text-sm">
        <a
          :href="`/${ui.locale}/newsletter/unsubscribe/${abonne.token}`"
          class="text-red-600 underline hover:text-red-700"
        >
          Me désabonner complètement
        </a>
      </p>
    </div>
  </section>
</template>
