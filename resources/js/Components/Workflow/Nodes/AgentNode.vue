<template>
    <div class="agent-node-config">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Agent Configuration
        </label>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-600 mb-1">
            Select Agent
          </label>
          <select 
            v-model="localConfig.agentId" 
            class="w-full px-3 py-2 border rounded-md bg-white"
            @change="updateConfig"
          >
            <option value="" disabled>Select an agent</option>
            <option v-for="agent in agents" :key="agent.id" :value="agent.id">
              {{ agent.name }}
            </option>
          </select>
          <div v-if="!localConfig.agentId" class="mt-1 text-xs text-red-500">
            An agent is required
          </div>
          <div v-else class="mt-1 flex justify-end">
            <button 
              @click="editAgent" 
              class="text-xs text-blue-600 hover:text-blue-800"
            >
              Edit Agent
            </button>
          </div>
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-600 mb-1">
            Input Template
          </label>
          <textarea 
            v-model="localConfig.input" 
            rows="3"
            placeholder="Enter input or use {{variables}} for dynamic values"
            class="w-full px-3 py-2 border rounded-md font-mono text-sm"
            @change="updateConfig"
          ></textarea>
          <div class="mt-1 text-xs text-gray-500">
            Use double curly braces to reference workflow variables
          </div>
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-600 mb-1">
            Override System Prompt (Optional)
          </label>
          <textarea 
            v-model="localConfig.systemPrompt" 
            rows="4"
            placeholder="Leave empty to use the agent's default system prompt"
            class="w-full px-3 py-2 border rounded-md font-mono text-sm"
            @change="updateConfig"
          ></textarea>
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-600 mb-1">
            Temperature
          </label>
          <div class="flex items-center">
            <input 
              type="range" 
              v-model.number="localConfig.temperature" 
              min="0" 
              max="2" 
              step="0.1"
              class="w-full mr-2"
              @change="updateConfig"
            />
            <span class="text-sm w-12 text-right">{{ localConfig.temperature }}</span>
          </div>
          <div class="mt-1 text-xs text-gray-500">
            Lower values produce more focused, deterministic responses
          </div>
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-600 mb-1">
            Use RAG Knowledge
          </label>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              v-model="localConfig.useRag" 
              class="mr-2 h-4 w-4"
              @change="updateConfig"
            />
            <span class="text-sm">Enable retrieval augmented generation</span>
          </div>
        </div>
        
        <div v-if="localConfig.useRag" class="mb-4 ml-6 p-3 border-l-2 border-blue-200">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-600 mb-1">
              Search Query Template
            </label>
            <textarea 
              v-model="localConfig.ragConfig.searchQuery" 
              rows="2"
              placeholder="Enter search query or use {{variables}}"
              class="w-full px-3 py-2 border rounded-md font-mono text-sm"
              @change="updateConfig"
            ></textarea>
          </div>
          
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-600 mb-1">
              Number of Results
            </label>
            <input 
              type="number" 
              v-model.number="localConfig.ragConfig.numResults" 
              min="1" 
              max="20"
              class="w-full px-3 py-2 border rounded-md"
              @change="updateConfig"
            />
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, computed, watch, onMounted } from 'vue';
  import { useToast } from '@/Composables/useToast';
  import { useRouter } from 'vue-router';
  
  export default {
    props: {
      node: {
        type: Object,
        required: true
      },
      config: {
        type: Object,
        required: true
      }
    },
    
    emits: ['update'],
    
    setup(props, { emit }) {
      const { showToast } = useToast();
      const router = useRouter();
      
      // Set default config values
      const defaultConfig = {
        agentId: '',
        input: '',
        systemPrompt: '',
        temperature: 0.7,
        useRag: false,
        ragConfig: {
          searchQuery: '',
          numResults: 5
        }
      };
      
      // Merge with existing config
      const localConfig = reactive({
        ...defaultConfig,
        ...props.config,
        ragConfig: {
          ...defaultConfig.ragConfig,
          ...(props.config.ragConfig || {})
        }
      });
      
      // Available agents
      const agents = ref([]);
      
      // Fetch available agents
      onMounted(async () => {
        try {
          const response = await axios.get('/api/agents');
          agents.value = response.data;
        } catch (error) {
          console.error('Error fetching agents:', error);
          showToast('Failed to load agents', 'error');
        }
      });
      
      // Update config
      const updateConfig = () => {
        emit('update', { ...localConfig });
      };
      
      // Edit agent
      const editAgent = () => {
        if (localConfig.agentId) {
          router.push({ name: 'agents.edit', params: { id: localConfig.agentId } });
        }
      };
      
      // Watch for external config changes
      watch(
        () => props.config,
        (newConfig) => {
          Object.assign(localConfig, {
            ...defaultConfig,
            ...newConfig,
            ragConfig: {
              ...defaultConfig.ragConfig,
              ...(newConfig.ragConfig || {})
            }
          });
        },
        { deep: true }
      );
      
      return {
        localConfig,
        agents,
        updateConfig,
        editAgent
      };
    }
  };
  </script>
  