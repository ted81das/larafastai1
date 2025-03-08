<?php 
namespace App\Services\Agent;

class ModelRegistry
{
    /**
     * Get all available models
     */
    public function getAvailableModels(): array
    {
        return array_merge(
            $this->getOpenAIModels(),
            $this->getPrismModels()
        );
    }
    
    /**
     * Get OpenAI models
     */
    public function getOpenAIModels(): array
    {
        return [
            'gpt-4o' => [
                'name' => 'GPT-4o',
                'provider' => 'openai',
                'description' => 'Most capable multimodal model for text and vision tasks',
                'context_window' => 128000,
                'supports_tools' => true,
                'supports_vision' => true,
            ],
            'gpt-4-turbo' => [
                'name' => 'GPT-4 Turbo',
                'provider' => 'openai',
                'description' => 'GPT-4 Turbo with improved capabilities',
                'context_window' => 128000,
                'supports_tools' => true,
                'supports_vision' => false,
            ],
            'gpt-3.5-turbo' => [
                'name' => 'GPT-3.5 Turbo',
                'provider' => 'openai',
                'description' => 'Fast and cost-effective model for most tasks',
                'context_window' => 16385,
                'supports_tools' => true,
                'supports_vision' => false,
            ],
        ];
    }
    
    /**
     * Get Prism models (self-hosted)
     */
    public function getPrismModels(): array
    {
        $models = [];
        
        // Get models from config
        $configuredModels = config('prism.models', []);
        
        foreach ($configuredModels as $modelId => $config) {
            $models[$modelId] = [
                'name' => $config['name'] ?? $modelId,
                'provider' => 'prism',
                'description' => $config['description'] ?? 'Self-hosted model via Prism',
                'context_window' => $config['context_window'] ?? 8192,
                'supports_tools' => $config['supports_tools'] ?? false,
                'supports_vision' => $config['supports_vision'] ?? false,
            ];
        }
        
        return $models;
    }
    
    /**
     * Get the provider for a specific model
     */
    public function getProviderForModel(string $modelId): string
    {
        $allModels = $this->getAvailableModels();
        
        if (isset($allModels[$modelId])) {
            return $allModels[$modelId]['provider'];
        }
        
        // Default to OpenAI if model not found
        return 'openai';
    }
    
    /**
     * Get model details by ID
     */
    public function getModelById(string $modelId): ?array
    {
        $allModels = $this->getAvailableModels();
        
        return $allModels[$modelId] ?? null;
    }
}
