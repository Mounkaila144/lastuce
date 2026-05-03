import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import { createAppI18n } from '@/i18n'
import { useUiStore } from '@/stores/ui'
import type { SharedProps, SupportedLocale } from '@/types/inertia'

import '../css/app.css'

const appName = (import.meta.env.VITE_APP_NAME as string | undefined) ?? "L'Astuce"

createInertiaApp({
  title: (title) => (title ? `${title} — ${appName}` : appName),
  resolve: (name) => {
    const page = resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
    )
    page.then((module) => {
      const component = module.default
      if (component && !component.layout) {
        component.layout = DefaultLayout
      }
    })
    return page
  },
  setup({ el, App, props, plugin }) {
    const shared = props.initialPage.props as Partial<SharedProps>
    const locale = (shared.locale ?? 'fr') as SupportedLocale

    const pinia = createPinia()
    const i18n = createAppI18n({
      locale,
      laravelTranslations: shared.translations,
    })

    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(pinia)
      .use(i18n)

    // Hydrate UI store with the locale resolved server-side.
    useUiStore(pinia).setLocale(locale)

    app.mount(el)
  },
  progress: {
    color: '#f06063',
  },
})
