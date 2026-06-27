<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Test Status Summary</h3>
    <div v-if="loading" class="h-40 animate-pulse bg-gray-50 rounded-lg" />
    <div v-else-if="error" class="text-xs text-red-600 text-center py-10">{{ error }}</div>
    <div v-else-if="total === 0" class="text-xs text-gray-400 text-center py-10">No tests</div>
    <div v-else>
      <apexchart type="donut" height="210" :options="chartOptions" :series="series" />
      <div class="flex justify-center gap-3 mt-1 text-xs text-gray-500 flex-wrap">
        <span v-for="s in statuses" :key="s.key" class="flex items-center gap-1">
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

const statuses = [
  { key: 'passing', label: 'Passing', color: '#059669' },
  { key: 'overdue', label: 'Overdue', color: '#dc2626' },
  { key: 'needs_remediation', label: 'Needs Remediation', color: '#d97706' },
  { key: 'due_soon', label: 'Due Soon', color: '#2563eb' },
  { key: 'not_yet_run', label: 'Not Yet Run', color: '#6b7280' },
]

const total = computed(() => props.data?.total ?? 0)
const series = computed(() => statuses.map(s => props.data[s.key] || 0))

const chartOptions = computed(() => ({
  chart: { type: 'donut', fontFamily: 'inherit' },
  labels: statuses.map(s => s.label),
  colors: statuses.map(s => s.color),
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
