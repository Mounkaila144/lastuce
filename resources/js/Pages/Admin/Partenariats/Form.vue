<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Input from '@/components/ui/Input.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import type { SelectOption } from '@/components/ui/Select.vue'

defineOptions({ layout: AdminLayout })

interface PartenaData {
  id?: number
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
}

const props = defineProps<{ partenariat: PartenaData | null }>()
const isEdit = computed(() => !!props.partenariat?.id)

const form = useForm({
  nom_entreprise: props.partenariat?.nom_entreprise ?? '',
  contact: props.partenariat?.contact ?? '',
  email: props.partenariat?.email ?? '',
  telephone: props.partenariat?.telephone ?? '',
  site_web: props.partenariat?.site_web ?? '',
  type_partenariat: props.partenariat?.type_partenariat ?? '',
  budget_envisage: props.partenariat?.budget_envisage ?? '',
  message: props.partenariat?.message ?? '',
  status: props.partenariat?.status ?? 'nouveau',
  notes_internes: props.partenariat?.notes_internes ?? '',
})

const statusOptions: SelectOption[] = [
  { value: 'nouveau', label: 'Nouveau' },
  { value: 'en_cours', label: 'En cours' },
  { value: 'accepte', label: 'Accepté' },
  { value: 'refuse', label: 'Refusé' },
]

function submit() {
  if (isEdit.value && props.partenariat) {
    form.put(`/admin/partenariats/${props.partenariat.id}`)
  } else {
    form.post('/admin/partenariats')
  }
}
useAdminTitle(() => (isEdit.value ? 'Éditer le partenariat' : 'Nouveau partenariat'))
</script>

<template>
  <Head :title="isEdit ? 'Éditer le partenariat' : 'Nouveau partenariat'" />

  <form class="space-y-5" @submit.prevent="submit">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
      <div class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Entreprise</h2>
          <Input v-model="form.nom_entreprise" label="Nom de l'entreprise *" required :error="form.errors.nom_entreprise" />
          <Input v-model="form.contact" label="Nom du contact *" required :error="form.errors.contact" />
          <Input v-model="form.email" type="email" label="Email *" required :error="form.errors.email" />
          <Input v-model="form.telephone" label="Téléphone" :error="form.errors.telephone" />
          <Input v-model="form.site_web" type="url" label="Site web" placeholder="https://…" :error="form.errors.site_web" />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Proposition</h2>
          <Input v-model="form.type_partenariat" label="Type de partenariat" :error="form.errors.type_partenariat" />
          <Input v-model="form.budget_envisage" label="Budget envisagé" :error="form.errors.budget_envisage" />
          <Textarea v-model="form.message" label="Message *" required :rows="5" :error="form.errors.message" />
        </div>
      </div>

      <aside class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Statut</h2>
          <Select v-model="form.status" :options="statusOptions" label="Statut *" :error="form.errors.status" />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <Textarea v-model="form.notes_internes" label="Notes internes" :rows="5" :error="form.errors.notes_internes" />
        </div>

        <div class="flex flex-col gap-2">
          <Button type="submit" variant="primary" :loading="form.processing" class="w-full">
            {{ isEdit ? 'Mettre à jour' : 'Créer' }}
          </Button>
          <Link href="/admin/partenariats" class="text-center text-sm font-medium text-slate-600 hover:text-slate-900">Annuler</Link>
        </div>
      </aside>
    </div>
  </form>
</template>
