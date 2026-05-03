<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useAdminTitle } from '@/composables/useAdminTitle'
import Textarea from '@/components/ui/Textarea.vue'
import Modal from '@/components/ui/Modal.vue'

defineOptions({ layout: AdminLayout })

interface AstuceImage {
  path: string
  url: string
}
interface AstuceShow {
  id: number
  titre_astuce: string
  description: string
  categorie?: string | null
  difficulte?: string | null
  temps_estime?: number | null
  materiel_requis?: string | null
  etapes: string[]
  conseils?: string | null
  fichier_joint?: string | null
  fichier_joint_url?: string | null
  images: AstuceImage[]
  nom: string
  email: string
  status: string
  status_label: string
  commentaire_admin?: string | null
  ip_soumetteur?: string | null
  created_at?: string | null
  updated_at?: string | null
}

const props = defineProps<{ astuce: AstuceShow }>()

const showApprove = ref(false)
const showReject = ref(false)

const approveForm = useForm({
  commentaire_admin: '',
  send_notification: true as boolean,
})
const rejectForm = useForm({
  commentaire_admin: props.astuce.commentaire_admin ?? '',
  send_notification: true as boolean,
})

function approve() {
  approveForm.post(`/admin/astuces/${props.astuce.id}/approve`, {
    preserveScroll: true,
    onSuccess: () => {
      showApprove.value = false
    },
  })
}
function reject() {
  rejectForm.post(`/admin/astuces/${props.astuce.id}/reject`, {
    preserveScroll: true,
    onSuccess: () => {
      showReject.value = false
    },
  })
}
function destroy() {
  if (!confirm(`Supprimer définitivement « ${props.astuce.titre_astuce} » ?`)) return
  router.delete(`/admin/astuces/${props.astuce.id}`)
}

const formattedDate = computed(() => {
  if (!props.astuce.created_at) return null
  return new Date(props.astuce.created_at).toLocaleString('fr-FR')
})

const statusBadge: Record<string, string> = {
  en_attente: 'bg-amber-100 text-amber-800',
  approuve: 'bg-emerald-100 text-emerald-800',
  rejete: 'bg-red-100 text-red-800',
}
useAdminTitle(() => props.astuce.titre_astuce)
</script>

