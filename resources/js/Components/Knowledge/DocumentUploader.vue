<template>
    <div class="document-uploader">
      <div 
        :class="{ 'border-blue-400 bg-blue-50': isDragging }"
        class="border-2 border-dashed rounded-lg p-6 transition-colors duration-200"
        @dragenter.prevent="isDragging = true"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleFileDrop"
      >
        <div class="text-center">
          <svg 
            class="mx-auto h-12 w-12 text-gray-400" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="currentColor" 
            aria-hidden="true"
          >
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="1" 
              d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" 
            />
          </svg>
          
          <div class="mt-2">
            <p class="text-sm text-gray-500">
              Drag and drop files here, or
              <label 
                for="file-upload" 
                class="relative cursor-pointer text-blue-600 hover:text-blue-700"
              >
                <span>browse for files</span>
                <input 
                  id="file-upload" 
                  type="file" 
                  multiple 
                  class="sr-only" 
                  @change="handleFileSelect"
                  :accept="acceptedFileTypes"
                />
              </label>
            </p>
            <p class="text-xs text-gray-400 mt-1">
              Accepted file types: PDF, DOCX, TXT, MD, CSV
            </p>
            <p class="text-xs text-gray-400">
              Maximum file size: 10MB
            </p>
          </div>
        </div>
      </div>
      
      <div v-if="files.length > 0" class="mt-4">
        <h3 class="text-sm font-medium text-gray-700 mb-2">Selected Files</h3>
        
        <div class="space-y-2">
          <div 
            v-for="(file, index) in files" 
            :key="index"
            class="flex items-center justify-between p-3 border rounded-md"
          >
            <div class="flex items-center">
              <div class="shrink-0 mr-3">
                <svg 
                  class="h-6 w-6 text-gray-400" 
                  fill="none" 
                  viewBox="0 0 24 24" 
                  stroke="currentColor"
                >
                  <path 
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    stroke-width="2" 
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" 
                  />
                </svg>
              </div>
              
              <div>
                <div class="text-sm font-medium text-gray-900 truncate max-w-xs">
                  {{ file.name }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ formatFileSize(file.size) }}
                </div>
              </div>
            </div>
            
            <div class="flex items-center gap-2">
              <div v-if="fileStates[index].status === 'uploading'">
                <div class="w-20">
                  <div class="bg-gray-200 rounded-full h-2.5">
                    <div 
                      class="bg-blue-600 h-2.5 rounded-full" 
                      :style="{ width: fileStates[index].progress + '%' }"
                    ></div>
                  </div>
                  <div class="text-xs text-center mt-1 text-gray-500">
                    {{ fileStates[index].progress }}%
                  </div>
                </div>
              </div>
              
              <div v-else-if="fileStates[index].status === 'error'" class="text-red-500 text-xs">
                {{ fileStates[index].error }}
              </div>
              
              <div v-else-if="fileStates[index].status === 'success'" class="text-green-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              
              <button 
                type="button" 
                @click="removeFile(index)"
                class="ml-1 text-gray-400 hover:text-gray-500"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>
        
        <div class="mt-4 flex justify-end space-x-3">
          <button 
            type="button" 
            @click="clearFiles"
            class="px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            :disabled="isUploading"
          >
            Clear All
          </button>
          <button 
            type="button" 
            @click="uploadFiles"
            class="px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            :disabled="files.length === 0 || isUploading"
          >
            <span v-if="isUploading">Uploading...</span>
            <span v-else>Upload {{ files.length }} {{ files.length === 1 ? 'File' : 'Files' }}</span>
          </button>
        </div>
      </div>
      
      <div v-if="options.showConfigOptions" class="mt-4">
        <h3 class="text-sm font-medium text-gray-700 mb-2">Processing Options</h3>
        
        <div class="space-y-3 p-3 border rounded-md">
          <div>
            <label class="flex items-center">
              <input 
                type="checkbox" 
                v-model="processingOptions.extractMetadata" 
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-700">Extract metadata</span>
            </label>
          </div>
          
          <div>
            <label class="flex items-center">
              <input 
                type="checkbox" 
                v-model="processingOptions.splitIntoChunks" 
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-700">Split documents into chunks</span>
            </label>
            
            <div v-if="processingOptions.splitIntoChunks" class="mt-2 pl-6">
              <label class="block text-xs text-gray-700">Chunk size (characters)</label>
              <input 
                type="number" 
                v-model.number="processingOptions.chunkSize" 
                min="100" 
                max="4000"
                class="mt-1 block w-24 py-1 px-2 border border-gray-300 bg-white rounded-md text-sm"
              />
            </div>
          </div>
          
          <div>
            <label class="flex items-center">
              <input 
                type="checkbox" 
                v-model="processingOptions.generateEmbeddings" 
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-700">Generate embeddings</span>
            </label>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, computed, watch } from 'vue';
  import { useToast } from '@/Composables/useToast';
  
  export default {
    props: {
      url: {
        type: String,
        default: '/api/knowledge/documents/upload'
      },
      options: {
        type: Object,
        default: () => ({
          showConfigOptions: true,
          maxFileSize: 10 * 1024 * 1024, // 10MB
          acceptedFileTypes: ['.pdf', '.docx', '.txt', '.md', '.csv']
        })
      },
      collectionId: {
        type: [Number, String],
        default: null
      }
    },
    
    emits: ['upload-success', 'upload-error', 'files-cleared'],
    
    setup(props, { emit }) {
      const { showToast } = useToast();
      const files = ref([]);
      const isDragging = ref(false);
      const isUploading = ref(false);
      
      // Track the status of each file
      const fileStates = ref([]);
      
      // Document processing options
      const processingOptions = reactive({
        extractMetadata: true,
        splitIntoChunks: true,
        chunkSize: 1000,
        generateEmbeddings: true
      });
      
      // Computed string of accepted file types for the file input
      const acceptedFileTypes = computed(() => {
        return props.options.acceptedFileTypes.join(',');
      });
      
      // Handle file select from input
      const handleFileSelect = (event) => {
        addFiles(Array.from(event.target.files));
        event.target.value = null; // Clear the input
      };
      
      // Handle file drop
      const handleFileDrop = (event) => {
        isDragging.value = false;
        addFiles(Array.from(event.dataTransfer.files));
      };
      
      // Add files to the list
      const addFiles = (newFiles) => {
        const validFiles = newFiles.filter(file => {
          // Check file size
          if (file.size > props.options.maxFileSize) {
            showToast(`File "${file.name}" exceeds the maximum file size of ${formatFileSize(props.options.maxFileSize)}`, 'error');
            return false;
          }
          
          // Check file type
          const extension = '.' + file.name.split('.').pop().toLowerCase();
          if (!props.options.acceptedFileTypes.includes(extension)) {
            showToast(`File "${file.name}" has an unsupported file type`, 'error');
            return false;
          }
          
          return true;
        });
        
        // Add valid files to the list
        validFiles.forEach(file => {
          files.value.push(file);
          fileStates.value.push({
            status: 'pending',
            progress: 0,
            error: null
          });
        });
      };
      
      // Remove file from the list
      const removeFile = (index) => {
        files.value.splice(index, 1);
        fileStates.value.splice(index, 1);
      };
      
      // Clear all files
      const clearFiles = () => {
        files.value = [];
        fileStates.value = [];
        emit('files-cleared');
      };
      
      // Format file size
      const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
      };
      
      // Upload files
      const uploadFiles = async () => {
        if (files.value.length === 0 || isUploading.value) return;
        
        isUploading.value = true;
        let uploadedCount = 0;
        let errorCount = 0;
        
        for (let i = 0; i < files.value.length; i++) {
          if (fileStates.value[i].status === 'success') {
            uploadedCount++;
            continue; // Skip already uploaded files
          }
          
          // Create form data
          const formData = new FormData();
          formData.append('file', files.value[i]);
          formData.append('options', JSON.stringify(processingOptions));
          
          if (props.collectionId) {
            formData.append('collection_id', props.collectionId);
          }
          
          // Update file status
          fileStates.value[i].status = 'uploading';
          fileStates.value[i].progress = 0;
          
          try {
            // Upload file with progress tracking
            const xhr = new XMLHttpRequest();
            
            xhr.open('POST', props.url, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            // Track upload progress
            xhr.upload.onprogress = (event) => {
              if (event.lengthComputable) {
                const progress = Math.round((event.loaded / event.total) * 100);
                fileStates.value[i].progress = progress;
              }
            };
            
            // Create a promise to handle the XHR response
            await new Promise((resolve, reject) => {
              xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                  fileStates.value[i].status

                  <script>
import { ref, reactive, computed, watch } from 'vue';
import { useToast } from '@/Composables/useToast';

export default {
  props: {
    url: {
      type: String,
      default: '/api/knowledge/documents/upload'
    },
    options: {
      type: Object,
      default: () => ({
        showConfigOptions: true,
        maxFileSize: 10 * 1024 * 1024, // 10MB
        acceptedFileTypes: ['.pdf', '.docx', '.txt', '.md', '.csv']
      })
    },
    collectionId: {
      type: [Number, String],
      default: null
    }
  },
  
  emits: ['upload-success', 'upload-error', 'files-cleared'],
  
  setup(props, { emit }) {
    const { showToast } = useToast();
    const files = ref([]);
    const isDragging = ref(false);
    const isUploading = ref(false);
    
    // Track the status of each file
    const fileStates = ref([]);
    
    // Document processing options
    const processingOptions = reactive({
      extractMetadata: true,
      splitIntoChunks: true,
      chunkSize: 1000,
      generateEmbeddings: true
    });
    
    // Computed string of accepted file types for the file input
    const acceptedFileTypes = computed(() => {
      return props.options.acceptedFileTypes.join(',');
    });
    
    // Handle file select from input
    const handleFileSelect = (event) => {
      addFiles(Array.from(event.target.files));
      event.target.value = null; // Clear the input
    };
    
    // Handle file drop
    const handleFileDrop = (event) => {
      isDragging.value = false;
      addFiles(Array.from(event.dataTransfer.files));
    };
    
    // Add files to the list
    const addFiles = (newFiles) => {
      const validFiles = newFiles.filter(file => {
        // Check file size
        if (file.size > props.options.maxFileSize) {
          showToast(`File "${file.name}" exceeds the maximum file size of ${formatFileSize(props.options.maxFileSize)}`, 'error');
          return false;
        }
        
        // Check file type
        const extension = '.' + file.name.split('.').pop().toLowerCase();
        if (!props.options.acceptedFileTypes.includes(extension)) {
          showToast(`File "${file.name}" has an unsupported file type`, 'error');
          return false;
        }
        
        return true;
      });
      
      // Add valid files to the list
      validFiles.forEach(file => {
        files.value.push(file);
        fileStates.value.push({
          status: 'pending',
          progress: 0,
          error: null
        });
      });
    };
    
    // Remove file from the list
    const removeFile = (index) => {
      files.value.splice(index, 1);
      fileStates.value.splice(index, 1);
    };
    
    // Clear all files
    const clearFiles = () => {
      files.value = [];
      fileStates.value = [];
      emit('files-cleared');
    };
    
    // Format file size
    const formatFileSize = (bytes) => {
      if (bytes === 0) return '0 Bytes';
      
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };
    
    // Upload files
    const uploadFiles = async () => {
      if (files.value.length === 0 || isUploading.value) return;
      
      isUploading.value = true;
      let uploadedCount = 0;
      let errorCount = 0;
      
      for (let i = 0; i < files.value.length; i++) {
        if (fileStates.value[i].status === 'success') {
          uploadedCount++;
          continue; // Skip already uploaded files
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('file', files.value[i]);
        formData.append('options', JSON.stringify(processingOptions));
        
        if (props.collectionId) {
          formData.append('collection_id', props.collectionId);
        }
        
        // Update file status
        fileStates.value[i].status = 'uploading';
        fileStates.value[i].progress = 0;
        
        try {
          // Upload file with progress tracking
          const xhr = new XMLHttpRequest();
          
          xhr.open('POST', props.url, true);
          xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
          
          // Track upload progress
          xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
              const progress = Math.round((event.loaded / event.total) * 100);
              fileStates.value[i].progress = progress;
            }
          };
          
          // Create a promise to handle the XHR response
          await new Promise((resolve, reject) => {
            xhr.onload = () => {
              if (xhr.status >= 200 && xhr.status < 300) {
                fileStates.value[i].status = 'success';
                uploadedCount++;
                resolve(JSON.parse(xhr.responseText));
              } else {
                let errorMessage = 'Upload failed';
                try {
                  const response = JSON.parse(xhr.responseText);
                  errorMessage = response.message || errorMessage;
                } catch (e) {
                  // If parsing fails, use the default error message
                }
                
                fileStates.value[i].status = 'error';
                fileStates.value[i].error = errorMessage;
                errorCount++;
                reject(new Error(errorMessage));
              }
            };
            
            xhr.onerror = () => {
              fileStates.value[i].status = 'error';
              fileStates.value[i].error = 'Network error';
              errorCount++;
              reject(new Error('Network error'));
            };
            
            xhr.send(formData);
          });
        } catch (error) {
          console.error(`Error uploading file ${files.value[i].name}:`, error);
          // Error is already handled in the promise
        }
      }
      
      isUploading.value = false;
      
      if (uploadedCount > 0) {
        showToast(`Successfully uploaded ${uploadedCount} ${uploadedCount === 1 ? 'file' : 'files'}`, 'success');
        emit('upload-success', uploadedCount);
      }
      
      if (errorCount > 0) {
        showToast(`Failed to upload ${errorCount} ${errorCount === 1 ? 'file' : 'files'}`, 'error');
        emit('upload-error', errorCount);
      }
    };
    
    return {
      files,
      isDragging,
      isUploading,
      fileStates,
      processingOptions,
      acceptedFileTypes,
      handleFileSelect,
      handleFileDrop,
      removeFile,
      clearFiles,
      uploadFiles,
      formatFileSize
    };
  }
};
</script>

<style scoped>
.document-uploader {
  font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

input[type="checkbox"] {
  cursor: pointer;
}

label {
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
</style>

  