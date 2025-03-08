<template>
    <div class="space-y-2">
      <div class="text-xs text-gray-500 flex items-center">
        <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">Scheduler</span>
      </div>
      
      <div class="text-xs text-gray-600">
        <div class="flex items-center mb-1">
          <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span class="font-mono">{{ config.schedule || '0 0 * * *' }}</span>
        </div>
        <div v-if="config.timezone" class="flex items-center">
          <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span>{{ config.timezone }}</span>
        </div>
      </div>
      
      <div class="text-xs text-gray-500">
        {{ scheduleSummary }}
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue';
  
  const props = defineProps({
    config: {
      type: Object,
      required: true,
      default: () => ({ schedule: '0 0 * * *', timezone: 'UTC' })
    },
    nodeId: {
      type: String,
      required: true
    }
  });
  
  const scheduleSummary = computed(() => {
    const schedule = props.config.schedule || '0 0 * * *';
    
    // This is a simplified interpretation, a real app might use a library like cron-parser
    if (schedule === '0 0 * * *') {
      return 'Runs daily at midnight';
    } else if (schedule === '0 * * * *') {
      return 'Runs hourly';
    } else if (schedule === '0 0 * * 0') {
      return 'Runs weekly on Sunday';
    } else if (schedule === '0 0 1 * *') {
      return 'Runs monthly on the 1st';
    } else {
      return 'Custom schedule';
    }
  });
  </script>
  