<template>
  <div class="flex flex-wrap items-center gap-3 px-1">
    <div v-for="f in fields" :key="f.key" class="relative">
      <label class="block text-[11px] font-medium text-gray-500 mb-0.5 tracking-wide uppercase">{{ f.label }}</label>
      <select
        :value="modelValue[f.key]"
        @change="emitChange(f.key, $event.target.value || null)"
        class="appearance-none bg-white border border-gray-200 text-sm text-gray-700 rounded-lg px-3 py-2 pr-8 min-w-[140px] focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 cursor-pointer hover:border-gray-300 transition-colors"
      >
        <option value="">All {{ f.label }}</option>
        <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
      </select>
      <svg class="pointer-events-none absolute right-2.5 top-[34px] w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>

    <div class="relative">
      <label class="block text-[11px] font-medium text-gray-500 mb-0.5 tracking-wide uppercase">From</label>
      <input type="date" :value="modelValue.dateFrom" @change="emitChange('dateFrom', $event.target.value || null)"
        class="bg-white border border-gray-200 text-sm text-gray-700 rounded-lg px-3 py-2 min-w-[140px] focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 hover:border-gray-300 transition-colors" />
    </div>
    <div class="relative">
      <label class="block text-[11px] font-medium text-gray-500 mb-0.5 tracking-wide uppercase">To</label>
      <input type="date" :value="modelValue.dateTo" @change="emitChange('dateTo', $event.target.value || null)"
        class="bg-white border border-gray-200 text-sm text-gray-700 rounded-lg px-3 py-2 min-w-[140px] focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 hover:border-gray-300 transition-colors" />
    </div>

    <div class="self-end pb-0.5">
      <button @click="$emit('reset')"
        class="text-xs text-gray-400 hover:text-gray-600 hover:underline transition-colors px-2 py-2">
        Clear
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: Object, default: () => ({}) },
  fields: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'reset'])

function emitChange(key, value) {
  emit('update:modelValue', { ...arguments[0], [key]: value })
}
</script>
