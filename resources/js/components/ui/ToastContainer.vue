<script setup lang="ts">
import { computed } from 'vue'
import { useToast, type ToastVariant } from '@/composables/useToast'

const { toasts, dismiss } = useToast()

const variantClass = computed(
  () =>
    (variant: ToastVariant) =>
      ({
        info: 'border-brand-500 bg-brand-50 text-brand-900',
        success: 'border-emerald-500 bg-emerald-50 text-emerald-900',
        error: 'border-red-500 bg-red-50 text-red-900',
        warning: 'border-amber-500 bg-amber-50 text-amber-900',
      })[variant],
)
</script>

<template>
  <Teleport to="body">
    <div
      class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-full max-w-sm flex-col gap-2"
      role="region"
      aria-live="polite"
      aria-label="Notifications"
    >
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'pointer-events-auto rounded-lg border-l-4 bg-white p-4 shadow-lg',
            variantClass(toast.variant),
          ]"
          role="status"
        >
          <div class="flex items-start gap-3">
            <div class="flex-1 text-sm">
              <p v-if="toast.title" class="font-semibold">{{ toast.title }}</p>
              <p>{{ toast.message }}</p>
            </div>
            <button
              type="button"
              class="text-current/70 hover:text-current"
              :aria-label="$t('common.close')"
              @click="dismiss(toast.id)"
            >
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
              </svg>
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
