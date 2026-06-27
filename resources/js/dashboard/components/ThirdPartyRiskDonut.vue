<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Third-Party Risk</h3>
    <div v-if="loading" class="h-40 animate-pulse bg-gray-50 rounded-lg" />
    <div v-else-if="error" class="text-xs text-red-600 text-center py-10">{{ error }}</div>
    <div v-else-if="total === 0" class="text-xs text-gray-400 text-center py-10">No vendors</div>
    <div v-else>
      <apexchart type="donut" height="210" :options="chartOptions" :series="series" />
      <div class="flex justify-center gap-3 mt-1 text-xs text-gray-500 flex-wrap">
        <span v-for="t in tiers" :key="t.key" class="flex items-center gap-1">
          <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: t.color }" /> {{ t.label }}: {{ breakdown[t.key] || 0 }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  breakdown: { type: Object, default: () => ({}) },
  totalVendors: { type: Number, default: 0 },
  loading: Boolean,
  error: String,
})

const tiers = [
  { key: 'critical', label: 'Critical', color: '#dc2626' },
  { key: 'high', label: 'High', color: '#d97706' },
  { key: 'medium', label: 'Medium', color: '#2563eb' },
  { key: 'low', label: 'Low', color: '#6b7280' },
]

const total = computed(() => props.totalVendors)
const series = computed(() => tiers.map(t => props.breakdown[t.key] || 0))

const chartOptions = computed(() => ({
  chart: { type: 'donut', fontFamily: 'inherit' },
  labels: tiers.map(t => t.label),
  colors: tiers.map(t => t.color),
  dataLabels: { enabled: false },
  legend: { show: false },
  plotOptions: {
    pie: {
      donut: { size: '60%', labels: { show: true, total: { show: true, label: 'Total', fontSize: '12px', color: '#6b7280', formatter: () => total.value.toLocaleString() } } },
    },
  },
  tooltip: { y: { formatter: v => v.toLocaleString() } },
}))
</script>
