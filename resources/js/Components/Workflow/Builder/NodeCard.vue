<template>
    <div 
      :class="['node-card', { 'node-selected': selected }]" 
      :style="nodeStyle"
      @mousedown="startDrag"
      @click.stop="$emit('select', node)"
    >
      <!-- Node header -->
      <div class="node-header" :style="{ backgroundColor: getNodeColor() + '20' }">
        <div class="flex items-center">
          <component
            :is="getNodeIcon(node.type)"
            class="w-5 h-5 mr-2"
            :style="{ color: getNodeColor() }"
          />
          <span class="font-medium text-sm">{{ node.label }}</span>
        </div>
        
        <div class="flex space-x-1">
          <button 
            @click.stop="$emit('delete', node.id)" 
            class="p-1 text-gray-500 hover:text-red-500 rounded-full hover:bg-red-50"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>
        </div>
      </div>
      
      <!-- Node content -->
      <div class="node-content">
        <!-- Node-specific component -->
        <component 
          :is="getNodeComponent()" 
          :config="node.config" 
          :node-id="node.id"
          @update-config="updateConfig"
        />
      </div>
      
      <!-- Node inputs -->
      <div v-if="node.inputs && node.inputs.length > 0" class="node-inputs">
        <div 
          v-for="(input, idx) in node.inputs" 
          :key="`input-${idx}`"
          class="node-port node-input-port"
          @mouseup.stop="$emit('connect-end', node.id, idx)"
        >
          <div class="node-port-point"></div>
          <div class="node-port-label">{{ input.name }}</div>
        </div>
      </div>
      
      <!-- Node outputs -->
      <div v-if="node.outputs && node.outputs.length > 0" class="node-outputs">
        <div 
          v-for="(output, idx) in node.outputs" 
          :key="`output-${idx}`"
          class="node-port node-output-port"
          @mousedown.stop="$emit('connect-start', node.id, idx)"
        >
          <div class="node-port-label">{{ output.name }}</div>
          <div class="node-port-point"></div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed, ref } from 'vue';
  import WebhookNode from '../Nodes/WebhookNode.vue';
  import HttpNode from '../Nodes/HttpNode.vue';
  import AgentNode from '../Nodes/AgentNode.vue';
  import ConditionNode from '../Nodes/ConditionNode.vue';
  import TransformNode from '../Nodes/TransformNode.vue';
  import SchedulerNode from '../Nodes/SchedulerNode.vue';
  import ReturnNode from '../Nodes/ReturnNode.vue';
  
  const props = defineProps({
    node: {
      type: Object,
      required: true
    },
    selected: {
      type: Boolean,
      default: false
    }
  });
  
  const emit = defineEmits(['select', 'delete', 'move', 'connect-start', 'connect-end']);
  
  // Dragging state
  const isDragging = ref(false);
  const dragOffset = ref({ x: 0, y: 0 });
  
  // Node position style
  const nodeStyle = computed(() => ({
    left: `${props.node.position.x}px`,
    top: `${props.node.position.y}px`
  }));
  
  // Start dragging the node
  const startDrag = (event) => {
    // Don't start drag if clicking on port or button
    if (event.target.closest('.node-port') || event.target.closest('button')) {
      return;
    }
    
    isDragging.value = true;
    dragOffset.value = {
      x: event.clientX - props.node.position.x,
      y: event.clientY - props.node.position.y
    };
    
    // Add event listeners
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
    
    // Select the node
    emit('select', props.node);
  };
  
  // Handle node dragging
  const onDrag = (event) => {
    if (!isDragging.value) return;
    
    const newPosition = {
      x: event.clientX - dragOffset.value.x,
      y: event.clientY - dragOffset.value.y
    };
  

  // Handle node dragging
  const onDrag = (event) => {
    if (!isDragging.value) return;
    
    const newPosition = {
      x: Math.max(0, event.clientX - dragOffset.value.x),
      y: Math.max(0, event.clientY - dragOffset.value.y)
    };
    
    emit('move', props.node.id, newPosition);
  };

  // Stop dragging
  const stopDrag = () => {
    isDragging.value = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
  };

  // Get node component based on type
  const getNodeComponent = () => {
    switch (props.node.type) {
      case 'webhook': return WebhookNode;
      case 'http': return HttpNode;
      case 'agent': return AgentNode;
      case 'condition': return ConditionNode;
      case 'transform': return TransformNode;
      case 'scheduler': return SchedulerNode;
      case 'return': return ReturnNode;
      default: return null;
    }
  };

  // Update node configuration
  const updateConfig = (newConfig) => {
    emit('update-config', props.node.id, newConfig);
  };

  // Get node color based on type
  const getNodeColor = () => {
    switch (props.node.type) {
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

  // Get icon component for node type
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
</script>

<style scoped>
.node-card {
  position: absolute;
  width: 300px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  cursor: move;
  border: 2px solid transparent;
  overflow: visible;
}

.node-selected {
  border-color: #6366F1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.node-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  border-top-left-radius: 6px;
  border-top-right-radius: 6px;
}

.node-content {
  padding: 12px;
  background-color: white;
}

.node-inputs {
  position: absolute;
  top: 40px;
  left: -12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.node-outputs {
  position: absolute;
  top: 40px;
  right: -12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.node-port {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.node-input-port {
  flex-direction: row;
}

.node-output-port {
  flex-direction: row-reverse;
}

.node-port-point {
  width: 12px;
  height: 12px;
  background-color: #6366F1;
  border-radius: 50%;
  border: 2px solid white;
  margin: 0 4px;
}

.node-port-label {
  font-size: 12px;
  color: #4B5563;
  background-color: white;
  padding: 2px 6px;
  border-radius: 4px;
}
</style>
