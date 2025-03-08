<template>
    <div class="space-y-2">
      <div class="text-xs text-gray-500 flex items-center">
        <span class="px-2 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-medium">{{ config.method || 'POST' }}</span>
        <span class="ml-2 font-mono">{{ webhookPath }}</span>
      </div>
      
      <div class="text-xs text-gray-500">
        <span class="block">Listening for webhook events</span>
        <span v-if="config.path" class="block text-gray-600 font-medium">
          {{ webhookUrl }}
        </span>
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue';
  
  const props = defineProps({
    config: {
      type: Object,
      required: true,
      default: () => ({ path: '', method: 'POST' })
    },
    nodeId: {
      type: String,
      required: true
    }
  });
  
  const webhookPath = computed(() => {
    return props.config.path || '/[path]';
  });
  
  const webhookUrl = computed(() => {
    return `${window.location.origin}/api/workflows/webhooks${webhookPath.value}`;
  });
  </script>
  