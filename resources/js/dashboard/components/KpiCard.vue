<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm flex flex-col gap-1.5">
    <div v-if="loading" class="space-y-3 animate-pulse">
      <div class="h-4 w-20 bg-gray-200 rounded" />
      <div class="h-8 w-24 bg-gray-200 rounded" />
      <div class="h-3 w-16 bg-gray-100 rounded" />
    </div>
    <template v-else-if="error">
      <div class="text-xs text-red-600 flex items-center gap-1.5">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ error }}</span>
      </div>
    </template>
    <template v-else>
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium tracking-wide uppercase" :class="labelColorClass">{{ label }}</span>
        <div v-if="$slots.icon" class="w-8 h-8 rounded-lg flex items-center justify-center" :class="iconBgClass">
          <slot name="icon" />
        </div>
      </div>
      <span class="text-2xl font-bold text-gray-900">{{ displayValue }}</span>
      <div v-if="subtitle" class="flex items-center gap-1 text-xs" :class="subtitleColorClass">
        <svg v-if="trendUp" class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
        <svg v-if="trendDown" class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        <span>{{ subtitle }}</span>
      </div>
      <TrendSparkline
        v-if="trendPoints"
        :points="trendPoints"
        :color="sparklineColor"
        :min-points="minTrendPoints"
        width="80"
        height="28"
      />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import TrendSparkline from './TrendSparkline.vue'

const props = defineProps({
  label: String,
  value: [Number, String],
  subtitle: String,
  color: { type: String, default: 'blue' },
  loading: Boolean,
  error: String,
  trendUp: Boolean,
  trendDown: Boolean,
  trendPoints: Array,
  minTrendPoints: { type: Number, default: 6 },
})

const colorMap = {
  blue: { label: 'text-blue-600', icon: 'bg-blue-50 text-blue-600' },
  emerald: { label: 'text-emerald-600', icon: 'bg-emerald-50 text-emerald-600' },
  amber: { label: 'text-amber-600', icon: 'bg-amber-50 text-amber-600' },
  red: { label: 'text-red-600', icon: 'bg-red-50 text-red-600' },
  purple: { label: 'text-purple-600', icon: 'bg-purple-50 text-purple-600' },
  gray: { label: 'text-gray-600', icon: 'bg-gray-50 text-gray-600' },
}

const labelColorClass = computed(() => colorMap[props.color]?.label || colorMap.blue.label)
const iconBgClass = computed(() => colorMap[props.color]?.icon?.split(' ')[0] || 'bg-blue-50')
const iconColorClass = computed(() => colorMap[props.color]?.icon?.split(' ')[1] || 'text-blue-600')
const subtitleColorClass = computed(() => {
  if (props.trendDown) return 'text-red-500'
  if (props.trendUp) return 'text-emerald-500'
  return 'text-gray-500'
})

const sparklineHexMap = {
  blue: '#2563eb',
  emerald: '#059669',
  amber: '#d97706',
  red: '#dc2626',
  purple: '#7c3aed',
  gray: '#6b7280',
}
const sparklineColor = computed(() => sparklineHexMap[props.color] || '#2563eb')

const displayValue = computed(() => {
  if (props.value === null || props.value === undefined) return '—'
  if (typeof props.value === 'number') return props.value.toLocaleString()
  return props.value
})
</script>
