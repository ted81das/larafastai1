<template>
    <div class="space-y-2">
      <div class="text-xs text-gray-500 flex items-center">
        <span class="px-2 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-medium">Transform</span>
      </div>
      
      <div v-if="hasScript" class="text-xs font-mono bg-gray-50 p-2 rounded border border-gray-200 max-h-24 overflow-y-auto">
        {{ scriptPreview }}
      </div>
      <div v-else class="text-xs text-gray-400">
        No transformation script defined
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue';
  
  const props = defineProps({
    config: {
      type: Object,
      required: true,
      default: () => ({ script: '' })
    },
    nodeId: {
      type: String,
      required: true
    }
  });
  
  const hasScript = computed(() => {
    return props.config.script && props.config.script.trim().length > 0;
  });
  
  const scriptPreview = computed(() => {
    if (!hasScript.value) return '';
    return props.config.script;
  });
  </script>
  