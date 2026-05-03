<script setup lang="ts">
import { onBeforeUnmount, ref, useId, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    label?: string
    align?: 'left' | 'right'
  }>(),
  {
    align: 'right',
  },
)

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const buttonId = `dd-btn-${useId()}`
const menuId = `dd-menu-${useId()}`

function close() {
  open.value = false
}

function toggle() {
  open.value = !open.value
}

function onDocumentClick(event: MouseEvent) {
  if (!root.value) return
  if (!root.value.contains(event.target as Node)) close()
}

function onKeydown(event: KeyboardEvent) {
  if (!open.value) return
  if (event.key === 'Escape') {
    close()
    ;(document.getElementById(buttonId) as HTMLButtonElement | null)?.focus()
  }
}

watch(open, (value) => {
  if (typeof document === 'undefined') return
  if (value) {
    document.addEventListener('click', onDocumentClick)
    document.addEventListener('keydown', onKeydown)
  } else {
    document.removeEventListener('click', onDocumentClick)
    document.removeEventListener('keydown', onKeydown)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div ref="root" class="relative inline-block">
    <button
      :id="buttonId"
      type="button"
      :aria-haspopup="true"
      :aria-expanded="open"
      :aria-controls="menuId"
      class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-surface-fg hover:bg-surface-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
      @click="toggle"
    >
      <slot name="trigger">
        {{ label }}
      </slot>
      <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
      </svg>
    </button>
    <Transition name="fade">
      <div
        v-if="open"
        :id="menuId"
        role="menu"
        :aria-labelledby="buttonId"
        :class="[
          'absolute z-30 mt-2 min-w-[10rem] rounded-lg border border-surface-border bg-surface-0 py-1 shadow-lg',
          align === 'right' ? 'right-0' : 'left-0',
        ]"
        @click="close"
      >
        <slot />
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.12s ease, transform 0.12s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
