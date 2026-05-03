import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

const css = readFileSync(resolve(__dirname, '../css/app.css'), 'utf8')

describe('brand theme', () => {
  it('uses the logo coral as the primary brand color', () => {
    expect(css).toContain('--color-brand-500: #f06063;')
    expect(css).toContain('--color-astuce-500: #f06063;')
  })

  it('uses the logo teal as the accent color', () => {
    expect(css).toContain('--color-accent-600: #00645f;')
  })
})
