<template>
    <div class="bg-white h-full flex flex-col">
      <div class="p-4 border-b border-gray-200">
        <h2 class="font-medium text-lg text-gray-700">Node Configuration</h2>
        <p class="text-sm text-gray-500">{{ node.label }} settings</p>
      </div>
      
      <div class="flex-1 overflow-y-auto p-4">
        <!-- Common settings -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Node Name</label>
          <input 
            type="text" 
            v-model="configData.name" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            @change="updateConfig"
          />
        </div>
        
        <!-- Node-specific settings -->
        <div v-if="node.type === 'webhook'" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Path</label>
            <input 
              type="text" 
              v-model="configData.path" 
              placeholder="/webhooks/my-endpoint"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              @change="updateConfig"
            />
            <p class="mt-1 text-xs text-gray-500">Webhook URL: {{ webhookUrl }}</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
            <select 
              v-model="configData.method" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              @change="updateConfig"
            >
              <option value="POST">POST</option>
              <option value="GET">GET</option>
              <option value="PUT">PUT</option>
              <option value="DELETE">DELETE</option>
            </select>
          </div>
        </div>
        
        <div v-else-if="node.type === 'http'" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
            <input 
              type="text" 
              v-model="configData.url" 
              placeholder="https://api.example.com/endpoint"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              @change="updateConfig"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
            <select 
              v-model="configData.method" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              @change="updateConfig"
            >
              <option value="GET">GET</option>
              <option value="POST">POST</option>
              <option value="PUT">PUT</option>
              <option value="DELETE">DELETE</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Headers</label>
            <div v-for="(value, key, index) in configData.headers" :key="index" class="flex gap-2 mb-2">
              <input 
                type="text" 
                v-model="headerKeys[index]" 
                placeholder="Key"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                @change="updateHeaders"
              />
              <input 
                type="text" 
                v-model="headerValues[index]" 
                placeholder="Value"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                @change="updateHeaders"
              />
              <button @click="removeHeader(key)" class="p-2 text-gray-400 hover:text-red-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            <button 
              @click="addHeader" 
              class="mt-1 inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Add Header
            </button>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Body</label>
            <textarea 
              v-model="configData.body" 
              rows="4"
              placeholder="Request body (JSON, text, etc.)"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              @change="updateConfig"
            ></textarea>
          </div>
        </div>
        
        <div v-else-if="node.type === 'agent'" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Agent</label>
            <select 
              v-model="configData.agent_id" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              @change="updateConfig"
            >
              <option value="">Select an agent</option>
              <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                {{ agent.name }}
              </option>
            </select>
          </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Input</label>
          <textarea 
            v-model="configData.input" 
            rows="4"
            placeholder="Input for the agent or use variable: {{context.variable}}"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            @change="updateConfig"
          ></textarea>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Input from Context</label>
          <input 
            type="text" 
            v-model="configData.input_from_context" 
            placeholder="context.variable_name"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            @change="updateConfig"
          />
        </div>
      </div>
      
      <div v-else-if="node.type === 'condition'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
          <textarea 
            v-model="configData.condition" 
            rows="4"
            placeholder="context.status === 'success' || input.value > 10"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            @change="updateConfig"
          ></textarea>
          <p class="mt-1 text-xs text-gray-500">JavaScript expression that evaluates to true/false</p>
        </div>
        
        <div class="flex space-x-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">True Branch</label>
            <div class="text-sm bg-green-50 border border-green-200 p-2 rounded-md">
              Connected to: {{ getTrueNodeName() }}
            </div>
          </div>
          
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">False Branch</label>
            <div class="text-sm bg-red-50 border border-red-200 p-2 rounded-md">
              Connected to: {{ getFalseNodeName() }}
            </div>
          </div>
        </div>
      </div>
      
      <div v-else-if="node.type === 'transform'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Transform Script</label>
          <textarea 
            v-model="configData.script" 
            rows="8"
            placeholder="// Transform your data with JavaScript
