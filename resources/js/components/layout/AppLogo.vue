<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{ compact?: boolean }>(), { compact: false })

// En mode compact (sidebar admin, espaces étroits) on utilise l'icône courte
// « L'a! » ; sinon le logotype complet « L'astuce! ».
const logoSrc = computed(() => (props.compact ? '/favicon.png' : '/logo.png'))
</script>

<template>
  <span class="inline-flex items-center">
    <img
      :src="logoSrc"
      alt="L'Astuce"
      :class="compact ? 'h-10 w-auto' : 'h-24 w-auto'"
    >
  </span>
</template>
