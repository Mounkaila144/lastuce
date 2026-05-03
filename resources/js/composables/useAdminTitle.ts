import { ref, watchEffect, onScopeDispose } from 'vue'

const DEFAULT_TITLE = 'Administration'

export const adminPageTitle = ref<string>(DEFAULT_TITLE)

export function useAdminTitle(source: string | (() => string)): void {
  watchEffect(() => {
    adminPageTitle.value = typeof source === 'function' ? source() : source
  })
  onScopeDispose(() => {
    adminPageTitle.value = DEFAULT_TITLE
  })
}