// Input is available as 'input'
// Return the transformed data
return {
  ...input,
  processed: true,
  timestamp: new Date().toISOString()
};"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-mono"
            @change="updateConfig"
          ></textarea>
        </div>
      </div>
      
      <div v-else-if="node.type === 'scheduler'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Schedule (Cron Expression)</label>
          <input 
            type="text" 
            v-model="configData.schedule" 
            placeholder="0 0 * * *"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            @change="updateConfig"
          />
          <p class="mt-1 text-xs text-gray-500">Format: minute hour day-of-month month day-of-week</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
          <select 
            v-model="configData.timezone" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            @change="updateConfig"
          >
            <option value="UTC">UTC</option>
            <option value="America/New_York">America/New_York</option>
            <option value="Europe/London">Europe/London</option>
            <option value="Asia/Tokyo">Asia/Tokyo</option>
            <!-- Add more timezones as needed -->
          </select>
        </div>
      </div>
      
      <div v-else-if="node.type === 'return'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Output Variable</label>
          <input 
            type="text" 
            v-model="configData.output_variable" 
            placeholder="result"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            @change="updateConfig"
          />
          <p class="mt-1 text-xs text-gray-500">Name of the variable to store in workflow result</p>
        </div>
      </div>
    </div>
    
    <div class="p-4 border-t border-gray-200">
      <button 
        @click="testNode" 
        class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        Test Node
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
  node: {
    type: Object,
    required: true
  },
  availableNodes: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update']);

// Mock data for agents - replace with actual data from your backend
const agents = ref([
  { id: 1, name: 'General Assistant' },
  { id: 2, name: 'Customer Support Agent' },
  { id: 3, name: 'Data Processor' },
]);

// Local copy of node config
const configData = ref({ ...props.node.config, name: props.node.label });

// Header management for HTTP node
const headerKeys = ref([]);
const headerValues = ref([]);

// Initialize headers
onMounted(() => {
  if (props.node.type === 'http' && props.node.config.headers) {
    Object.entries(props.node.config.headers).forEach(([key, value], index) => {
      headerKeys.value[index] = key;
      headerValues.value[index] = value;
    });
  }
});

// Update config when node changes
watch(() => props.node, (newNode) => {
  configData.value = { ...newNode.config, name: newNode.label };
  
  // Reset headers for HTTP nodes
  if (newNode.type === 'http' && newNode.config.headers) {
    headerKeys.value = [];
    headerValues.value = [];
    Object.entries(newNode.config.headers).forEach(([key, value], index) => {
      headerKeys.value[index] = key;
      headerValues.value[index] = value;
    });
  }
}, { deep: true });

// Computed webhook URL
const webhookUrl = computed(() => {
  if (props.node.type !== 'webhook') return '';
  return `${window.location.origin}/api/workflows/webhooks${configData.value.path || '/[path]'}`;
});

// Get connected node names
const getTrueNodeName = () => {
  if (!configData.value.true_node_id) return 'Not connected';
  const node = props.availableNodes.find(n => n.id === configData.value.true_node_id);
  return node ? node.label : 'Unknown';
};

const getFalseNodeName = () => {
  if (!configData.value.false_node_id) return 'Not connected';
  const node = props.availableNodes.find(n => n.id === configData.value.false_node_id);
  return node ? node.label : 'Unknown';
};

// Update config
const updateConfig = () => {
  const config = { ...configData.value };
  delete config.name; // Remove name as it's stored separately
  
  // Emit update with new config and name
  emit('update', props.node.id, config, configData.value.name);
};

// HTTP node header management
const updateHeaders = () => {
  const headers = {};
  headerKeys.value.forEach((key, index) => {
    if (key && key.trim()) {
      headers[key] = headerValues.value[index] || '';
    }
  });
  
  configData.value.headers = headers;
  updateConfig();
};

const addHeader = () => {
  headerKeys.value.push('');
  headerValues.value.push('');
};

const removeHeader = (key) => {
  const index = headerKeys.value.indexOf(key);
  if (index !== -1) {
    headerKeys.value.splice(index, 1);
    headerValues.value.splice(index, 1);
    updateHeaders();
  }
};

// Test the node
const testNode = async () => {
  try {
    // This would typically make an API call to test the node
    alert('Node testing functionality would be implemented in the backend');
  } catch (error) {
    console.error('Error testing node:', error);
  }
};
</script>
