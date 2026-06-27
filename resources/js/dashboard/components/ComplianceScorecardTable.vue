<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Compliance Scorecard</h3>
    <div v-if="loading" class="space-y-2 animate-pulse">
      <div v-for="i in 4" :key="i" class="h-12 bg-gray-100 rounded" />
    </div>
    <div v-else-if="error" class="text-xs text-red-600 text-center py-6">{{ error }}</div>
    <div v-else-if="!scorecard.length" class="text-xs text-gray-400 text-center py-6">No frameworks</div>
    <div v-else class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="border-b border-gray-100 text-[11px] font-medium text-gray-500 uppercase tracking-wide">
            <th class="pb-2 pr-3">Framework</th>
            <th class="pb-2 pr-3">Phase</th>
            <th class="pb-2 pr-3 w-full">Progress</th>
            <th class="pb-2 pr-3 text-right">Finding Compliance</th>
            <th class="pb-2 text-right">Test Pass Rate</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="f in scorecard" :key="f.framework" class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
            <td class="py-3 pr-3 font-medium text-gray-800">{{ f.framework }}</td>
            <td class="py-3 pr-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ring-1 ring-inset" :class="phaseBadgeClass(f.phase)">
                {{ phaseLabel(f.phase) }}
              </span>
            </td>
            <td class="py-3 pr-3">
              <div class="w-full bg-gray-100 rounded-full h-2 max-w-[200px]">
                <div class="h-2 rounded-full transition-all duration-500" :class="progressBarClass(f.percentage)" :style="{ width: f.percentage + '%' }" />
              </div>
            </td>
            <td class="py-3 pr-3 text-right font-mono text-sm" :class="pctTextClass(f.percentage)">{{ f.percentage.toFixed(1) }}%</td>
            <td class="py-3 text-right font-mono text-sm" :class="testRateTextClass(f.test_pass_rate)">
              <template v-if="f.test_pass_rate !== null">{{ f.test_pass_rate.toFixed(1) }}%</template>
              <span v-else class="text-gray-300 text-xs">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { phaseBadgeClass, phaseLabel } from '../../dashboard/utils/formatters.js'

defineProps({
  scorecard: { type: Array, default: () => [] },
  loading: Boolean,
  error: String,
})

function progressBarClass(pct) {
  if (pct >= 80) return 'bg-emerald-500'
  if (pct >= 50) return 'bg-amber-500'
  if (pct >= 25) return 'bg-orange-500'
  return 'bg-red-500'
}

function pctTextClass(pct) {
  if (pct >= 80) return 'text-emerald-600'
  if (pct >= 50) return 'text-amber-600'
  if (pct >= 25) return 'text-orange-600'
  return 'text-red-600'
}

function testRateTextClass(rate) {
  if (rate === null) return ''
  if (rate >= 80) return 'text-indigo-600'
  if (rate >= 50) return 'text-amber-600'
  return 'text-red-600'
}
</script>
