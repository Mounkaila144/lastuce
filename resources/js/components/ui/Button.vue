<script setup lang="ts">
import { computed } from 'vue'

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger'
type Size = 'sm' | 'md' | 'lg'

const props = withDefaults(
  defineProps<{
    variant?: Variant
    size?: Size
    type?: 'button' | 'submit' | 'reset'
    disabled?: boolean
    loading?: boolean
    fullWidth?: boolean
    as?: 'button' | 'a'
    href?: string
  }>(),
  {
    variant: 'primary',
    size: 'md',
    type: 'button',
    disabled: false,
    loading: false,
    fullWidth: false,
    as: 'button',
  },
)

const variantClasses: Record<Variant, string> = {
  primary:
    'bg-brand-600 text-white hover:bg-brand-700 focus-visible:ring-brand-500 shadow-sm disabled:bg-brand-300',
  secondary:
    'bg-surface-1 text-surface-fg border border-surface-border hover:bg-surface-2 focus-visible:ring-brand-500',
  ghost:
    'bg-transparent text-surface-fg hover:bg-surface-2 focus-visible:ring-brand-500',
  danger:
    'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500 shadow-sm disabled:bg-red-300',
}

const sizeClasses: Record<Size, string> = {
  sm: 'h-8 px-3 text-sm gap-1.5',
  md: 'h-10 px-4 text-sm gap-2',
  lg: 'h-12 px-6 text-base gap-2',
}

const classes = computed(() => [
  'inline-flex items-center justify-center rounded-lg font-medium transition-colors',
  'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
  'disabled:cursor-not-allowed disabled:opacity-70',
  variantClasses[props.variant],
  sizeClasses[props.size],
  props.fullWidth ? 'w-full' : '',
])

const isDisabled = computed(() => props.disabled || props.loading)
</script>

<template>
  <component
    :is="as === 'a' ? 'a' : 'button'"
    :class="classes"
    :type="as === 'button' ? type : undefined"
    :href="as === 'a' ? href : undefined"
    :disabled="as === 'button' ? isDisabled : undefined"
    :aria-busy="loading || undefined"
    :aria-disabled="isDisabled || undefined"
  >
    <span v-if="loading" class="inline-flex" aria-hidden="true">
      <svg
        class="h-4 w-4 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
      >
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4" />
        <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
      </svg>
    </span>
    <slot v-else name="icon-leading" />
    <slot />
    <slot name="icon-trailing" />
  </component>
</template>
