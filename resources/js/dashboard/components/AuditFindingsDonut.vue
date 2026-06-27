<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Audit Findings</h3>
    <div v-if="loading" class="h-40 animate-pulse bg-gray-50 rounded-lg" />
    <div v-else-if="error" class="text-xs text-red-600 text-center py-10">{{ error }}</div>
    <div v-else-if="total === 0" class="text-xs text-gray-400 text-center py-10">No findings</div>
    <div v-else>
      <apexchart type="donut" height="210" :options="chartOptions" :series="series" />
      <div class="flex justify-center gap-3 mt-1 text-xs text-gray-500 flex-wrap">
        <span v-for="s in severities" :key="s.key" class="flex items-center gap-1">
          <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: s.color }" /> {{ s.label }}: {{ data[s.key] || 0 }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  data: { type: Object, default: () => ({}) },
  loading: Boolean,
  error: String,
})

const severities = [
  { key: 'critical', label: 'Critical', color: '#dc2626' },
  { key: 'high', label: 'High', color: '#d97706' },
  { key: 'medium', label: 'Medium', color: '#2563eb' },
  { key: 'low', label: 'Low', color: '#6b7280' },
]

const total = computed(() => severities.reduce((s, sev) => s + (props.data[sev.key] || 0), 0))

const series = computed(() => severities.map(s => props.data[s.key] || 0))

const chartOptions = computed(() => ({
  chart: { type: 'donut', fontFamily: 'inherit' },
  labels: severities.map(s => s.label),
  colors: severities.map(s => s.color),
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
