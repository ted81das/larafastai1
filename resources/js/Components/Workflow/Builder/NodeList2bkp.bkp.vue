<template>
    <div class="bg-white h-full flex flex-col">
      <div class="p-4 border-b border-gray-200">
        <h2 class="font-medium text-lg text-gray-700">Node Types</h2>
        <p class="text-sm text-gray-500">Drag and drop to add to canvas</p>
      </div>
      
      <div class="flex-1 overflow-y-auto p-2">
        <div class="space-y-2">
          <div
            v-for="nodeType in nodeTypes"
            :key="nodeType.type"
            class="p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 cursor-move flex items-center"
            draggable="true"
            @dragstart="handleDragStart($event, nodeType)"
            @click="addNode(nodeType)"
          >
            <div 
              class="w-9 h-9 rounded-lg flex items-center justify-center mr-3"
              :style="{ backgroundColor: nodeType.color + '20' }"
            >
              <component
                :is="getNodeIcon(nodeType.icon)"
                class="w-5 h-5"
                :style="{ color: nodeType.color }"
              />
            </div>
            <div>
              <div class="font-medium text-sm">{{ nodeType.label }}</div>
              <div class="text-xs text-gray-500">{{ getNodeDescription(nodeType.type) }}</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="p-3 border-t border-gray-200">
        <button 
          class="w-full py-2 px-4 bg-indigo-50 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-100 flex items-center justify-center"
          @click="$emit('toggle-sequence-view')"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
          Sequence View
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  
  const props = defineProps({
    nodeTypes: {
      type: Array,
      required: true
    }
  });
  
  const emit = defineEmits(['add-node', 'toggle-sequence-view']);
  
  // Handle drag start event
  const handleDragStart = (event, nodeType) => {
    event.dataTransfer.setData('nodeType', JSON.stringify(nodeType));
    event.dataTransfer.effectAllowed = 'copy';
  };
  
  // Add a node directly by clicking 
  const addNode = (nodeType) => {
    emit('add-node', nodeType);
  };
  
  // Get description for a node type
  const getNodeDescription = (type) => {
    switch (type) {
      case 'webhook':
        return 'Receive HTTP webhook events';
      case 'http':
        return 'Make an HTTP request';
      case 'agent':
        return 'Process with an AI agent';
      case 'condition':
        return 'Branch based on conditions';
      case 'transform':
        return 'Transform data with code';
      case 'scheduler':
        return 'Run on a schedule';
      case 'return':
        return 'Return workflow output';
      default:
        return '';
    }
  };
  
  // Get icon component for a node type
  const getNodeIcon = (icon) => {
    // This would ideally map to your icon components
    // Here I'm using a simple function to render SVG paths
    return {
      template: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${getIconPath(icon)}</svg>`
    };
  };
  
  const getIconPath = (icon) => {
    switch (icon) {
      case 'webhook':
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />';
      case 'globe':
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />';
      case 'robot':
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />';
      case 'switch':
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />';
      case 'code':
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />';
      case 'clock':
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />';
      case 'reply':
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />';
      default:
        return '';
    }
  };
  </script>
  