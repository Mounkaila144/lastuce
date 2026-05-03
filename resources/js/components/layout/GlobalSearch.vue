<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDebounce } from '@/composables/useDebounce'
import { useUiStore } from '@/stores/ui'

interface Suggestion {
  type: 'episode' | 'article'
  label: string
  href: string
}

const props = defineProps<{ placeholder: string }>()

const ui = useUiStore()
const query = ref('')
const debounced = useDebounce(query, 300)
const suggestions = ref<Suggestion[]>([])
const focused = ref(false)
const activeIndex = ref(-1)
let lastController: AbortController | null = null

const expanded = computed(() => focused.value || query.value.length > 0)
const open = computed(() => focused.value && suggestions.value.length > 0)

watch(debounced, async (term) => {
  const trimmed = term.trim()
  if (trimmed.length < 2) {
    suggestions.value = []
    return
  }

  lastController?.abort()
  lastController = new AbortController()
  try {
    const res = await fetch(`/api/search/suggestions?q=${encodeURIComponent(trimmed)}`, {
      headers: { Accept: 'application/json' },
      signal: lastController.signal,
    })
    if (!res.ok) {
      suggestions.value = []
      return
    }
    const json = (await res.json()) as { suggestions: Suggestion[] }
    suggestions.value = json.suggestions ?? []
    activeIndex.value = -1
  } catch (err) {
    if ((err as Error).name !== 'AbortError') {
      suggestions.value = []
    }
  }
})

function submit() {
  const term = query.value.trim()
  if (!term) return
  router.visit(`/${ui.locale}/search?q=${encodeURIComponent(term)}`)
  focused.value = false
}

function deferBlur() {
  // Laisse le @mousedown des suggestions s'exécuter avant la fermeture.
  window.setTimeout(() => {
    focused.value = false
  }, 150)
}

function onKey(event: KeyboardEvent) {
  if (!open.value) return
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    activeIndex.value = (activeIndex.value + 1) % suggestions.value.length
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    activeIndex.value = activeIndex.value <= 0 ? suggestions.value.length - 1 : activeIndex.value - 1
  } else if (event.key === 'Enter' && activeIndex.value >= 0) {
    event.preventDefault()
    const target = suggestions.value[activeIndex.value]
    if (target) {
      router.visit(target.href)
      focused.value = false
    }
  } else if (event.key === 'Escape') {
    focused.value = false
  }
}

onBeforeUnmount(() => lastController?.abort())
</script>

<template>
  <form
    role="search"
    class="relative flex w-full items-center"
    @submit.prevent="submit"
  >
    <label class="relative flex w-full items-center">
      <svg
        class="pointer-events-none absolute left-3 h-4 w-4 text-surface-fg-muted"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
      >
        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.74 9.6l3.08 3.08a.75.75 0 1 0 1.06-1.06l-3.08-3.08A5.5 5.5 0 0 0 9 3.5Zm-4 5.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
      </svg>
      <input
        v-model="query"
        type="search"
        :placeholder="props.placeholder"
        :aria-label="props.placeholder"
        :aria-expanded="open"
        aria-controls="search-suggestions"
        :class="[
          'w-full rounded-full border border-surface-border bg-surface-1 py-2 pl-9 pr-4 text-sm text-surface-fg shadow-sm transition-all',
          'placeholder:text-surface-fg-muted/70 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500',
          expanded ? 'lg:w-80' : 'lg:w-56',
        ]"
        @focus="focused = true"
        @blur="deferBlur"
        @keydown="onKey"
      />
    </label>

    <ul
      v-if="open"
      id="search-suggestions"
      role="listbox"
      class="absolute left-0 right-0 top-full mt-2 max-h-72 overflow-y-auto rounded-lg border border-surface-border bg-surface-0 py-1 shadow-lg"
    >
      <li
        v-for="(s, idx) in suggestions"
        :key="s.href"
        role="option"
        :aria-selected="idx === activeIndex"
      >
        <a
          :href="s.href"
          :class="[
            'flex items-center justify-between gap-3 px-3 py-2 text-sm',
            idx === activeIndex ? 'bg-surface-2' : 'hover:bg-surface-2',
          ]"
          @mousedown.prevent="router.visit(s.href)"
        >
          <span class="line-clamp-1">{{ s.label }}</span>
          <span class="text-xs text-surface-fg-muted">{{ s.type === 'episode' ? 'Épisode' : 'Article' }}</span>
        </a>
      </li>
    </ul>
  </form>
</template>
