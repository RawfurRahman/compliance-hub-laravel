<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Ownership & Accountability</h3>
    <div v-if="loading" class="space-y-2 animate-pulse">
      <div v-for="i in 5" :key="i" class="h-10 bg-gray-100 rounded" />
    </div>
    <div v-else-if="error" class="text-xs text-red-600 text-center py-6">{{ error }}</div>
    <div v-else>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="bg-gray-50 rounded-lg p-3 text-center">
          <div class="text-lg font-bold text-gray-900">{{ ownership.total_assignments ?? 0 }}</div>
          <div class="text-[11px] text-gray-500">Assignments</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
          <div class="text-lg font-bold text-blue-600">{{ ownership.primary_owners ?? 0 }}</div>
          <div class="text-[11px] text-gray-500">Primary Owners</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
          <div class="text-lg font-bold text-emerald-600">{{ ownership.domain_coverage_pct ?? 0 }}%</div>
          <div class="text-[11px] text-gray-500">Domain Coverage</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
          <div class="text-lg font-bold text-amber-600">{{ sla.sla_compliance_pct ?? 100 }}%</div>
          <div class="text-[11px] text-gray-500">SLA Compliance</div>
        </div>
      </div>
      <div v-if="sla.total_trackers > 0" class="flex flex-wrap gap-3 text-xs text-gray-500">
        <span>Total SLA: <strong class="text-gray-700">{{ sla.total_trackers }}</strong></span>
        <span class="text-red-500">Breached: <strong>{{ sla.breached }}</strong></span>
        <span class="text-amber-500">At Risk: <strong>{{ sla.at_risk }}</strong></span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  ownership: { type: Object, default: () => ({}) },
  sla: { type: Object, default: () => ({}) },
  loading: Boolean,
  error: String,
})
</script>
