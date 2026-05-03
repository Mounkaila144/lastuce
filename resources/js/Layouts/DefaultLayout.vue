<script setup lang="ts">
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'

defineProps<{ title?: string }>()
</script>

<template>
  <div class="flex min-h-screen flex-col bg-surface-0 text-surface-fg antialiased">
    <AppHeader />
    <main id="main" class="flex-1 focus:outline-none" tabindex="-1">
      <slot />
    </main>
    <AppFooter />
    <ToastContainer />
  </div>
</template>
