<script setup lang="ts">
import { computed, useId } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue?: string | null
    label?: string
    helper?: string
    error?: string
    placeholder?: string
    disabled?: boolean
    required?: boolean
    rows?: number
    id?: string
    name?: string
  }>(),
  {
    rows: 4,
    disabled: false,
    required: false,
  },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const fallbackId = useId()
const textareaId = computed(() => props.id ?? `textarea-${fallbackId}`)
const helperId = computed(() => `${textareaId.value}-helper`)
const errorId = computed(() => `${textareaId.value}-error`)
const describedBy = computed(() => {
  const ids: string[] = []
  if (props.helper) ids.push(helperId.value)
  if (props.error) ids.push(errorId.value)
  return ids.length ? ids.join(' ') : undefined
})

const classes = computed(() => [
  'block w-full rounded-lg border bg-surface-0 px-3 py-2 text-sm text-surface-fg shadow-sm',
  'placeholder:text-surface-fg-muted/70',
  'focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500',
  'disabled:bg-surface-2 disabled:cursor-not-allowed',
  props.error ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-surface-border',
])
</script>

<template>
  <div class="space-y-1">
    <label
      v-if="label"
      :for="textareaId"
      class="block text-sm font-medium text-surface-fg"
    >
      {{ label }}
      <span v-if="required" class="text-red-600" aria-hidden="true">*</span>
    </label>
    <textarea
      :id="textareaId"
      :name="name"
      :rows="rows"
      :value="modelValue ?? ''"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="describedBy"
      :class="classes"
      @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
    <p v-if="helper && !error" :id="helperId" class="text-xs text-surface-fg-muted">
      {{ helper }}
    </p>
    <p v-if="error" :id="errorId" class="text-xs text-red-600" role="alert">
      {{ error }}
    </p>
  </div>
</template>
