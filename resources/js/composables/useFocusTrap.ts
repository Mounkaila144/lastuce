import { onBeforeUnmount, watch, type Ref } from 'vue'

const FOCUSABLE_SELECTORS = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled]):not([type="hidden"])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

export interface FocusTrapOptions {
  active: Ref<boolean>
  container: Ref<HTMLElement | null>
  onEscape?: () => void
  initialFocus?: Ref<HTMLElement | null>
}

export function useFocusTrap({ active, container, onEscape, initialFocus }: FocusTrapOptions) {
  let lastFocused: HTMLElement | null = null

  function focusables(): HTMLElement[] {
    if (!container.value) return []
    return Array.from(container.value.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTORS)).filter(
      (el) => !el.hasAttribute('aria-hidden') && el.offsetParent !== null,
    )
  }

  function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      onEscape?.()
      return
    }

    if (event.key !== 'Tab') return

    const els = focusables()
    if (els.length === 0) {
      event.preventDefault()
      return
    }

    const first = els[0]
    const last = els[els.length - 1]
    const current = document.activeElement as HTMLElement | null

    if (event.shiftKey && current === first) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && current === last) {
      event.preventDefault()
      first.focus()
    }
  }

  watch(
    active,
    async (open) => {
      if (open) {
        lastFocused = document.activeElement as HTMLElement | null
        document.addEventListener('keydown', onKeydown)
        // Microtask to wait for the container to render.
        queueMicrotask(() => {
          const target = initialFocus?.value ?? focusables()[0] ?? container.value
          target?.focus()
        })
      } else {
        document.removeEventListener('keydown', onKeydown)
        lastFocused?.focus()
      }
    },
    { immediate: true },
  )

  onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown)
  })
}
