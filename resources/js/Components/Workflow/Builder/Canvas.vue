<template>
    <div class="flex h-full">
      <!-- Left sidebar with available nodes -->
      <NodeList 
        :node-types="nodeTypes" 
        @add-node="addNodeToCanvas" 
        class="w-64 border-r border-gray-200 overflow-auto"
      />
      
      <!-- Main canvas area -->
      <div 
        ref="canvasContainer" 
        class="flex-1 bg-gray-50 relative overflow-auto" 
        @click="handleCanvasClick"
        @mousemove="handleMouseMove"
        @mouseup="handleMouseUp"
      >
        <!-- Grid background -->
        <div class="absolute inset-0 grid-pattern"></div>
        
        <!-- Zoom and pan controls -->
        <div class="absolute top-4 right-4 bg-white shadow-md rounded-md p-2 z-10 flex">
          <button @click="zoomIn" class="p-1 hover:bg-gray-100 rounded">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
          </button>
          <button @click="zoomOut" class="p-1 hover:bg-gray-100 rounded">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"></path>
            </svg>
          </button>
          <button @click="resetView" class="p-1 hover:bg-gray-100 rounded">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"></path>
            </svg>
          </button>
        </div>
        
        <!-- Connection lines between nodes -->
        <svg class="absolute inset-0 pointer-events-none z-10" :width="canvasWidth" :height="canvasHeight">
          <g :transform="`translate(${panOffset.x}, ${panOffset.y}) scale(${zoomLevel})`">
            <path
              v-for="(connection, index) in connections"
              :key="`connection-${index}`"
              :d="calculatePath(connection)"
              stroke="#6366F1"
              stroke-width="2"
              fill="none"
              marker-end="url(#arrowhead)"
            ></path>
            
            <!-- Dragging connection line -->
            <path
              v-if="isConnecting"
              :d="calculateDraggingPath()"
              stroke="#6366F1"
              stroke-width="2"
              stroke-dasharray="5,5"
              fill="none"
            ></path>
          </g>
          
          <!-- Arrow marker definition -->
          <defs>
            <marker
              id="arrowhead"
              viewBox="0 0 10 10"
              refX="8"
              refY="5"
              markerWidth="6"
              markerHeight="6"
              orient="auto"
            >
              <path d="M 0 0 L 10 5 L 0 10 z" fill="#6366F1"></path>
            </marker>
          </defs>
        </svg>
        
        <!-- Nodes -->
        <div 
          :style="`transform: translate(${panOffset.x}px, ${panOffset.y}px) scale(${zoomLevel})`" 
          class="absolute left-0 top-0 origin-top-left"
        >
          <NodeCard
            v-for="node in nodes"
            :key="node.id"
            :node="node"
            :selected="selectedNode && selectedNode.id === node.id"
            @select="selectNode"
            @delete="deleteNode"
            @move="moveNode"
            @connect-start="startConnectionDrag"
            @connect-end="endConnectionDrag"
          />
        </div>
        
        <!-- Empty state -->
        <div 
          v-if="nodes.length === 0" 
          class="absolute inset-0 flex items-center justify-center text-gray-400"
        >
          <div class="text-center">
            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <p class="text-lg">Drag and drop nodes from the sidebar to get started</p>
          </div>
        </div>
      </div>
      
      <!-- Right sidebar for node configuration -->
      <NodeConfig 
        v-if="selectedNode" 
        :node="selectedNode" 
        :available-nodes="nodes"
        @update="updateNodeConfig" 
        class="w-72 border-l border-gray-200 overflow-auto"
      />
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted, nextTick } from 'vue';
  import NodeList from './NodeList.vue';
  import NodeCard from './NodeCard.vue';
  import NodeConfig from './NodeConfig.vue';
  
  const props = defineProps({
    initialWorkflow: {
      type: Object,
      default: () => ({ 
        id: null, 
        name: 'New Workflow', 
        nodes: [], 
        connections: [] 
      })
    }
  });
  
  // Canvas state
  const canvasContainer = ref(null);
  const canvasWidth = ref(2000);
  const canvasHeight = ref(2000);
  const zoomLevel = ref(1);
  const panOffset = ref({ x: 100, y: 100 });
  
  // Nodes and connections
  const nodes = ref([]);
  const connections = ref([]);
  const selectedNode = ref(null);
  
  // Connection dragging state
  const isConnecting = ref(false);
  const connectionStart = ref(null);
  const connectionEnd = ref({ x: 0, y: 0 });
  
  // Available node types
  const nodeTypes = ref([
    { type: 'webhook', label: 'Webhook', icon: 'webhook', color: '#F59E0B' },
    { type: 'http', label: 'HTTP Request', icon: 'globe', color: '#10B981' },
    { type: 'agent', label: 'AI Agent', icon: 'robot', color: '#6366F1' },
    { type: 'condition', label: 'Condition', icon: 'switch', color: '#EC4899' },
    { type: 'transform', label: 'Transform', icon: 'code', color: '#8B5CF6' },
    { type: 'scheduler', label: 'Scheduler', icon: 'clock', color: '#3B82F6' },
    { type: 'return', label: 'Return', icon: 'reply', color: '#EF4444' },
  ]);
  
  // Initialize workflow data
  onMounted(() => {
    if (props.initialWorkflow) {
      nodes.value = [...props.initialWorkflow.nodes];
      connections.value = [...props.initialWorkflow.connections];
      
      // Set canvas dimensions based on content
      nextTick(() => {
        updateCanvasDimensions();
      });
    }
  });
  
  // Handle canvas interactions
  const handleCanvasClick = (event) => {
    // Deselect node if clicking on empty canvas
    if (event.target === canvasContainer.value) {
      selectedNode.value = null;
    }
  };
  
  // Add a new node to the canvas
  const addNodeToCanvas = (nodeType) => {
    const id = `node-${Date.now()}`;
    const position = {
      x: -panOffset.value.x / zoomLevel.value + 200,
      y: -panOffset.value.y / zoomLevel.value + 200
    };
    
    const newNode = {
      id,
      type: nodeType.type,
      label: nodeType.label,
      position,
      config: getDefaultConfigForType(nodeType.type),
      inputs: getInputsForType(nodeType.type),
      outputs: getOutputsForType(nodeType.type),
    };
    
    nodes.value.push(newNode);
    selectedNode.value = newNode;
    updateCanvasDimensions();
  };
  
  // Calculate path for connections between nodes
  const calculatePath = (connection) => {
    const source = nodes.value.find(n => n.id === connection.source);
    const target = nodes.value.find(n => n.id === connection.target);
    
    if (!source || !target) return '';
    
    const sourceX = source.position.x + 150; // Right side of source node
    const sourceY = source.position.y + 40;  // Middle of source node
    const targetX = target.position.x;       // Left side of target node
    const targetY = target.position.y + 40;  // Middle of target node
    
    const controlPointX = (targetX - sourceX) / 2;
    
    return `M ${sourceX} ${sourceY} C ${sourceX + controlPointX} ${sourceY}, ${targetX - controlPointX} ${targetY}, ${targetX} ${targetY}`;
  };
  
  // Calculate path for current dragging connection
  const calculateDraggingPath = () => {
    const source = nodes.value.find(n => n.id === connectionStart.value.nodeId);
    
    if (!source) return '';
    
    const sourceX = source.position.x + 150; // Right side of source node
    const sourceY = source.position.y + 40;  // Middle of source node
    
    const controlPointX = (connectionEnd.value.x - sourceX) / 2;
    
    return `M ${sourceX} ${sourceY} C ${sourceX + controlPointX} ${sourceY}, ${connectionEnd.value.x - controlPointX} ${connectionEnd.value.y}, ${connectionEnd.value.x} ${connectionEnd.value.y}`;
  };
  
  // Start dragging a new connection
  const startConnectionDrag = (nodeId, outputIndex) => {
    isConnecting.value = true;
    connectionStart.value = { nodeId, outputIndex };
  };
  
  // Mouse move during connection drag
  const handleMouseMove = (event) => {
    if (isConnecting.value) {
      // Calculate mouse position relative to canvas and zoom
      const rect = canvasContainer.value.getBoundingClientRect();
      connectionEnd.value = {
        x: (event.clientX - rect.left - panOffset.value.x) / zoomLevel.value,
        y: (event.clientY - rect.top - panOffset.value.y) / zoomLevel.value
      };
    }
  };
  
  // End connection drag on a node input
  const endConnectionDrag = (targetNodeId, inputIndex) => {
    if (isConnecting.value && connectionStart.value) {
      // Add the new connection
      connections.value.push({
        source: connectionStart.value.nodeId,
        sourceHandle: connectionStart.value.outputIndex,
        target: targetNodeId,
        targetHandle: inputIndex
      });
      
      // Also update node config to reference next node
      const sourceNode = nodes.value.find(n => n.id === connectionStart.value.nodeId);
      if (sourceNode) {
        if (!sourceNode.config.next_nodes) {
          sourceNode.config.next_nodes = [];
        }
        sourceNode.config.next_nodes[connectionStart.value.outputIndex] = targetNodeId;
      }
    }
    
    // Reset connection dragging state
    isConnecting.value = false;
    connectionStart.value = null;
  };
  
  // Handle mouse up anywhere to cancel connection
  const handleMouseUp = () => {
    if (isConnecting.value) {
      isConnecting.value = false;
      connectionStart.value = null;
    }
  };
  
  // Node selection
  const selectNode = (node) => {
    selectedNode.value = node;
  };
  
  // Delete a node
  const deleteNode = (nodeId) => {
    nodes.value = nodes.value.filter(n => n.id !== nodeId);
    connections.value = connections.value.filter(
      c => c.source !== nodeId && c.target !== nodeId
    );
    
    if (selectedNode.value && selectedNode.value.id === nodeId) {
      selectedNode.value = null;
    }
  };
  
  // Move a node
  const moveNode = (nodeId, newPosition) => {
    const nodeIndex = nodes.value.findIndex(n => n.id === nodeId);
    if (nodeIndex >= 0) {
      nodes.value[nodeIndex].position = newPosition;
      updateCanvasDimensions();
    }
  };
  
  // Update node configuration
  const updateNodeConfig = (nodeId, newConfig) => {
    const nodeIndex = nodes.value.findIndex(n => n.id === nodeId);
    if (nodeIndex >= 0) {
      nodes.value[nodeIndex].config = { ...newConfig };
    }
  };
  
  // Zoom controls
  const zoomIn = () => {
    zoomLevel.value = Math.min(zoomLevel.value + 0.1, 2);
  };
  
  const zoomOut = () => {
    zoomLevel.value = Math.max(zoomLevel.value - 0.1, 0.5);
  };
  
  const resetView = () => {
    zoomLevel.value = 1;
    panOffset.value = { x: 100, y: 100 };
  };
  
  // Update canvas dimensions based on node positions
  const updateCanvasDimensions = () => {
    if (nodes.value.length === 0) return;
    
    let maxX = 0;
    let maxY = 0;
    
    nodes.value.forEach(node => {
        nodes.value.forEach(node => {
    const nodeRight = node.position.x + 300;
    const nodeBottom = node.position.y + 80;
    
    maxX = Math.max(maxX, nodeRight);
    maxY = Math.max(maxY, nodeBottom);
  });
  
  // Add some padding
  canvasWidth.value = Math.max(maxX + 200, 2000);
  canvasHeight.value = Math.max(maxY + 200, 2000);
};

// Utility functions for node types
const getDefaultConfigForType = (type) => {
  switch (type) {
    case 'webhook':
      return { path: '', method: 'POST' };
    case 'http':
      return { url: '', method: 'GET', headers: {}, body: '' };
    case 'agent':
      return { agent_id: null, input: '', input_from_context: '' };
    case 'condition':
      return { condition: '', true_node_id: null, false_node_id: null };
    case 'transform':
      return { script: 'return input;' };
    case 'scheduler':
      return { schedule: '0 0 * * *', timezone: 'UTC' };
    case 'return':
      return { output_variable: 'result' };
    default:
      return {};
  }
};

const getInputsForType = (type) => {
  switch (type) {
    case 'webhook':
      return [];
    case 'condition':
      return [{ name: 'input', type: 'any' }];
    case 'return':
      return [{ name: 'input', type: 'any' }];
    default:
      return [{ name: 'input', type: 'any' }];
  }
};

const getOutputsForType = (type) => {
  switch (type) {
    case 'condition':
      return [
        { name: 'true', type: 'boolean' },
        { name: 'false', type: 'boolean' }
      ];
    case 'return':
      return [];
    default:
      return [{ name: 'output', type: 'any' }];
  }
};
</script>

<style scoped>
.grid-pattern {
  background-size: 20px 20px;
  background-image: linear-gradient(to right, #e5e7eb 1px, transparent 1px),
                    linear-gradient(to bottom, #e5e7eb 1px, transparent 1px);
}
</style>
