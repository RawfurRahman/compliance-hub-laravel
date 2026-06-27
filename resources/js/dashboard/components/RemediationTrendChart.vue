<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Remediation Trend</h3>
    <div v-if="loading" class="h-56 animate-pulse bg-gray-50 rounded-lg" />
    <div v-else-if="error" class="text-xs text-red-600 text-center py-14">{{ error }}</div>
    <div v-else-if="!monthly.length" class="text-xs text-gray-400 text-center py-14">No trend data</div>
    <div v-else>
      <apexchart type="area" height="250" :options="chartOptions" :series="chartSeries" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CHART_COLORS } from '../composables/useChartColors.js'

const props = defineProps({
  monthly: { type: Array, default: () => [] },
  loading: Boolean,
  error: String,
})

const categories = computed(() => props.monthly.map(m => m.month || ''))

const chartSeries = computed(() => [
  { name: 'Opened', data: props.monthly.map(m => m.opened || 0) },
  { name: 'Closed', data: props.monthly.map(m => m.closed || 0) },
])

const chartOptions = computed(() => ({
  chart: { type: 'area', toolbar: { show: false }, fontFamily: 'inherit' },
  colors: [CHART_COLORS.bad, CHART_COLORS.good],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 0.1, opacityFrom: 0.3, opacityTo: 0 } },
  xaxis: {
    categories: categories.value,
    labels: { style: { fontSize: '11px', colors: '#6b7280' } },
  },
  yaxis: {
    labels: { style: { fontSize: '11px', colors: '#6b7280' } },
    min: 0,
  },
  grid: { borderColor: '#f3f4f6', strokeDashArray: 3 },
  tooltip: { shared: true, y: { formatter: v => v.toLocaleString() } },
  legend: { position: 'top', fontSize: '12px', markers: { radius: 4 } },
}))
</script>
