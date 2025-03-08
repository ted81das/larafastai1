<template>
    <div class="node-list">
      <h3 class="font-semibold text-lg mb-4">Available Nodes</h3>
      
      <div class="mb-4">
        <input
          type="text"
          placeholder="Search nodes..."
          v-model="searchQuery"
          class="w-full px-3 py-2 border rounded-md text-sm"
        />
      </div>
      
      <div class="categories space-y-4">
        <div v-for="(category, index) in categories" :key="index" class="category">
          <h4 
            @click="toggleCategory(category.name)"
            class="font-medium text-sm uppercase tracking-wider mb-2 cursor-pointer flex items-center"
          >
            <svg 
              :class="{'transform rotate-90': expandedCategories[category.name]}"
              class="w-4 h-4 mr-1 transition-transform" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            {{ category.name }}
          </h4>
          
          <div 
            v-if="expandedCategories[category.name]"
            class="grid gap-2 transition-all duration-300"
          >
            <div 
              v-for="node in filteredNodes.filter(n => n.category === category.name)" 
              :key="node.type"
              draggable="true"
              @dragstart="onDragStart($event, node)"
              class="node-item bg-white p-3 rounded border shadow-sm cursor-move hover:shadow-md transition-shadow"
            >
              <div class="flex items-center">
                <div 
                  class="node-icon w-8 h-8 rounded-md flex items-center justify-center mr-3"
                  :style="{ backgroundColor: node.color + '20', color: node.color }"
                >
                  <i :class="node.icon"></i>
                </div>
                <div>
                  <div class="font-medium text-sm">{{ node.label }}</div>
                  <div class="text-xs text-gray-500">{{ node.description }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="mt-6 pt-4 border-t">
        <a href="#" class="text-blue-600 text-sm hover:text-blue-800" @click.prevent="showDocs">
          View Documentation
        </a>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, computed, onMounted } from 'vue';
  
  export default {
    emits: ['add-node'],
    
    setup(_, { emit }) {
      const searchQuery = ref('');
      const expandedCategories = ref({});
      
      // Node definitions
      const nodes = ref([
        // Trigger nodes
        {
          type: 'webhook',
          label: 'Webhook',
          description: 'Trigger workflow via HTTP webhook',
          category: 'Triggers',
          color: '#4f46e5',
          icon: 'fas fa-bolt',
          defaultConfig: {
            path: '',
            method: 'POST',
            requireAuth: true
          }
        },
        {
          type: 'scheduler',
          label: 'Scheduler',
          description: 'Schedule workflow execution',
          category: 'Triggers',
          color: '#4f46e5',
          icon: 'fas fa-clock',
          defaultConfig: {
            frequency: 'daily',
            time: '00:00',
            cronExpression: ''
          }
        },
        
        // Action nodes
        {
          type: 'http',
          label: 'HTTP Request',
          description: 'Make HTTP requests to external APIs',
          category: 'Actions',
          color: '#06b6d4',
          icon: 'fas fa-globe',
          defaultConfig: {
            url: '',
            method: 'GET',
            headers: {},
            body: '',
            timeout: 30
          }
        },
        {
          type: 'agent',
          label: 'AI Agent',
          description: 'Use AI agent to process data',
          category: 'Actions',
          color: '#06b6d4',
          icon: 'fas fa-robot',
          defaultConfig: {
            agentId: null,
            input: '',
            systemPrompt: '',
            temperature: 0.7
          }
        },
        {
          type: 'transform',
          label: 'Transform',
          description: 'Transform data using JavaScript',
          category: 'Actions',
          color: '#06b6d4',
          icon: 'fas fa-code',
          defaultConfig: {
            code: 'function transform(data) {\n  // Transform data here\n  return data;\n}'
          }
        },
        
        // Flow control
        {
          type: 'condition',
          label: 'Condition',
          description: 'Branch workflow based on conditions',
          category: 'Flow',
          color: '#f97316',
          icon: 'fas fa-code-branch',
          defaultConfig: {
            conditions: [
              { expression: '', result: null }
            ],
            defaultResult: null
          }
        },
        {
          type: 'return',
          label: 'Return',
          description: 'End workflow and return data',
          category: 'Flow',
          color: '#f97316',
          icon: 'fas fa-flag-checkered',
          defaultConfig: {
            data: ''
          }
        }
      ]);
      
      // Define categories
      const categories = ref([
        { name: 'Triggers', order: 1 },
        { name: 'Actions', order: 2 },
        { name: 'Flow', order: 3 }
      ]);
      
      // Automatically expand all categories by default
      onMounted(() => {
        categories.value.forEach(category => {
          expandedCategories.value[category.name] = true;
        });
      });
      
      // Filter nodes based on search query
      const filteredNodes = computed(() => {
        if (!searchQuery.value) return nodes.value;
        
        const query = searchQuery.value.toLowerCase();
        return nodes.value.filter(node => 
          node.label.toLowerCase().includes(query) || 
          node.description.toLowerCase().includes(query)
        );
      });
      
      // Toggle category expansion
      const toggleCategory = (categoryName) => {
        expandedCategories.value[categoryName] = !expandedCategories.value[categoryName];
      };
      
      // Handle drag start
      const onDragStart = (event, node) => {
        // Set drag data
        event.dataTransfer.setData('application/json', JSON.stringify({
          type: node.type,
          label: node.label,
          color: node.color,
          config: node.defaultConfig
        }));
        
        // Set drag effect
        event.dataTransfer.effectAllowed = 'copy';
      };
      
      // Show documentation
      const showDocs = () => {
        window.open('/docs/workflow-nodes', '_blank');
      };
      
      return {
        searchQuery,
        nodes,
        categories,
        expandedCategories,
        filteredNodes,
        toggleCategory,
        onDragStart,
        showDocs
      };
    }
  };
  </script>
  