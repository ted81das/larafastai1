<?php

namespace App\Services\LLM;

use App\Models\User;
use EchoLabs\Prism\Facades\PrismServer;
use Illuminate\Support\Facades\Log;

class PrismAdapter
{
    /**
     * The user to fetch API keys for
     */
    protected ?User $user = null;
    
    /**
     * Provider mapping for models to determine which provider API key to use
     * This can be moved to config if needed
     */
    protected array $modelProviderMap = [
        'gpt-4' => 'openai',
        'gpt-3.5-turbo' => 'openai',
        'text-embedding-ada-002' => 'openai',
        'claude-3-opus' => 'anthropic',
        'claude-3-sonnet' => 'anthropic',
        'claude-3-haiku' => 'anthropic',
        'deepseek-coder' => 'deepseek',
        // Add more model-to-provider mappings as needed
    ];

    /**
     * Set the user context for API key retrieval
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get the provider name for a given model
     */
    protected function getProviderForModel(string $model): string
    {
        return $this->modelProviderMap[$model] ?? 'default';
    }

    /**
     * Configure the Prism client with user-specific API key if available
     */
    protected function configurePrismForModel(string $model): void
    {
        if (!$this->user) {
            return; // Use default configuration if no user is set
        }
        
        $provider = $this->getProviderForModel($model);
        $apiKey = $this->user->getProviderApiKey($provider, $model);
        
        if ($apiKey) {
            // Configure Prism to use this API key
            PrismServer::withToken($apiKey);
        }
    }

    /**
     * Send a chat completion request to Prism
     */
    public function chatCompletion(array $messages, string $model, float $temperature = 0.7, array $tools = [], bool $stream = false)
    {
        try {
            // Configure Prism with user's API key if available
            $this->configurePrismForModel($model);
            
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
            // Configure Prism with user's API key if available
            $this->configurePrismForModel($model);
            
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
            // For model checks, we can use the default API key as this is just a validation
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



/*



namespace App\Services\LLM;

use EchoLabs\Prism\Facades\PrismServer;
use Illuminate\Support\Facades\Log;

class PrismAdapter
{
    /**
     * Send a chat completion request to Prism
     
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
*/