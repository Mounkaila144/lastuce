import { ref, watch, type Ref } from 'vue'

/**
 * Returns a ref that mirrors the source after a debounce delay.
 * Used by the live search (S3.3) to throttle Inertia query updates.
 */
export function useDebounce<T>(source: Ref<T>, delay = 300): Ref<T> {
  const debounced = ref(source.value) as Ref<T>
  let timeoutId: ReturnType<typeof setTimeout> | null = null

  watch(source, (value) => {
    if (timeoutId !== null) {
      clearTimeout(timeoutId)
    }
    timeoutId = setTimeout(() => {
      debounced.value = value
      timeoutId = null
    }, delay)
  })

  return debounced
}
