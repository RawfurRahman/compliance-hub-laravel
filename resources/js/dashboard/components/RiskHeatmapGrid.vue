<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-4">Risk Heatmap</h3>
    <div v-if="loading" class="space-y-2 animate-pulse">
      <div v-for="i in 5" :key="i" class="flex gap-2">
        <div v-for="j in 5" :key="j" class="h-10 w-full bg-gray-100 rounded" />
      </div>
    </div>
    <div v-else-if="error" class="text-xs text-red-600 text-center py-6">{{ error }}</div>
    <div v-else-if="!cells.length" class="text-xs text-gray-400 text-center py-6">No risk data</div>
    <div v-else>
      <div class="overflow-x-auto">
        <div class="min-w-[400px]">
          <div class="flex mb-1">
            <div class="w-20 shrink-0" />
            <div v-for="impact in impactLabels" :key="impact" class="flex-1 text-center text-[11px] font-medium text-gray-500 uppercase tracking-wide">{{ impact }}</div>
          </div>
          <div v-for="likelihood in likelihoodLabels" :key="likelihood" class="flex mb-1">
            <div class="w-20 shrink-0 flex items-center text-[11px] font-medium text-gray-500 pr-2">{{ likelihood }}</div>
            <div v-for="impact in impactLabels" :key="impact" class="flex-1 px-0.5">
              <div
                :class="cellClass(likelihood, impact)"
                class="h-10 rounded-lg flex items-center justify-center text-sm font-semibold transition-colors cursor-default"
                :title="`${likelihood} / ${impact}: ${cellCount(likelihood, impact)} findings`"
              >
                {{ cellCount(likelihood, impact) }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-3 mt-3 text-[11px] text-gray-500">
        <span class="font-medium">Low</span>
        <div class="flex gap-0.5">
          <div v-for="(cls, i) in gradientClasses" :key="i" class="w-5 h-3 rounded" :class="cls" />
        </div>
        <span class="font-medium">High</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  cells: { type: Array, default: () => [] },
  loading: Boolean,
  error: String,
})

const likelihoodLabels = ['Open', 'In Progress', 'Closed']
const impactLabels = ['Low', 'Medium', 'High']

const gradientClasses = ['bg-blue-50', 'bg-blue-100', 'bg-blue-200', 'bg-amber-200', 'bg-orange-300', 'bg-red-400']

const maxCount = computed(() => Math.max(...props.cells.map(c => c.count || 0), 1))

function cellCount(likelihood, impact) {
  const cell = props.cells.find(c => c.likelihood === likelihood && c.impact === impact)
  return cell?.count ?? 0
}

function cellClass(likelihood, impact) {
  const count = cellCount(likelihood, impact)
  const intensity = count / maxCount.value
  if (count === 0) return 'bg-gray-50 text-gray-300'
  if (intensity <= 0.2) return 'bg-blue-100 text-blue-700'
  if (intensity <= 0.4) return 'bg-blue-200 text-blue-800'
  if (intensity <= 0.6) return 'bg-amber-200 text-amber-800'
  if (intensity <= 0.8) return 'bg-orange-300 text-orange-900'
  return 'bg-red-400 text-white'
}
</script>
