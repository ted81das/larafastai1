<template>
    <div class="space-y-2">
      <div class="text-xs text-gray-500 flex items-center">
        <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-medium">{{ config.method || 'GET' }}</span>
        <span class="ml-2 font-mono truncate">{{ displayUrl }}</span>
      </div>
      
      <div class="text-xs text-gray-500">
        <span v-if="hasHeaders" class="block">Headers: {{ headerCount }}</span>
        <span v-if="hasBody" class="block">Body: {{ bodyPreview }}</span>
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue';
  
  const props = defineProps({
    config: {
      type: Object,
      required: true,
      default: () => ({ url: '', method: 'GET', headers: {}, body: '' })
    },
    nodeId: {
      type: String,
      required: true
    }
  });
  
  const displayUrl = computed(() => {
    return props.config.url || 'https://example.com/api';
  });
  
  const hasHeaders = computed(() => {
    return props.config.headers && Object.keys(props.config.headers).length > 0;
  });
  
  const headerCount = computed(() => {
    return Object.keys(props.config.headers || {}).length;
  });
  
  const hasBody = computed(() => {
    return props.config.body && props.config.body.trim().length > 0;
  });
  
  const bodyPreview = computed(() => {
    if (!hasBody.value) return '';
    
    const body = props.config.body.trim();
    if (body.length > 30) {
      return body.substring(0, 30) + '...';
    }
    return body;
  });
  </script>
  