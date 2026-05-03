<script setup lang="ts">
withDefaults(defineProps<{ compact?: boolean }>(), { compact: false })

const logoSrc = '/logo.png'
</script>

<template>
  <span class="inline-flex items-center">
    <img
      :src="logoSrc"
      alt="L'Astuce"
      :class="compact ? 'h-8 w-auto' : 'h-10 w-auto'"
    >
  </span>
</template>
