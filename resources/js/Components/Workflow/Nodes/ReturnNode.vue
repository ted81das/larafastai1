<template>
    <div class="space-y-2">
      <div class="text-xs text-gray-500 flex items-center">
        <span class="px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-medium">Return</span>
      </div>
      
      <div class="text-xs text-gray-600">
        <div class="flex items-center">
          <svg class="w-4 h-4 mr-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
          </svg>
          <span>Returns workflow output as: <span class="font-mono">{{ outputVariable }}</span></span>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue';
  
  const props = defineProps({
    config: {
      type: Object,
      required: true,
      default: () => ({ output_variable: 'result' })
    },
    nodeId: {
      type: String,
      required: true
    }
  });
  
  const outputVariable = computed(() => {
    return props.config.output_variable || 'result';
  });
  </script>
  