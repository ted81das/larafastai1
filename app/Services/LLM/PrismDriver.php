<?php

namespace App\Services\LLM;

use LarAgent\Contracts\LLMDriver;
use PrismPHP\Prism\Prism;
use PrismPHP\Prism\Enums\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PrismDriver implements LLMDriver 
{
    protected ?User $user = null;
    protected Provider $provider;
    protected string $model;
    protected array $options = [];

    /**
     * Create a new PrismDriver instance
     * 
     * @param string|Provider $provider The provider (can be string or Provider enum)
     * @param string $model The model name
     */
    public function __construct($provider, string $model)
    {
        // Convert string provider to Provider enum if needed
        if (is_string($provider)) {
            $provider = $this->resolveProviderEnum($provider);
        }
        
        $this->provider = $provider;
        $this->model = $model;
    }

    /**
     * Convert string provider name to Provider enum
     */
    protected function resolveProviderEnum(string $provider): Provider
    {
        $provider = strtolower($provider);
        
        return match($provider) {
            'openai' => Provider::OpenAI,
            'anthropic' => Provider::Anthropic,
            'deepseek' => Provider::DeepSeek,
            'mistral' => Provider::Mistral,
            'google' => Provider::Google,
            // Add more provider mappings as needed
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}")
        };
    }

    /**
     * Set the user context for API key retrieval
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set additional options for the request
     */
    public function withOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Get the provider name as a string (for API key lookup)
     */
    protected function getProviderName(): string
    {
        return strtolower(Str::afterLast((string)$this->provider, '\\'));
    }

    /**
     * Configure Prism with user-specific API key
     */
    protected function configurePrismWithUserKey(): Prism
    {
        $prism = new Prism();
        
        // If we have a user context, try to get their API key
        if ($this->user) {
            $providerName = $this->getProviderName();
            $apiKey = $this->user->getProviderApiKey($providerName, $this->model);
            
            if ($apiKey) {
                // Configure Prism with the provider-specific API key
                $prism->configure($this->provider, [
                    'api_key' => $apiKey
                ]);
            }
        }
        
        return $prism;
    }

    /**
     * Execute a chat completion
     */
    public function chatCompletion(array $messages, array $options = [])
    {
        try {
            $prism = $this->configurePrismWithUserKey();
            $mergedOptions = array_merge($this->options, $options);
            
            // Extract system message if present
            $systemMessage = null;
            foreach ($messages as $key => $message) {
                if ($message['role'] === 'system') {
                    $systemMessage = $message['content'];
                    unset($messages[$key]);
                    break;
                }
            }
            
            // Prepare the chat request using fluent interface
            $chat = $prism->chat()
                ->using($this->provider, $this->model);
            
            // Add system prompt if available
            if ($systemMessage) {
                $chat->withSystemPrompt($systemMessage);
            }
            
            // Add user/assistant messages
            foreach ($messages as $message) {
                if ($message['role'] === 'user') {
                    $chat->withUserMessage($message['content']);
                } elseif ($message['role'] === 'assistant') {
                    $chat->withAssistantMessage($message['content']);
                }
            }
            
            // Set temperature if provided
            if (isset($mergedOptions['temperature'])) {
                $chat->withTemperature($mergedOptions['temperature']);
            }
            
            // Add tools if provided
            if (!empty($mergedOptions['tools'])) {
                $chat->withTools($mergedOptions['tools']);
            }
            
            // Generate the response
            $response = $chat->generate();
            
            // Format response for LarAgent compatibility
            return [
                'message' => $response->text,
                'raw' => $response,
                'finish_reason' => $response->finish_reason ?? 'stop',
                'usage' => [
                    'prompt_tokens' => $response->usage->prompt_tokens ?? null,
                    'completion_tokens' => $response->usage->completion_tokens ?? null,
                    'total_tokens' => $response->usage->total_tokens ?? null,
                ],
            ];
            
        } catch (\Exception $e) {
            Log::error('Prism chat completion error', [
                'error' => $e->getMessage(),
                'provider' => $this->getProviderName(),
                'model' => $this->model,
            ]);
            
            throw $e;
        }
    }
}
