export type SupportedLocale = 'fr' | 'en'

export type TranslationDictionary = Record<string, unknown>

export interface SharedProps {
  locale: SupportedLocale
  availableLocales: SupportedLocale[]
  translations: TranslationDictionary
  appName: string
  flash: {
    success?: string | null
    error?: string | null
    info?: string | null
  }
}

declare module '@inertiajs/core' {
  interface PageProps extends SharedProps {
    [key: string]: unknown
  }
}
