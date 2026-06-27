<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Inherent vs Residual Risk</h3>
    <div v-if="loading" class="h-64 animate-pulse bg-gray-50 rounded-lg" />
    <div v-else-if="error" class="text-xs text-red-600 text-center py-16">{{ error }}</div>
    <div v-else-if="!items.length" class="text-xs text-gray-400 text-center py-16">No data</div>
    <div v-else>
      <apexchart type="bar" height="280" :options="chartOptions" :series="chartSeries" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CHART_COLORS } from '../composables/useChartColors.js'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: Boolean,
  error: String,
})

const categories = computed(() => props.items.map(i => i.department || 'Unknown'))

const chartSeries = computed(() => [
  { name: 'Inherent', data: props.items.map(i => i.inherent || 0) },
  { name: 'Residual', data: props.items.map(i => i.residual || 0) },
])

const chartOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
  colors: [CHART_COLORS.info, CHART_COLORS.good],
  plotOptions: {
    bar: { horizontal: false, columnWidth: '60%', borderRadius: 4, borderRadiusApplication: 'end' },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: categories.value,
    labels: { style: { fontSize: '11px', colors: '#6b7280' } },
  },
  yaxis: {
    labels: { style: { fontSize: '11px', colors: '#6b7280' } },
  },
  grid: { borderColor: '#f3f4f6', strokeDashArray: 3 },
  tooltip: { y: { formatter: v => v.toLocaleString() } },
  legend: { position: 'top', fontSize: '12px', markers: { radius: 4 } },
}))
</script>
