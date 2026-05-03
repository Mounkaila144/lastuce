<script setup lang="ts">
import { computed, useId } from 'vue'

export interface SelectOption {
  label: string
  value: string | number
  disabled?: boolean
}

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null
    options: SelectOption[]
    label?: string
    helper?: string
    error?: string
    placeholder?: string
    disabled?: boolean
    required?: boolean
    id?: string
    name?: string
  }>(),
  {
    disabled: false,
    required: false,
  },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const fallbackId = useId()
const selectId = computed(() => props.id ?? `select-${fallbackId}`)
const helperId = computed(() => `${selectId.value}-helper`)
const errorId = computed(() => `${selectId.value}-error`)
const describedBy = computed(() => {
  const ids: string[] = []
  if (props.helper) ids.push(helperId.value)
  if (props.error) ids.push(errorId.value)
  return ids.length ? ids.join(' ') : undefined
})

const classes = computed(() => [
  'block w-full rounded-lg border bg-surface-0 px-3 py-2 text-sm text-surface-fg shadow-sm appearance-none pr-9',
  'focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500',
  'disabled:bg-surface-2 disabled:cursor-not-allowed',
  props.error ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-surface-border',
])
</script>

<template>
  <div class="space-y-1">
    <label
      v-if="label"
      :for="selectId"
      class="block text-sm font-medium text-surface-fg"
    >
      {{ label }}
      <span v-if="required" class="text-red-600" aria-hidden="true">*</span>
    </label>
    <div class="relative">
      <select
        :id="selectId"
        :name="name"
        :value="modelValue ?? ''"
        :disabled="disabled"
        :required="required"
        :aria-invalid="error ? 'true' : undefined"
        :aria-describedby="describedBy"
        :class="classes"
        @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option
          v-for="opt in options"
          :key="opt.value"
          :value="opt.value"
          :disabled="opt.disabled"
        >
          {{ opt.label }}
        </option>
      </select>
      <svg
        class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-surface-fg-muted"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
      >
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
      </svg>
    </div>
    <p v-if="helper && !error" :id="helperId" class="text-xs text-surface-fg-muted">
      {{ helper }}
    </p>
    <p v-if="error" :id="errorId" class="text-xs text-red-600" role="alert">
      {{ error }}
    </p>
  </div>
</template>
