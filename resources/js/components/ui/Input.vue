<script setup lang="ts">
import { computed, useId } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null
    label?: string
    helper?: string
    error?: string
    type?: string
    placeholder?: string
    disabled?: boolean
    required?: boolean
    autocomplete?: string
    id?: string
    name?: string
  }>(),
  {
    type: 'text',
    disabled: false,
    required: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  blur: [event: FocusEvent]
  focus: [event: FocusEvent]
}>()

const fallbackId = useId()
const inputId = computed(() => props.id ?? `input-${fallbackId}`)
const helperId = computed(() => `${inputId.value}-helper`)
const errorId = computed(() => `${inputId.value}-error`)

const describedBy = computed(() => {
  const ids: string[] = []
  if (props.helper) ids.push(helperId.value)
  if (props.error) ids.push(errorId.value)
  return ids.length ? ids.join(' ') : undefined
})

const inputClasses = computed(() => [
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
      :for="inputId"
      class="block text-sm font-medium text-surface-fg"
    >
      {{ label }}
      <span v-if="required" class="text-red-600" aria-hidden="true">*</span>
    </label>
    <input
      :id="inputId"
      :name="name"
      :type="type"
      :value="modelValue ?? ''"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :autocomplete="autocomplete"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="describedBy"
      :class="inputClasses"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      @blur="emit('blur', $event)"
      @focus="emit('focus', $event)"
    />
    <p v-if="helper && !error" :id="helperId" class="text-xs text-surface-fg-muted">
      {{ helper }}
    </p>
    <p v-if="error" :id="errorId" class="text-xs text-red-600" role="alert">
      {{ error }}
    </p>
  </div>
</template>
