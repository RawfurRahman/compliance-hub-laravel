<template>
  <div class="relative" :style="{ width: width + 'px', height: height + 'px' }">
    <svg
      v-if="showSparkline"
      :width="width"
      :height="height"
      :viewBox="`0 0 ${width} ${height}`"
      class="overflow-visible"
    >
      <defs>
        <linearGradient :id="gradientId" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" :stop-color="color" stop-opacity="0.15" />
          <stop offset="100%" :stop-color="color" stop-opacity="0.01" />
        </linearGradient>
      </defs>
      <path
        :d="areaPath"
        :fill="`url(#${gradientId})`"
      />
      <path
        :d="linePath"
        :stroke="color"
        stroke-width="1.5"
        fill="none"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
      <circle
        :cx="lastPoint.x"
        :cy="lastPoint.y"
        :r="2.5"
        :fill="color"
        stroke="white"
        stroke-width="1"
      />
    </svg>
    <div
      v-else-if="showInsufficient"
      class="flex items-center justify-center h-full text-[10px] text-gray-400 italic leading-tight px-1 text-center"
    >
      Not enough history yet
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  points: { type: Array, default: () => [] },
  color: { type: String, default: '#2563eb' },
  width: { type: Number, default: 80 },
  height: { type: Number, default: 32 },
  minPoints: { type: Number, default: 6 },
})

const uid = computed(() => Math.random().toString(36).slice(2, 8))
const gradientId = computed(() => `sparkline-grad-${uid.value}`)

const values = computed(() => props.points.map(p => p.value))
const showSparkline = computed(() => values.value.length >= props.minPoints)
const showInsufficient = computed(() => values.value.length > 0 && values.value.length < props.minPoints)

const minVal = computed(() => Math.min(...values.value))
const maxVal = computed(() => Math.max(...values.value))
const range = computed(() => Math.max(maxVal.value - minVal.value, 1))

const padding = 2
const drawWidth = computed(() => props.width - padding * 2)
const drawHeight = computed(() => props.height - padding * 2)

const scaledPoints = computed(() => {
  const n = values.value.length
  if (n === 0) return []
  return values.value.map((v, i) => ({
    x: padding + (i / (n - 1)) * drawWidth.value,
    y: padding + drawHeight.value - ((v - minVal.value) / range.value) * drawHeight.value,
  }))
})

const linePath = computed(() => {
  if (scaledPoints.value.length < 2) return ''
  return scaledPoints.value.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ')
})

const areaPath = computed(() => {
  if (scaledPoints.value.length < 2) return ''
  const start = scaledPoints.value[0]
  const end = scaledPoints.value[scaledPoints.value.length - 1]
  const bottom = padding + drawHeight.value
  return [
    `M${start.x.toFixed(1)},${bottom}`,
    linePath.value,
    `L${end.x.toFixed(1)},${bottom}`,
    'Z',
  ].join(' ')
})

const lastPoint = computed(() => scaledPoints.value[scaledPoints.value.length - 1] || { x: 0, y: 0 })
</script>
