<script setup lang="ts">
import { computed, ref, watch } from 'vue'

export interface TabItem {
  id: string
  label: string
  disabled?: boolean
}

const props = withDefaults(
  defineProps<{
    modelValue?: string
    tabs: TabItem[]
    ariaLabel?: string
  }>(),
  {},
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const internal = ref(props.modelValue ?? props.tabs[0]?.id ?? '')

watch(
  () => props.modelValue,
  (v) => {
    if (v && v !== internal.value) internal.value = v
  },
)

const activeId = computed({
  get: () => internal.value,
  set: (v) => {
    internal.value = v
    emit('update:modelValue', v)
  },
})

function focusTab(index: number) {
  const enabled = props.tabs.filter((t) => !t.disabled)
  if (enabled.length === 0) return
  const next = (index + enabled.length) % enabled.length
  activeId.value = enabled[next].id
  queueMicrotask(() => {
    const el = document.getElementById(`tab-${enabled[next].id}`)
    el?.focus()
  })
}

function onKey(event: KeyboardEvent, index: number) {
  if (event.key === 'ArrowRight') {
    event.preventDefault()
    focusTab(index + 1)
  } else if (event.key === 'ArrowLeft') {
    event.preventDefault()
    focusTab(index - 1)
  } else if (event.key === 'Home') {
    event.preventDefault()
    focusTab(0)
  } else if (event.key === 'End') {
    event.preventDefault()
    focusTab(props.tabs.length - 1)
  }
}
</script>

<template>
  <div>
    <div
      role="tablist"
      :aria-label="ariaLabel"
      class="flex gap-1 border-b border-surface-border"
    >
      <button
        v-for="(tab, index) in tabs"
        :key="tab.id"
        :id="`tab-${tab.id}`"
        type="button"
        role="tab"
        :aria-selected="activeId === tab.id"
        :aria-controls="`panel-${tab.id}`"
        :tabindex="activeId === tab.id ? 0 : -1"
        :disabled="tab.disabled"
        :class="[
          'relative -mb-px px-4 py-2 text-sm font-medium transition-colors',
          'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 rounded-t-md',
          activeId === tab.id
            ? 'border-b-2 border-brand-600 text-brand-700'
            : 'text-surface-fg-muted hover:text-surface-fg',
          tab.disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
        ]"
        @click="!tab.disabled && (activeId = tab.id)"
        @keydown="onKey($event, index)"
      >
        {{ tab.label }}
      </button>
    </div>
    <div
      v-for="tab in tabs"
      :key="tab.id"
      :id="`panel-${tab.id}`"
      role="tabpanel"
      :aria-labelledby="`tab-${tab.id}`"
      :hidden="activeId !== tab.id"
      class="pt-4"
      tabindex="0"
    >
      <slot :name="tab.id" />
    </div>
  </div>
</template>
