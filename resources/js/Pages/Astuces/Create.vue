<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import Input from '@/components/ui/Input.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import { useUiStore } from '@/stores/ui'
import { useToast } from '@/composables/useToast'
import type { SelectOption } from '@/components/ui/Select.vue'

interface Options {
  categories: SelectOption[]
  difficultes: SelectOption[]
}

const props = defineProps<{ options: Options }>()

const ui = useUiStore()
const toast = useToast()
const step = ref<1 | 2 | 3>(1)

const form = useForm({
  // Étape 1 — Identité
  nom: '',
  email: '',
  // Étape 2 — Astuce
  titre_astuce: '',
  categorie: '',
  difficulte: '',
  temps_estime: null as number | null,
  description: '',
  materiel_requis: '',
  etapes: [''],
  conseils: '',
  // Étape 3 — Médias (transmis via FormData implicite par useForm)
  fichier_joint: null as File | null,
  images: [] as File[],
  // Méta
  cgv: false as boolean,
  website: '',
})

const stepLabels = ['Identité', 'Astuce', 'Médias']

const canGoNext = computed(() => {
  if (step.value === 1) {
    return form.nom.trim().length >= 2 && /.+@.+\..+/.test(form.email)
  }
  if (step.value === 2) {
    return (
      form.titre_astuce.trim().length >= 5 &&
      form.categorie !== '' &&
      form.difficulte !== '' &&
      form.description.trim().length >= 20 &&
      form.etapes.length >= 1 &&
      form.etapes.every((e) => e.trim().length >= 3)
    )
  }
  return form.cgv === true
})

function next() {
  if (step.value < 3 && canGoNext.value) step.value = (step.value + 1) as 1 | 2 | 3
}

function prev() {
  if (step.value > 1) step.value = (step.value - 1) as 1 | 2 | 3
}

function addStep() {
  if (form.etapes.length < 20) form.etapes.push('')
}

function removeStep(index: number) {
  form.etapes.splice(index, 1)
  if (form.etapes.length === 0) form.etapes.push('')
}

function onFileJoint(event: Event) {
  const target = event.target as HTMLInputElement
  form.fichier_joint = target.files?.[0] ?? null
}

function onImages(event: Event) {
  const target = event.target as HTMLInputElement
  form.images = target.files ? Array.from(target.files).slice(0, 3) : []
}

function submit() {
  if (!canGoNext.value) return
  form.post(`/${ui.locale}/astuces`, {
    forceFormData: true,
    preserveScroll: true,
    onError: () => {
      toast.error('Le formulaire contient des erreurs. Vérifiez chaque étape.')
      // Retourner à la première étape qui contient une erreur.
      const errs = form.errors as Record<string, string>
      if (errs.nom || errs.email) step.value = 1
      else if (Object.keys(errs).some((k) => !['fichier_joint', 'images'].includes(k))) step.value = 2
    },
  })
}
</script>

