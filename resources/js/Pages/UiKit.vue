<script setup lang="ts">
import { ref } from 'vue'
import {
  Button,
  Input,
  Textarea,
  Select,
  Modal,
  Tabs,
  Dropdown,
  Tooltip,
  type SelectOption,
  type TabItem,
} from '@/components/ui'
import VideoPlayer from '@/components/domain/VideoPlayer.vue'
import EpisodeCard from '@/components/domain/EpisodeCard.vue'
import EpisodeListItem from '@/components/domain/EpisodeListItem.vue'
import NewsletterForm from '@/components/domain/NewsletterForm.vue'
import ShareButtons from '@/components/domain/ShareButtons.vue'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const text = ref('')
const longText = ref('')
const selected = ref<string>('')
const modalOpen = ref(false)
const tab = ref('overview')

const options: SelectOption[] = [
  { label: 'Cuisine', value: 'cuisine' },
  { label: 'Maison', value: 'maison' },
  { label: 'Bricolage', value: 'bricolage' },
]
const tabs: TabItem[] = [
  { id: 'overview', label: 'Aperçu' },
  { id: 'cards', label: 'Cards' },
  { id: 'forms', label: 'Forms' },
]

const fakeEpisode = {
  id: 1,
  slug: 'astuce-citron',
  titre: 'Comment nettoyer une planche en bois avec un citron',
  type: 'episode' as const,
  type_label: 'Épisode',
  description: 'Une astuce simple et naturelle pour entretenir vos planches.',
  date_publication: '2026-04-12T00:00:00+00:00',
  duree: 192,
  vues: 1834,
  thumbnail_url: null,
}
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-12 px-4 py-12">
    <header class="space-y-1">
      <h1 class="text-3xl font-bold">UI Kit — L'Astuce</h1>
      <p class="text-sm text-surface-fg-muted">
        Catalogue interne des primitives et composants domaine (Epic 2).
      </p>
    </header>

    <section class="space-y-3" aria-labelledby="kit-buttons">
      <h2 id="kit-buttons" class="text-xl font-semibold">Buttons</h2>
      <div class="flex flex-wrap items-center gap-3">
        <Button>Primary</Button>
        <Button variant="secondary">Secondary</Button>
        <Button variant="ghost">Ghost</Button>
        <Button variant="danger">Danger</Button>
        <Button loading>Loading…</Button>
        <Button disabled>Disabled</Button>
        <Button size="sm">Small</Button>
        <Button size="lg">Large</Button>
      </div>
    </section>

    <section class="grid gap-6 md:grid-cols-2" aria-labelledby="kit-forms">
      <h2 id="kit-forms" class="text-xl font-semibold md:col-span-2">Forms</h2>
      <Input v-model="text" label="Email" placeholder="vous@exemple.com" helper="Votre adresse principale." />
      <Input v-model="text" label="Avec erreur" error="Adresse invalide" />
      <Select v-model="selected" :options="options" label="Catégorie" placeholder="Choisir…" />
      <Textarea v-model="longText" label="Description" placeholder="Décrivez votre astuce…" />
    </section>

    <section class="space-y-3" aria-labelledby="kit-overlays">
      <h2 id="kit-overlays" class="text-xl font-semibold">Overlays</h2>
      <div class="flex flex-wrap items-center gap-3">
        <Button @click="modalOpen = true">Open modal</Button>
        <Button variant="secondary" @click="toast.success('Astuce enregistrée !')">Toast success</Button>
        <Button variant="danger" @click="toast.error('Quelque chose a échoué.')">Toast error</Button>
        <Tooltip label="Action de partage">
          <Button variant="ghost">Hover me</Button>
        </Tooltip>
        <Dropdown label="Menu">
          <button class="flex w-full px-3 py-2 text-sm hover:bg-surface-2" type="button">Action 1</button>
          <button class="flex w-full px-3 py-2 text-sm hover:bg-surface-2" type="button">Action 2</button>
        </Dropdown>
      </div>
      <Modal v-model:open="modalOpen" title="Exemple de modale" description="Avec focus trap et fermeture ESC.">
        <p class="text-sm text-surface-fg-muted">
          Le contenu de la modale est libre. Les boutons du footer sont en slot.
        </p>
        <template #footer>
          <Button variant="secondary" @click="modalOpen = false">Annuler</Button>
          <Button @click="modalOpen = false">Confirmer</Button>
        </template>
      </Modal>
    </section>

    <section aria-labelledby="kit-tabs">
      <h2 id="kit-tabs" class="text-xl font-semibold">Tabs</h2>
      <Tabs v-model="tab" :tabs="tabs" aria-label="Sections du kit">
        <template #overview>
          <p class="text-sm text-surface-fg-muted">Onglet Aperçu — vue d'ensemble.</p>
        </template>
        <template #cards>
          <div class="grid gap-4 sm:grid-cols-2">
            <EpisodeCard :episode="fakeEpisode" />
            <EpisodeListItem :episode="fakeEpisode" />
          </div>
        </template>
        <template #forms>
          <NewsletterForm source="ui-kit" />
        </template>
      </Tabs>
    </section>

    <section class="space-y-3" aria-labelledby="kit-domain">
      <h2 id="kit-domain" class="text-xl font-semibold">Domain components</h2>
      <VideoPlayer
        url="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"
        provider="youtube"
        thumbnail="https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg"
        title="Démonstration VideoPlayer"
      />
      <ShareButtons url="https://lastuce.example/episodes/demo" title="Démo Astuce" />
    </section>
  </div>
</template>
