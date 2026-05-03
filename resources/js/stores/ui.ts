import { defineStore } from 'pinia'
import type { SupportedLocale } from '@/types/inertia'

interface UiState {
  locale: SupportedLocale
  mobileMenuOpen: boolean
}

export const useUiStore = defineStore('ui', {
  state: (): UiState => ({
    locale: 'fr',
    mobileMenuOpen: false,
  }),
  actions: {
    setLocale(locale: SupportedLocale) {
      this.locale = locale
    },
    openMobileMenu() {
      this.mobileMenuOpen = true
    },
    closeMobileMenu() {
      this.mobileMenuOpen = false
    },
    toggleMobileMenu() {
      this.mobileMenuOpen = !this.mobileMenuOpen
    },
  },
})
