import { createI18n, type I18nOptions } from 'vue-i18n'
import fr from './fr.json'
import en from './en.json'
import type { SupportedLocale, TranslationDictionary } from '@/types/inertia'

const fallbackLocale: SupportedLocale = 'fr'

type FrontendMessages = typeof fr
type Messages = Record<SupportedLocale, FrontendMessages & { laravel?: TranslationDictionary }>

const messages: Messages = {
  fr,
  en,
}

export interface I18nBootstrap {
  locale: SupportedLocale
  laravelTranslations?: TranslationDictionary
}

export function createAppI18n({ locale, laravelTranslations }: I18nBootstrap) {
  // Backend Laravel translations are merged under the `laravel` namespace
  // so frontend keys (nav.home, common.search…) never collide with the
  // server-side message catalog. Use {{ $t('laravel.app.welcome') }}.
  if (laravelTranslations) {
    const target = (locale === 'en' ? messages.en : messages.fr) as FrontendMessages & {
      laravel?: TranslationDictionary
    }
    target.laravel = laravelTranslations
  }

  const options: I18nOptions = {
    legacy: false,
    locale,
    fallbackLocale,
    messages,
    missingWarn: false,
    fallbackWarn: false,
  }

  return createI18n(options)
}
