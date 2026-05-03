<script setup lang="ts">
import { ref, useId } from 'vue'

const props = withDefaults(
  defineProps<{
    label: string
    placement?: 'top' | 'bottom' | 'left' | 'right'
  }>(),
  {
    placement: 'top',
  },
)

const open = ref(false)
const id = `tt-${useId()}`

function show() {
  open.value = true
}
function hide() {
  open.value = false
}

const placementClasses: Record<string, string> = {
  top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
  bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
  left: 'right-full top-1/2 -translate-y-1/2 mr-2',
  right: 'left-full top-1/2 -translate-y-1/2 ml-2',
}
</script>

<template>
  <span
    class="relative inline-flex"
    @mouseenter="show"
    @mouseleave="hide"
    @focusin="show"
    @focusout="hide"
  >
    <span :aria-describedby="open ? id : undefined">
      <slot />
    </span>
    <Transition name="fade">
      <span
        v-if="open"
        :id="id"
        role="tooltip"
        :class="[
          'pointer-events-none absolute z-40 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white shadow-md',
          placementClasses[placement],
        ]"
      >
        {{ label }}
      </span>
    </Transition>
  </span>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.12s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
