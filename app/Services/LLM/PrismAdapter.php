<?php

namespace App\Services\LLM;

use EchoLabs\Prism\Facades\PrismServer;
use Illuminate\Support\Facades\Log;

class PrismAdapter
{
    /**
     * Send a chat completion request to Prism
     */
    public function chatCompletion(array $messages, string $model, float $temperature = 0.7, array $tools = [], bool $stream = false)
    {
        try {
            $params = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ];
            
            // Add tools if provided
            if (!empty($tools)) {
                $params['tools'] = $tools;
                $params['tool_choice'] = 'auto';
            }
            
            // Handle streaming
            if ($stream) {
                return PrismServer::chatCompletions()->create($params + ['stream' => true]);
            }
            
            // Regular request
            return PrismServer::chatCompletions()->create($params);
        } catch (\Exception $e) {
            Log::error('Prism API error', [
                'error' => $e->getMessage(),
                'model' => $model,
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Generate embeddings via Prism
     */
    public function generateEmbeddings(string $text, string $model = 'text-embedding-ada-002'): array
    {
        try {
            $response = PrismServer::embeddings()->create([
                'model' => $model,
                'input' => $text,
            ]);
            
            return $response->data[0]->embedding;
        } catch (\Exception $e) {
            Log::error('Prism embeddings error', [
                'error' => $e->getMessage(),
                'model' => $model,
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Check if a model is available in Prism
     */
    public function isModelAvailable(string $model): bool
    {
        try {
            $models = PrismServer::models()->list();
            
            foreach ($models->data as $availableModel) {
                if ($availableModel->id === $model) {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::warning('Failed to check model availability in Prism', [
                'error' => $e->getMessage(),
                'model' => $model,
            ]);
            
            return false;
        }
    }
}