<template>
  <Head :title="`Astuce — ${astuce.titre_astuce}`" />


  <div class="space-y-6">
    <!-- Header avec statut + actions -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2 text-sm">
        <Link href="/admin/astuces" class="text-slate-500 hover:text-brand-700">← Toutes les astuces</Link>
        <span
          :class="['ml-2 rounded-full px-2.5 py-0.5 text-xs font-semibold', statusBadge[astuce.status] ?? 'bg-slate-100']"
        >
          {{ astuce.status_label }}
        </span>
        <span v-if="formattedDate" class="text-xs text-slate-500">· soumise le {{ formattedDate }}</span>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="astuce.status !== 'approuve'"
          type="button"
          class="inline-flex h-9 items-center rounded-md bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700"
          @click="showApprove = true"
        >
          Approuver
        </button>
        <button
          v-if="astuce.status !== 'rejete'"
          type="button"
          class="inline-flex h-9 items-center rounded-md bg-red-600 px-3 text-sm font-semibold text-white hover:bg-red-700"
          @click="showReject = true"
        >
          Rejeter
        </button>
        <button
          type="button"
          class="inline-flex h-9 items-center rounded-md border border-red-200 bg-white px-3 text-sm font-semibold text-red-700 hover:bg-red-50"
          @click="destroy"
        >
          Supprimer
        </button>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
      <div class="space-y-5">
        <!-- Description -->
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Description
          </h2>
          <p class="whitespace-pre-line text-sm text-slate-800">{{ astuce.description }}</p>
        </div>

        <!-- Étapes -->
        <div v-if="astuce.etapes.length" class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Étapes
          </h2>
          <ol class="list-decimal space-y-2 pl-5 text-sm text-slate-800">
            <li v-for="(etape, i) in astuce.etapes" :key="i" class="whitespace-pre-line">
              {{ etape }}
            </li>
          </ol>
        </div>

        <!-- Matériel & conseils -->
        <div v-if="astuce.materiel_requis || astuce.conseils" class="grid gap-5 sm:grid-cols-2">
          <div v-if="astuce.materiel_requis" class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
              Matériel
            </h2>
            <p class="whitespace-pre-line text-sm text-slate-700">{{ astuce.materiel_requis }}</p>
          </div>
          <div v-if="astuce.conseils" class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
              Conseils
            </h2>
            <p class="whitespace-pre-line text-sm text-slate-700">{{ astuce.conseils }}</p>
          </div>
        </div>

        <!-- Images -->
        <div v-if="astuce.images.length" class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Images
          </h2>
          <div class="grid gap-3 sm:grid-cols-3">
            <a
              v-for="img in astuce.images"
              :key="img.path"
              :href="img.url"
              target="_blank"
              rel="noopener"
              class="block overflow-hidden rounded-lg border border-slate-200"
            >
              <img :src="img.url" alt="" loading="lazy" class="aspect-video w-full object-cover" />
            </a>
          </div>
        </div>

        <!-- Fichier joint -->
        <div v-if="astuce.fichier_joint_url" class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Fichier joint
          </h2>
          <a
            :href="astuce.fichier_joint_url"
            target="_blank"
            rel="noopener"
            class="text-sm font-semibold text-brand-700 hover:text-brand-800"
          >
            Télécharger : {{ astuce.fichier_joint }}
          </a>
        </div>
      </div>

      <!-- Sidebar -->
      <aside class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Soumettant
          </h2>
          <p class="text-sm font-semibold text-slate-900">{{ astuce.nom }}</p>
          <a :href="`mailto:${astuce.email}`" class="text-sm text-brand-700 hover:text-brand-800">
            {{ astuce.email }}
          </a>
          <p v-if="astuce.ip_soumetteur" class="mt-2 text-xs text-slate-500">
            IP : {{ astuce.ip_soumetteur }}
          </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Métadonnées
          </h2>
          <dl class="space-y-1 text-sm">
            <div class="flex justify-between">
              <dt class="text-slate-600">Catégorie</dt>
              <dd class="text-slate-900">{{ astuce.categorie ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-600">Difficulté</dt>
              <dd class="text-slate-900 capitalize">{{ astuce.difficulte ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-600">Temps estimé</dt>
              <dd class="text-slate-900">
                {{ astuce.temps_estime ? `${astuce.temps_estime} min` : '—' }}
              </dd>
            </div>
          </dl>
        </div>

        <div v-if="astuce.commentaire_admin" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
          <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Commentaire admin actuel
          </h2>
          <p class="whitespace-pre-line text-sm text-slate-700">{{ astuce.commentaire_admin }}</p>
        </div>
      </aside>
    </div>

    <!-- Modal Approuver -->
    <Modal
      v-model:open="showApprove"
      title="Approuver l'astuce"
      description="Le commentaire est optionnel et sera visible par le soumettant."
    >
      <Textarea
        v-model="approveForm.commentaire_admin"
        label="Commentaire (optionnel)"
        rows="3"
        :error="approveForm.errors.commentaire_admin"
      />
      <template #footer>
        <button
          type="button"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700"
          @click="showApprove = false"
        >
          Annuler
        </button>
        <button
          type="button"
          class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
          :disabled="approveForm.processing"
          @click="approve"
        >
          Confirmer l'approbation
        </button>
      </template>
    </Modal>

    <!-- Modal Rejeter -->
    <Modal
      v-model:open="showReject"
      title="Rejeter l'astuce"
      description="Le commentaire est obligatoire (≥ 10 caractères) et sera affiché au soumettant."
    >
      <Textarea
        v-model="rejectForm.commentaire_admin"
        label="Commentaire de rejet *"
        rows="4"
        placeholder="Expliquer brièvement la raison du rejet…"
        :error="rejectForm.errors.commentaire_admin"
      />
      <template #footer>
        <button
          type="button"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700"
          @click="showReject = false"
        >
          Annuler
        </button>
        <button
          type="button"
          class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
          :disabled="rejectForm.processing || rejectForm.commentaire_admin.trim().length < 10"
          @click="reject"
        >
          Confirmer le rejet
        </button>
      </template>
    </Modal>
  </div>
</template>
