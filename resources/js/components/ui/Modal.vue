<script setup lang="ts">
import { computed, ref, toRef, useId, watch } from 'vue'
import { useFocusTrap } from '@/composables/useFocusTrap'

const props = withDefaults(
  defineProps<{
    open: boolean
    title?: string
    description?: string
    size?: 'sm' | 'md' | 'lg' | 'xl'
    closeOnBackdrop?: boolean
  }>(),
  {
    size: 'md',
    closeOnBackdrop: true,
  },
)

const emit = defineEmits<{ 'update:open': [value: boolean]; close: [] }>()

const dialog = ref<HTMLElement | null>(null)
const titleId = `modal-title-${useId()}`
const descId = `modal-desc-${useId()}`

const sizeClasses = computed(
  () =>
    ({
      sm: 'max-w-sm',
      md: 'max-w-md',
      lg: 'max-w-2xl',
      xl: 'max-w-4xl',
    })[props.size],
)

useFocusTrap({
  active: toRef(props, 'open'),
  container: dialog,
  onEscape: close,
})

watch(
  () => props.open,
  (open) => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = open ? 'hidden' : ''
  },
)

function close() {
  emit('update:open', false)
  emit('close')
}

function onBackdropClick() {
  if (props.closeOnBackdrop) close()
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center px-4"
        role="presentation"
      >
        <div
          class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
          aria-hidden="true"
          @click="onBackdropClick"
        />
        <div
          ref="dialog"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="title ? titleId : undefined"
          :aria-describedby="description ? descId : undefined"
          tabindex="-1"
          :class="[
            'relative z-10 w-full rounded-2xl bg-surface-0 text-surface-fg shadow-xl border border-surface-border',
            sizeClasses,
          ]"
        >
          <header v-if="title || $slots.header" class="flex items-start justify-between gap-4 border-b border-surface-border px-6 py-4">
            <div>
              <h2 v-if="title" :id="titleId" class="text-lg font-semibold">
                {{ title }}
              </h2>
              <p v-if="description" :id="descId" class="mt-1 text-sm text-surface-fg-muted">
                {{ description }}
              </p>
              <slot name="header" />
            </div>
            <button
              type="button"
              class="rounded-md p-1 text-surface-fg-muted transition hover:bg-surface-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
              :aria-label="$t('common.close')"
              @click="close"
            >
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
              </svg>
            </button>
          </header>
          <div class="px-6 py-5">
            <slot />
          </div>
          <footer v-if="$slots.footer" class="flex justify-end gap-2 border-t border-surface-border px-6 py-4">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: scale(0.96);
  opacity: 0;
}
</style>
