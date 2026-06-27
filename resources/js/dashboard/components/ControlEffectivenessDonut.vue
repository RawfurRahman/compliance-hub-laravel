<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Control Effectiveness</h3>
    <div v-if="loading" class="h-40 animate-pulse bg-gray-50 rounded-lg" />
    <div v-else-if="error" class="text-xs text-red-600 text-center py-10">{{ error }}</div>
    <div v-else-if="total === 0" class="text-xs text-gray-400 text-center py-10">No data</div>
    <div v-else>
      <apexchart type="donut" height="210" :options="chartOptions" :series="series" />
      <div class="flex justify-center gap-4 mt-1 text-xs text-gray-500">
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500" /> {{ pct(effective) }}</span>
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500" /> {{ pct(partial) }}</span>
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500" /> {{ pct(ineffective) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CHART_COLORS } from '../composables/useChartColors.js'

const props = defineProps({
  effective: { type: Number, default: 0 },
  partial: { type: Number, default: 0 },
  ineffective: { type: Number, default: 0 },
  loading: Boolean,
  error: String,
})

const total = computed(() => props.effective + props.partial + props.ineffective)

const series = computed(() => [props.effective, props.partial, props.ineffective])

const chartOptions = computed(() => ({
  chart: { type: 'donut', fontFamily: 'inherit' },
  labels: ['Effective', 'Partial', 'Ineffective'],
  colors: [CHART_COLORS.good, CHART_COLORS.warning, CHART_COLORS.bad],
  dataLabels: { enabled: false },
  legend: { show: false },
  plotOptions: {
    pie: {
      donut: { size: '60%', labels: { show: true, total: { show: true, label: 'Total', fontSize: '12px', color: '#6b7280', formatter: () => total.value.toLocaleString() } } },
    },
  },
  tooltip: { y: { formatter: v => v.toLocaleString() } },
}))

function pct(n) { return total.value > 0 ? Math.round(n / total.value * 100) + '%' : '0%' }
</script>
