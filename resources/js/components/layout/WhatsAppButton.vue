<script setup lang="ts">
// Bouton flottant WhatsApp, toujours visible (fixe en bas à droite).
// Numéro au format international sans « + » ni espaces pour wa.me.
const phone = '22797388388'
const href = `https://wa.me/${phone}`
</script>

<template>
  <a
    :href="href"
    target="_blank"
    rel="noopener noreferrer"
    class="group fixed bottom-5 right-5 z-50 inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg ring-1 ring-black/5 transition hover:scale-105 hover:bg-[#1ebe5d] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#25D366]/40"
    aria-label="Nous contacter sur WhatsApp"
    title="WhatsApp"
  >
    <span
      class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#25D366] opacity-40 motion-reduce:hidden"
      aria-hidden="true"
    />
    <svg
      class="relative h-7 w-7"
      viewBox="0 0 32 32"
      fill="currentColor"
      aria-hidden="true"
    >
      <path
        d="M16.004 0h-.008C7.174 0 0 7.176 0 16c0 3.498 1.124 6.74 3.036 9.376L1.05 31.3l6.13-1.96A15.9 15.9 0 0 0 16.004 32C24.826 32 32 24.822 32 16S24.826 0 16.004 0Zm9.31 22.594c-.386 1.09-1.92 1.994-3.142 2.258-.836.178-1.928.32-5.604-1.204-4.7-1.948-7.726-6.724-7.962-7.034-.226-.31-1.9-2.53-1.9-4.826 0-2.296 1.166-3.424 1.636-3.904.386-.396.84-.576 1.36-.576.168 0 .32.008.456.014.4.018.6.042.864.674.328.79 1.13 2.74 1.226 2.94.098.2.196.47.062.78-.126.32-.236.46-.436.708-.2.248-.39.438-.59.704-.182.234-.388.486-.158.882.23.39 1.022 1.684 2.196 2.728 1.514 1.348 2.738 1.766 3.176 1.95.326.136.714.104.952-.144.302-.318.676-.846 1.056-1.366.27-.372.612-.418.972-.282.366.128 2.31 1.088 2.71 1.286.4.198.664.294.762.46.098.168.098.964-.288 2.054Z"
      />
    </svg>
  </a>
</template>
