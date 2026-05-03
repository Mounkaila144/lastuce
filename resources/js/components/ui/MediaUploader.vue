<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue?: File | null
    label?: string
    helper?: string
    error?: string
    accept?: string
    /** Image existante (URL) à afficher en preview tant qu'aucun nouveau
     *  fichier n'est sélectionné. */
    existingUrl?: string | null
    /** Affiche un bouton "Supprimer l'image" qui émet `remove`. */
    removable?: boolean
  }>(),
  {
    accept: 'image/jpeg,image/png,image/webp',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: File | null]
  remove: []
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const localFile = ref<File | null>(props.modelValue ?? null)
const localPreview = ref<string | null>(null)

watch(
  () => props.modelValue,
  (value) => {
    if (value !== localFile.value) {
      localFile.value = value ?? null
      updatePreview(value ?? null)
    }
  },
)

function updatePreview(file: File | null) {
  if (localPreview.value) {
    URL.revokeObjectURL(localPreview.value)
    localPreview.value = null
  }
  if (file && file.type.startsWith('image/')) {
    localPreview.value = URL.createObjectURL(file)
  }
}

function onChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0] ?? null
  localFile.value = file
  updatePreview(file)
  emit('update:modelValue', file)
}

function clear() {
  localFile.value = null
  updatePreview(null)
  if (fileInput.value) fileInput.value.value = ''
  emit('update:modelValue', null)
}

function remove() {
  clear()
  emit('remove')
}

const previewUrl = computed(() => localPreview.value ?? props.existingUrl ?? null)
const fileName = computed(() => localFile.value?.name ?? null)
const fileSize = computed(() => {
  if (!localFile.value) return null
  const kb = localFile.value.size / 1024
  return kb < 1024 ? `${kb.toFixed(0)} Ko` : `${(kb / 1024).toFixed(1)} Mo`
})
</script>

<template>
  <div class="space-y-2">
    <label v-if="label" class="block text-sm font-medium text-slate-700">{{ label }}</label>

    <div class="flex items-start gap-4">
      <div
        class="relative flex h-28 w-44 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50"
      >
        <img
          v-if="previewUrl"
          :src="previewUrl"
          alt=""
          class="h-full w-full object-cover"
        />
        <span v-else class="text-xs text-slate-400">Aucune image</span>
      </div>

      <div class="flex-1 space-y-2">
        <input
          ref="fileInput"
          type="file"
          :accept="accept"
          class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
          @change="onChange"
        />
        <p v-if="fileName" class="text-xs text-slate-600">
          {{ fileName }} <span class="text-slate-400">— {{ fileSize }}</span>
        </p>
        <p v-if="helper && !error" class="text-xs text-slate-500">{{ helper }}</p>
        <p v-if="error" class="text-xs text-red-600" role="alert">{{ error }}</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-if="localFile"
            type="button"
            class="text-xs font-medium text-slate-600 hover:text-slate-900"
            @click="clear"
          >
            Annuler la sélection
          </button>
          <button
            v-if="removable && existingUrl && !localFile"
            type="button"
            class="text-xs font-medium text-red-600 hover:text-red-800"
            @click="remove"
          >
            Supprimer l'image existante
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
