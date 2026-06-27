<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Top Ranked Risks</h3>
    <div v-if="loading" class="space-y-2 animate-pulse">
      <div v-for="i in 5" :key="i" class="h-10 bg-gray-100 rounded" />
    </div>
    <div v-else-if="error" class="text-xs text-red-600 text-center py-6">{{ error }}</div>
    <div v-else-if="!rankings.length" class="text-xs text-gray-400 text-center py-6">No risks ranked</div>
    <div v-else class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="border-b border-gray-100 text-[11px] font-medium text-gray-500 uppercase tracking-wide">
            <th class="pb-2 pr-2 w-8">#</th>
            <th class="pb-2 pr-3">Control</th>
            <th class="pb-2 pr-3 hidden sm:table-cell">Framework</th>
            <th class="pb-2 pr-3 hidden md:table-cell">Project</th>
            <th class="pb-2 pr-2">Risk</th>
            <th class="pb-2 text-right">Score</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(r, i) in rankings" :key="r.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
            <td class="py-2.5 pr-2 text-gray-400 text-xs">{{ i + 1 }}</td>
            <td class="py-2.5 pr-3 font-medium text-gray-800 text-sm max-w-[200px] truncate" :title="r.title">{{ r.control || r.title }}</td>
            <td class="py-2.5 pr-3 text-gray-500 hidden sm:table-cell">{{ r.framework }}</td>
            <td class="py-2.5 pr-3 text-gray-500 text-xs hidden md:table-cell">{{ r.project }}</td>
            <td class="py-2.5 pr-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ring-1 ring-inset" :class="riskBadgeClass(r.risk)">{{ r.risk }}</span>
            </td>
            <td class="py-2.5 text-right font-mono text-sm text-gray-700">{{ r.risk_score }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { riskBadgeClass } from '../../dashboard/utils/formatters.js'

defineProps({
  rankings: { type: Array, default: () => [] },
  loading: Boolean,
  error: String,
})
</script>
