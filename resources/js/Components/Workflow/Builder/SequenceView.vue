<template>
    <div class="bg-white h-full flex flex-col">
      <div class="p-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h2 class="font-medium text-lg text-gray-700">Workflow Sequence</h2>
          <button 
            @click="$emit('close')" 
            class="p-1.5 text-gray-500 hover:text-gray-700 rounded-full hover:bg-gray-100"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <p class="text-sm text-gray-500">Linear view of workflow execution</p>
      </div>
      
      <div class="flex-1 overflow-y-auto p-4">
        <div v-if="sortedNodes.length === 0" class="text-center py-16 text-gray-400">
          <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <p>No nodes in this workflow yet</p>
        </div>
        
        <div class="space-y-2">
          <template v-for="(node, index) in sortedNodes" :key="node.id">
            <!-- Node card -->
            <div 
              :class="['p-3 border rounded-lg flex items-center cursor-pointer hover:bg-gray-50', 
                { 'border-indigo-300 bg-indigo-50 hover:bg-indigo-50': selectedNodeId === node.id }]"
              @click="selectNode(node.id)"
            >
              <div 
                class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 flex-shrink-0"
                :style="{ backgroundColor: getNodeColor(node.type) + '20' }"
              >
                <component
                  :is="getNodeIcon(node.type)"
                  class="w-4 h-4"
                  :style="{ color: getNodeColor(node.type) }"
                />
              </div>
              
              <div class="flex-1 min-w-0">
                <div class="font-medium text-sm truncate">{{ node.label }}</div>
                <div class="text-xs text-gray-500">{{ getNodeDescription(node.type) }}</div>
              </div>
              
              <div class="flex items-center gap-2">
                <button
                  v-if="index !== 0" 
                  @click.stop="moveNodeUp(node.id)" 
                  class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                  </svg>
                </button>
                
                <button
                  v-if="index !== sortedNodes.length - 1" 
                  @click.stop="moveNodeDown(node.id)" 
                  class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray        
                 >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              
              <button 
                @click.stop="$emit('delete-node', node.id)" 
                class="p-1 text-gray-400 hover:text-red-500 rounded-full hover:bg-red-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Connection line -->
          <div v-if="index < sortedNodes.length - 1" class="h-8 w-px bg-gray-300 mx-auto relative">
            <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 flex justify-center">
              <div class="w-4 h-4 rounded-full bg-white border border-gray-300 flex items-center justify-center">
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
    
    <div class="p-4 border-t border-gray-200">
      <button 
        @click="optimizeSequence" 
        class="w-full py-2 px-4 bg-indigo-50 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        Optimize Sequence
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  nodes: {
    type: Array,
    required: true
  },
  connections: {
    type: Array,
    required: true
  },
  selectedNodeId: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['select-node', 'delete-node', 'reorder-nodes', 'close']);

// Topologically sort nodes based on connections
const sortedNodes = computed(() => {
  if (props.nodes.length === 0) return [];
  
  // Create a copy of nodes to avoid mutating props
  const nodes = [...props.nodes];
  
  // Find start nodes (those without incoming connections)
  const startNodeIds = nodes
    .filter(node => !props.connections.some(c => c.target === node.id))
    .map(node => node.id);
  
  if (startNodeIds.length === 0 && nodes.length > 0) {
    // If no start nodes found, just return nodes as is
    return nodes;
  }
  
  // Build dependency graph
  const graph = {};
  nodes.forEach(node => {
    graph[node.id] = [];
  });
  
  props.connections.forEach(conn => {
    if (graph[conn.source]) {
      graph[conn.source].push(conn.target);
    }
  });
  
  // Perform topological sort
  const visited = new Set();
  const tempVisited = new Set();
  const result = [];
  
  function visit(nodeId) {
    if (tempVisited.has(nodeId)) {
      // Cycle detected
      return;
    }
    
    if (visited.has(nodeId)) {
      return;
    }
    
    tempVisited.add(nodeId);
    
    const neighbors = graph[nodeId] || [];
    for (const neighbor of neighbors) {
      visit(neighbor);
    }
    
    tempVisited.delete(nodeId);
    visited.add(nodeId);
    
    const node = nodes.find(n => n.id === nodeId);
    if (node) {
      result.unshift(node);
    }
  }
  
  for (const nodeId of startNodeIds) {
    visit(nodeId);
  }
  
  // Add any remaining unvisited nodes
  for (const node of nodes) {
    if (!visited.has(node.id)) {
      result.push(node);
    }
  }
  
  return result;
});

// Select a node
const selectNode = (nodeId) => {
  emit('select-node', nodeId);
};

// Move a node up in the sequence
const moveNodeUp = (nodeId) => {
  const nodes = [...sortedNodes.value];
  const index = nodes.findIndex(n => n.id === nodeId);
  
  if (index > 0) {
    // Swap nodes
    [nodes[index - 1], nodes[index]] = [nodes[index], nodes[index - 1]];
    emit('reorder-nodes', nodes);
  }
};

// Move a node down in the sequence
const moveNodeDown = (nodeId) => {
  const nodes = [...sortedNodes.value];
  const index = nodes.findIndex(n => n.id === nodeId);
  
  if (index < nodes.length - 1) {
    // Swap nodes
    [nodes[index], nodes[index + 1]] = [nodes[index + 1], nodes[index]];
    emit('reorder-nodes', nodes);
  }
};

// Optimize node sequence
const optimizeSequence = () => {
  emit('reorder-nodes', sortedNodes.value);
};

// Utility functions for node visualization
const getNodeColor = (type) => {
  switch (type) {
    case 'webhook': return '#F59E0B';
    case 'http': return '#10B981';
    case 'agent': return '#6366F1';
    case 'condition': return '#EC4899';
    case 'transform': return '#8B5CF6';
    case 'scheduler': return '#3B82F6';
    case 'return': return '#EF4444';
    default: return '#6B7280';
  }
};

const getNodeIcon = (type) => {
  // Using the same icon function from NodeList.vue
  return {
    template: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${getIconPath(type)}</svg>`
  };
};

const getIconPath = (type) => {
  switch (type) {
    case 'webhook': return '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />';
    case 'http': return '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />';
    case 'agent': return '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />';
    case 'condition': return '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />';
    case 'transform': return '<path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />';
    case 'scheduler': return '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />';
    case 'return': return '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />';
    default: return '';
  }
};

const getNodeDescription = (type) => {
  switch (type) {
    case 'webhook': return 'Receive HTTP webhook events';
    case 'http': return 'Make an HTTP request';
    case 'agent': return 'Process with an AI agent';
    case 'condition': return 'Branch based on conditions';
    case 'transform': return 'Transform data with code';
    case 'scheduler': return 'Run on a schedule';
    case 'return': return 'Return workflow output';
    default: return '';
  }
};
</script>
         