<template>
  <Head title="Proposer une astuce" />

  <section class="bg-surface-1 py-8">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold">Proposer une astuce</h1>
      <p class="mt-1 text-sm text-surface-fg-muted">
        3 étapes rapides — vous recevrez un email de confirmation.
      </p>

      <ol class="mt-6 flex items-center gap-2" aria-label="Progression">
        <li
          v-for="(label, idx) in stepLabels"
          :key="label"
          class="flex flex-1 items-center gap-2"
        >
          <span
            :class="[
              'flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
              step >= idx + 1 ? 'bg-brand-600 text-white' : 'bg-surface-2 text-surface-fg-muted',
            ]"
          >
            {{ idx + 1 }}
          </span>
          <span :class="['text-sm font-medium', step === idx + 1 ? 'text-surface-fg' : 'text-surface-fg-muted']">
            {{ label }}
          </span>
          <span v-if="idx < stepLabels.length - 1" class="ml-2 h-px flex-1 bg-surface-border" aria-hidden="true" />
        </li>
      </ol>
    </div>
  </section>

  <form class="py-10" @submit.prevent="submit" novalidate>
    <div class="mx-auto max-w-3xl space-y-6 rounded-xl border border-surface-border bg-surface-0 p-6 px-4 shadow-sm sm:px-6 lg:px-8">

      <!-- Honeypot caché -->
      <div class="absolute -left-[10000px]" aria-hidden="true">
        <label>Site web (laisser vide)
          <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
        </label>
      </div>

      <section v-if="step === 1" class="space-y-4">
        <h2 class="text-xl font-bold">Qui êtes-vous ?</h2>
        <Input
          v-model="form.nom"
          label="Votre nom"
          required
          autocomplete="name"
          :error="form.errors.nom"
        />
        <Input
          v-model="form.email"
          label="Votre email"
          type="email"
          required
          autocomplete="email"
          helper="Utilisé uniquement pour la confirmation et le suivi."
          :error="form.errors.email"
        />
      </section>

      <section v-if="step === 2" class="space-y-4">
        <h2 class="text-xl font-bold">Votre astuce</h2>
        <Input
          v-model="form.titre_astuce"
          label="Titre"
          required
          :error="form.errors.titre_astuce"
        />
        <div class="grid gap-4 sm:grid-cols-3">
          <Select
            v-model="form.categorie"
            :options="options.categories"
            label="Catégorie"
            placeholder="Choisir…"
            required
            :error="form.errors.categorie"
          />
          <Select
            v-model="form.difficulte"
            :options="options.difficultes"
            label="Difficulté"
            placeholder="Choisir…"
            required
            :error="form.errors.difficulte"
          />
          <Input
            v-model.number="form.temps_estime"
            type="number"
            label="Temps (min)"
            placeholder="ex: 15"
            :error="form.errors.temps_estime"
          />
        </div>
        <Textarea
          v-model="form.description"
          label="Description"
          required
          :rows="4"
          :error="form.errors.description"
          helper="20 caractères minimum, 2000 maximum."
        />
        <Textarea
          v-model="form.materiel_requis"
          label="Matériel requis"
          :rows="2"
          :error="form.errors.materiel_requis"
        />

        <div>
          <p class="block text-sm font-medium text-surface-fg">Étapes</p>
          <ol class="mt-2 space-y-2">
            <li v-for="(_, idx) in form.etapes" :key="idx" class="flex items-start gap-2">
              <span class="mt-2 inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                {{ idx + 1 }}
              </span>
              <Input
                v-model="form.etapes[idx]"
                :placeholder="`Étape ${idx + 1}`"
                :error="(form.errors as Record<string, string>)[`etapes.${idx}`]"
              />
              <button
                type="button"
                v-if="form.etapes.length > 1"
                class="mt-1 text-sm text-red-600 hover:text-red-700"
                :aria-label="`Supprimer l'étape ${idx + 1}`"
                @click="removeStep(idx)"
              >
                ×
              </button>
            </li>
          </ol>
          <button
            type="button"
            class="mt-2 inline-flex items-center text-sm font-semibold text-brand-700 hover:text-brand-800"
            @click="addStep"
            :disabled="form.etapes.length >= 20"
          >
            + Ajouter une étape
          </button>
        </div>

        <Textarea v-model="form.conseils" label="Conseils (optionnel)" :rows="2" :error="form.errors.conseils" />
      </section>

      <section v-if="step === 3" class="space-y-4">
        <h2 class="text-xl font-bold">Médias et validation</h2>
        <div>
          <label class="block text-sm font-medium text-surface-fg">Pièce jointe (PDF, DOC, image)</label>
          <input
            type="file"
            accept=".pdf,.doc,.docx,image/*"
            class="mt-1 block w-full text-sm text-surface-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-brand-700 hover:file:bg-brand-100"
            @change="onFileJoint"
          />
          <p v-if="form.errors.fichier_joint" class="mt-1 text-xs text-red-600" role="alert">{{ form.errors.fichier_joint }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-surface-fg">Images illustratives (jusqu'à 3)</label>
          <input
            type="file"
            multiple
            accept="image/*"
            class="mt-1 block w-full text-sm text-surface-fg-muted file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-brand-700 hover:file:bg-brand-100"
            @change="onImages"
          />
          <p v-if="form.errors.images" class="mt-1 text-xs text-red-600" role="alert">{{ form.errors.images }}</p>
        </div>

        <label class="flex items-start gap-3">
          <input v-model="form.cgv" type="checkbox" class="mt-1 h-4 w-4 rounded border-surface-border text-brand-600 focus:ring-brand-500" />
          <span class="text-sm text-surface-fg">
            J'accepte que mon astuce soit publiée sur L'Astuce après modération.
          </span>
        </label>
        <p v-if="form.errors.cgv" class="text-xs text-red-600" role="alert">{{ form.errors.cgv }}</p>
        <p v-if="(form.errors as Record<string, string>).throttle" class="text-xs text-red-600" role="alert">
          {{ (form.errors as Record<string, string>).throttle }}
        </p>
      </section>

      <footer class="flex justify-between gap-3 border-t border-surface-border pt-5">
        <Button v-if="step > 1" type="button" variant="secondary" @click="prev">Précédent</Button>
        <span v-else />
        <div class="flex gap-2">
          <Button v-if="step < 3" type="button" :disabled="!canGoNext" @click="next">Suivant</Button>
          <Button v-else type="submit" :loading="form.processing" :disabled="!canGoNext">
            Envoyer
          </Button>
        </div>
      </footer>
    </div>
  </form>
</template>
