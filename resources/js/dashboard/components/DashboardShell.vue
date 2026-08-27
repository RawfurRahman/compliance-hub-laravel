<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">{{ title }}</h2>
      <div class="flex items-center gap-2 text-xs text-gray-400">
        <span>Last updated: {{ lastUpdated }}</span>
        <button @click="$emit('refresh')" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Refresh all">
          <svg class="w-4 h-4" :class="{ 'animate-spin': refreshing }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
      </div>
    </div>

    <!-- Online/Offline status indicator -->
    <div v-if="!onlineStatus" class="fixed bottom-4 left-4 right-4 bg-red-500 text-white text-sm py-2 rounded px-4 shadow-lg z-50">
      📡 Offline mode - Dashboard running from cached data. Some features may be limited.
    </div>

    <TopFilterBar :model-value="filters" :fields="filterFields" @update:model-value="$emit('update:filters', $event)" @reset="$emit('reset-filters')" />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <slot name="kpis" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <slot name="heatmap" />
      <slot name="inherent-residual" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <slot name="control-effectiveness" />
      <slot name="audit-findings" />
      <slot name="third-party-risk" />
    </div>

    <div>
      <slot name="top-risks" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <slot name="issue-aging" />
      <slot name="remediation-trend" />
    </div>

    <div>
      <slot name="compliance-scorecard" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import TopFilterBar from './TopFilterBar.vue'

const props = defineProps({
  title: { type: String, default: 'Enterprise Dashboard' },
  filters: { type: Object, default: () => ({}) },
  refreshing: { type: Boolean, default: false },
})

defineEmits(['refresh', 'update:filters', 'reset-filters'])

const now = ref(new Date())
const lastUpdated = computed(() => now.value.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }))

watch(() => props.refreshing, (val) => { if (!val) now.value = new Date() })

const isOnline = navigator.onLine
const onlineStatus = computed(() => isOnline)

const filterFields = [
  { key: 'businessUnit', label: 'Business Unit', options: ['IT Department', 'HR', 'Finance', 'Operations', 'Legal'] },
  { key: 'framework', label: 'Framework', options: ['PCI DSS', 'ISO 27001', 'SOC 2', 'NIST CSF', 'GDPR'] },
  { key: 'owner', label: 'Owner', options: [] },
  { key: 'riskStatus', label: 'Risk Status', options: ['High', 'Medium', 'Low', 'None'] },
]
</script>
