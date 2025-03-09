<?php

namespace App\Services\LLM;

use App\Models\CustomOpenAIEndpoint;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomEndpointService
{
    /**
     * Get all available models for a user from their custom endpoints
     */
    public function getAvailableModelsForUser(User $user): array
    {
        $endpoints = CustomOpenAIEndpoint::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
            
        $models = [];
        
        foreach ($endpoints as $endpoint) {
            $availableModels = $endpoint->fetchAvailableModels();
            
            foreach ($availableModels as $model) {
                $modelId = $model['id'] ?? null;
                if (!$modelId) continue;
                
                $modelName = $model['id'];
                if (isset($model['name']) && !empty($model['name'])) {
                    $modelName = $model['name'];
                }
                
                // Use format: endpoint_id:model_id
                $key = "{$endpoint->id}:{$modelId}";
                
                $models[$key] = [
                    'name' => "{$endpoint->name} - {$modelName}",
                    'context_window' => $endpoint->context_window,
                    'endpoint' => $endpoint,
                    'model_details' => $model,
                ];
            }
        }
        
        return $models;
    }
    
    /**
     * Register custom endpoint models in the LarAgent configuration
     */
    public function registerCustomModelsInConfig(User $user): void
    {
        $models = $this->getAvailableModelsForUser($user);
        
        // This would need to be implemented based on how LarAgent loads configurations
        // This is conceptual and would need to be adapted to your application
        config(['laragent.providers.llphant_openai.models' => $models]);
    }
    
    /**
     * Test a custom endpoint connection
     */
    public function testEndpointConnection(CustomOpenAIEndpoint $endpoint): array
    {
        try {
            $models = $endpoint->fetchAvailableModels();
            
            return [
                'success' => true,
                'message' => 'Successfully connected to endpoint',
                'models_found' => count($models),
                'models' => $models,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect: ' . $e->getMessage(),
            ];
        }
    }
}
