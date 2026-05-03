import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppLogo from './AppLogo.vue'

describe('AppLogo', () => {
  it('renders the uploaded logo asset', () => {
    const wrapper = mount(AppLogo)

    const logo = wrapper.get('img')

    expect(logo.attributes('src')).toBe('/logo.png')
    expect(logo.attributes('alt')).toBe("L'Astuce")
  })
})
