import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

const css = readFileSync(resolve(__dirname, '../css/app.css'), 'utf8')

describe('brand theme', () => {
  it('uses the logo navy as the primary brand color', () => {
    expect(css).toContain('--color-brand-700: #272757;')
    expect(css).toContain('--color-astuce-700: #272757;')
  })

  it('uses the logo orange as the accent color', () => {
    expect(css).toContain('--color-accent-500: #ff7420;')
  })
})
