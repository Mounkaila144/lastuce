import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { ref, nextTick } from 'vue'
import { useDebounce } from './useDebounce'

describe('useDebounce', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('returns the initial value synchronously', () => {
    const source = ref('initial')
    const debounced = useDebounce(source, 300)

    expect(debounced.value).toBe('initial')
  })

  it('delays updates by the configured timeout', async () => {
    const source = ref('a')
    const debounced = useDebounce(source, 300)

    source.value = 'b'
    await nextTick()

    expect(debounced.value).toBe('a')

    vi.advanceTimersByTime(299)
    expect(debounced.value).toBe('a')

    vi.advanceTimersByTime(1)
    expect(debounced.value).toBe('b')
  })

  it('only emits the latest value when source changes rapidly', async () => {
    const source = ref(0)
    const debounced = useDebounce(source, 300)

    source.value = 1
    await nextTick()
    vi.advanceTimersByTime(100)

    source.value = 2
    await nextTick()
    vi.advanceTimersByTime(100)

    source.value = 3
    await nextTick()
    vi.advanceTimersByTime(300)

    expect(debounced.value).toBe(3)
  })
})
