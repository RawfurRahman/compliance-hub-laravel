<template>
  <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Policy Governance</h3>
    
    <div class="relative">
      <div
        v-if="clipboard && clipboard.isDragOver"
        class="absolute inset-0 border-2 border-dashed border-blue-400 bg-blue-50 rounded-lg z-10 cursor-pointer transition-all"
        @dragover.prevent="clipboard.startDragOver"
        @dragleave.prevent="clipboard.endDragOver"
        @drop.prevent="clipboard.handleDrop"
        @click="$refs.clipboardInput.click()"
      >
        <div class="flex items-center justify-center h-full">
          <div class="text-center">
            <svg class="w-8 h-8 text-blue-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1118 6L7 16z" />
            </svg>
            <p class="text-sm text-blue-600 font-medium">Drop image here</p>
          </div>
        </div>
      </div>
      
      <input
        ref="clipboardInput"
        type="file"
        accept="image/png,image/jpeg,image/webp,image/heic,image/heif"
        class="hidden"
        @change="(e) => e.target.files && clipboard.processImageFile(e.target.files[0])"
      />
      
      <div
        class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-blue-400 transition-colors cursor-pointer relative"
        :class="clipboard.isProcessing ? 'opacity-50 pointer-events-none' : ''"
        @dragover.prevent="clipboard.startDragOver"
        @dragleave.prevent="clipboard.endDragOver"
        @drop.prevent="clipboard.handleDrop"
        @paste="clipboard.handlePaste"
        tabindex="0"
        @keydown.space.prevent="$el.click()"
      >
        <div v-if="loading" class="space-y-3 animate-pulse">
          <div v-for="i in 4" :key="i" class="h-12 bg-gray-50 rounded" />
        </div>
        
        <div v-else-if="error && clipboard.error && !loading" class="text-xs text-red-600 text-center py-2 mb-2 bg-red-50 rounded">
          <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ clipboard.error }}
        </div>
        
        <div v-else-if="clipboard.previewUrl" class="mb-4">
          <div class="relative inline-block">
            <img :src="clipboard.previewUrl" alt="Pasted image preview" class="max-h-32 rounded border border-gray-200" />
            <button
              @click.stop="clipboard.clearImage"
              class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors"
            >
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <p class="text-xs text-green-600 mt-2 font-medium">Image uploaded successfully</p>
        </div>
        
        <div v-else-if="loading" class="space-y-3 animate-pulse">
          <div v-for="i in 4" :key="i" class="h-12 bg-gray-50 rounded" />
        </div>
        
        <div v-else-if="error && !loading" class="text-xs text-red-600 text-center py-2 mb-2 bg-red-50 rounded">
          <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ error }}
        </div>
        
        <div v-else class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Total Policies</span>
            <span class="text-lg font-bold text-gray-900">{{ data.total_policies ?? 0 }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Active</span>
            <span class="text-lg font-bold text-emerald-600">{{ data.active_policies ?? 0 }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Overdue Reviews</span>
            <span class="text-lg font-bold text-red-600">{{ data.overdue_reviews ?? 0 }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">Pending Approvals</span>
            <span class="text-lg font-bold text-amber-600">{{ data.pending_approvals ?? 0 }}</span>
          </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-gray-500">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1118 6L7 16z" />
              </svg>
              <span>Press Ctrl+V to paste image (20MB max)</span>
            </div>
            <div class="text-xs text-gray-400">
              PNG, JPEG, WEBP, HEIC, HEIF
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, computed, ref, onMounted } from 'vue'
import { useClipboard } from '../composables/useClipboard.js'

const props = defineProps({
  data: { type: Object, default: () => ({}) },
  loading: Boolean,
  error: String,
})

const clipboard = useClipboard()

onMounted(() => {
  window.addEventListener('paste', clipboard.handlePaste)
})
</script>
