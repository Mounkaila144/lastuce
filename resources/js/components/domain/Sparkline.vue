<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    values: number[]
    labels?: string[]
    width?: number
    height?: number
    stroke?: string
    fill?: string
    label?: string
  }>(),
  {
    width: 320,
    height: 80,
    stroke: '#ff7420',
    fill: 'rgba(255,116,32,0.12)',
  },
)

const max = computed(() => Math.max(1, ...props.values))
const padding = 2

const points = computed(() => {
  const n = props.values.length
  if (n === 0) return []
  const usableW = props.width - padding * 2
  const usableH = props.height - padding * 2
  return props.values.map((v, i) => {
    const x = n === 1 ? padding + usableW / 2 : padding + (i * usableW) / (n - 1)
    const y = padding + usableH - (v / max.value) * usableH
    return { x, y, v, label: props.labels?.[i] }
  })
})

const linePath = computed(() => {
  if (points.value.length === 0) return ''
  return points.value
    .map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x.toFixed(1)},${p.y.toFixed(1)}`)
    .join(' ')
})

const areaPath = computed(() => {
  if (points.value.length === 0) return ''
  const baseline = props.height - padding
  const first = points.value[0]
  const last = points.value[points.value.length - 1]
  return [
    `M${first.x.toFixed(1)},${baseline}`,
    ...points.value.map((p) => `L${p.x.toFixed(1)},${p.y.toFixed(1)}`),
    `L${last.x.toFixed(1)},${baseline}`,
    'Z',
  ].join(' ')
})

const total = computed(() => props.values.reduce((acc, v) => acc + v, 0))
</script>

<template>
  <div>
    <div class="mb-1 flex items-baseline justify-between">
      <p v-if="label" class="text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ label }}
      </p>
      <p class="text-xs text-slate-500">Total : {{ total.toLocaleString('fr-FR') }}</p>
    </div>
    <svg
      :viewBox="`0 0 ${width} ${height}`"
      :width="width"
      :height="height"
      class="w-full"
      role="img"
      :aria-label="label ?? 'Graphique des 30 derniers jours'"
      preserveAspectRatio="none"
    >
      <path :d="areaPath" :fill="fill" />
      <path :d="linePath" :stroke="stroke" stroke-width="1.5" fill="none" />
    </svg>
  </div>
</template>
