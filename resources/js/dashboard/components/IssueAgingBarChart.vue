<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Issue Aging</h3>
    <div v-if="loading" class="h-40 animate-pulse bg-gray-50 rounded-lg" />
    <div v-else-if="error" class="text-xs text-red-600 text-center py-10">{{ error }}</div>
    <div v-else-if="total === 0" class="text-xs text-gray-400 text-center py-10">No aging data</div>
    <div v-else>
      <apexchart type="bar" height="200" :options="chartOptions" :series="chartSeries" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CHART_COLORS, PALETTE } from '../composables/useChartColors.js'

const props = defineProps({
  buckets: { type: Object, default: () => ({}) },
  loading: Boolean,
  error: String,
})

const labels = Object.keys(props.buckets)
const values = Object.values(props.buckets)
const total = computed(() => values.reduce((s, v) => s + (v || 0), 0))

const chartSeries = computed(() => [{ name: 'Issues', data: values }])

const chartOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
  colors: [CHART_COLORS.warning],
  plotOptions: {
    bar: { horizontal: true, barHeight: '60%', borderRadius: 3, borderRadiusApplication: 'end' },
  },
  dataLabels: { enabled: true, formatter: v => v || '', style: { fontSize: '11px', colors: ['#374151'] } },
  xaxis: {
    categories: labels,
    labels: { style: { fontSize: '11px', colors: '#6b7280' } },
  },
  yaxis: {
    labels: { style: { fontSize: '11px', colors: '#6b7280' } },
  },
  grid: { borderColor: '#f3f4f6', strokeDashArray: 3 },
  tooltip: { y: { formatter: v => v.toLocaleString() } },
}))
</script>
