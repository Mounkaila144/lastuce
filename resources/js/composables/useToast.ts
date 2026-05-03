import { reactive, readonly } from 'vue'

export type ToastVariant = 'info' | 'success' | 'error' | 'warning'

export interface ToastInput {
  title?: string
  message: string
  variant?: ToastVariant
  duration?: number
}

export interface Toast extends Required<Omit<ToastInput, 'title'>> {
  id: number
  title?: string
}

interface ToastState {
  items: Toast[]
}

const state = reactive<ToastState>({ items: [] })
let nextId = 1

function dismiss(id: number) {
  const index = state.items.findIndex((t) => t.id === id)
  if (index !== -1) state.items.splice(index, 1)
}

function push(input: ToastInput): number {
  const toast: Toast = {
    id: nextId++,
    title: input.title,
    message: input.message,
    variant: input.variant ?? 'info',
    duration: input.duration ?? 4500,
  }
  state.items.push(toast)
  if (toast.duration > 0) {
    setTimeout(() => dismiss(toast.id), toast.duration)
  }
  return toast.id
}

export function useToast() {
  return {
    toasts: readonly(state).items,
    push,
    dismiss,
    success: (message: string, opts: Omit<ToastInput, 'message' | 'variant'> = {}) =>
      push({ ...opts, message, variant: 'success' }),
    error: (message: string, opts: Omit<ToastInput, 'message' | 'variant'> = {}) =>
      push({ ...opts, message, variant: 'error' }),
    info: (message: string, opts: Omit<ToastInput, 'message' | 'variant'> = {}) =>
      push({ ...opts, message, variant: 'info' }),
    warning: (message: string, opts: Omit<ToastInput, 'message' | 'variant'> = {}) =>
      push({ ...opts, message, variant: 'warning' }),
  }
}
