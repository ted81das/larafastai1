<template>
    <div class="chat-interface flex flex-col h-full">
      <!-- Chat messages -->
      <div 
        ref="messagesContainer"
        class="flex-1 overflow-y-auto p-4 space-y-4"
        :class="{ 'bg-gray-50': theme === 'light', 'bg-gray-900': theme === 'dark' }"
      >
        <!-- Welcome message -->
        <div v-if="messages.length === 0" class="text-center my-8">
          <div class="text-gray-500">
            <div class="mb-3">
              <svg class="w-12 h-12 mx-auto" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 14C8 14 9.5 16 12 16C14.5 16 16 14 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 9H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15 9H15.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <p class="text-lg font-medium">How can I assist you today?</p>
            <p class="mt-1">Send a message to start a conversation</p>
          </div>
          
          <div v-if="suggestedPrompts.length > 0" class="mt-6 flex flex-wrap justify-center gap-2">
            <button 
              v-for="(prompt, i) in suggestedPrompts" 
              :key="i"
              @click="sendMessage(prompt)"
              class="px-3 py-2 text-sm border rounded-full text-left hover:bg-blue-50 transition-colors"
              :class="{ 
                'bg-white text-gray-700 border-gray-200': theme === 'light',
                'bg-gray-800 text-gray-200 border-gray-700': theme === 'dark'
              }"
            >
              {{ prompt }}
            </button>
          </div>
        </div>
        
        <!-- Chat message bubbles -->
        <div 
          v-for="(message, index) in messages" 
          :key="index"
          class="message-bubble flex"
          :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
        >
          <div 
            class="max-w-3xl rounded-lg px-4 py-2"
            :class="messageClasses(message)"
          >
            <div class="font-medium text-xs mb-1" :class="message.role === 'user' ? 'text-right' : ''">
              {{ message.role === 'user' ? 'You' : agentName }}
            </div>
            <div v-if="message.role === 'assistant' && message.isLoading" class="typing-indicator">
              <span></span>
              <span></span>
              <span></span>
            </div>
            <div v-else v-html="formatMessage(message.content)"></div>
            
            <!-- Message timestamp and actions -->
            <div class="mt-1 flex items-center justify-between text-xs">
              <span class="text-gray-500">{{ formatTime(message.timestamp) }}</span>
              <div class="space-x-2" v-if="message.role === 'assistant' && !message.isLoading">
                <button 
                  @click="copyToClipboard(message.content)" 
                  title="Copy to clipboard"
                  class="text-gray-400 hover:text-gray-600"
                >
                  <i class="fas fa-copy"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Citation section -->
        <div 
          v-if="citations.length > 0"
          class="p-3 rounded-lg mt-4"
          :class="{ 
            'bg-gray-100 border border-gray-200': theme === 'light',
            'bg-gray-800 border border-gray-700': theme === 'dark'
          }"
        >
          <div class="font-medium text-sm mb-2">Sources</div>
          <div class="space-y-2">
            <div 
              v-for="(citation, i) in citations" 
              :key="i"
              class="text-sm flex"
            >
              <span class="mr-2">[{{ i + 1 }}]</span>
              <div>
                <div class="font-medium">{{ citation.title }}</div>
                <div class="text-xs text-gray-500">{{ citation.source }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Input area -->
      <div 
        class="p-4 border-t"
        :class="{ 
          'bg-white border-gray-200': theme === 'light',
          'bg-gray-800 border-gray-700': theme === 'dark'
        }"
      >
        <form @submit.prevent="sendMessage" class="flex">
          <textarea
            ref="inputField"
            v-model="userInput"
            rows="1"
            :disabled="isProcessing"
            placeholder="Type your message..."
            class="flex-1 resize-none px-3 py-2 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 
              'bg-white border-gray-300': theme === 'light',
              'bg-gray-700 border-gray-600 text-white': theme === 'dark'
            }"
            @keydown.enter.exact.prevent="sendMessage"
            @input="autoGrow"
          ></textarea>
          <button 
            type="submit" 
            :disabled="isProcessing || !userInput.trim()"
            class="px-4 rounded-r-lg flex items-center justify-center"
            :class="isProcessing || !userInput.trim() 
              ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
              : 'bg-blue-600 text-white hover:bg-blue-700'"
          >
            <i class="fas fa-paper-plane"></i>
          </button>
        </form>
        
        <div v-if="isProcessing" class="mt-2 text-xs text-center text-gray-500">
          Processing message...
        </div>
      </div>
    </div>
  </template>
  
<script>
  import { ref, reactive, onMounted, nextTick, watch, computed } from 'vue';
  import { useToast } from '@/Composables/useToast';
  import { marked } from 'marked';
  import DOMP
</script>  