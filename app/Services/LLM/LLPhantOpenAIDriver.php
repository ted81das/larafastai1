<?php

namespace App\Services\LLM;

use App\Models\CustomOpenAIEndpoint;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use LarAgent\Contracts\LLMDriver;
use Llphant\Chat\OpenAIChatModel;
use Llphant\Formatting\ChatMessage;
use Llphant\Llphant;

class LLPhantOpenAIDriver implements LLMDriver
{
    protected ?User $user = null;
    protected string $model;
    protected ?CustomOpenAIEndpoint $endpoint = null;
    protected array $options = [];

    /**
     * Create a new LLPhantOpenAIDriver instance
     * 
     * @param string $endpoint_id The ID of the custom OpenAI endpoint to use
     * @param string $model The model name to use
     */
    public function __construct(string $endpoint_id, string $model)
    {
        $this->endpoint = CustomOpenAIEndpoint::findOrFail($endpoint_id);
        $this->model = $model;
    }

    /**
     * Set the user context for the driver
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
     * Create an LLPhant OpenAI chat model configured with the custom endpoint
     */
    protected function createChatModel(): OpenAIChatModel
    {
        // Create a custom LLPhant OpenAI model with the custom endpoint configuration
        $chatModel = new OpenAIChatModel(
            apiKey: $this->endpoint->api_key,
            model: $this->model,
            baseUrl: $this->endpoint->base_url
        );

        // Configure additional parameters
        $options = array_merge([
            'temperature' => 0.7,
            'max_tokens' => $this->endpoint->max_tokens,
        ], $this->options);

        $chatModel->setTemperature($options['temperature']);
        $chatModel->setMaxTokens($options['max_tokens']);
        
        if (isset($options['top_p'])) {
            $chatModel->setTopP($options['top_p']);
        }
        
        return $chatModel;
    }

    /**
     * Execute a chat completion using LLPhant
     */
    public function chatCompletion(array $messages, array $options = [])
    {
        try {
            // Set options
            $this->withOptions($options);
            
            // Create the chat model
            $chatModel = $this->createChatModel();
            
            // Format messages for LLPhant
            $formattedMessages = [];
            foreach ($messages as $message) {
                $formattedMessages[] = new ChatMessage(
                    role: $message['role'],
                    content: $message['content']
                );
            }
            
            // Generate the response
            $response = $chatModel->generate($formattedMessages);
            
            // Format response for LarAgent compatibility
            return [
                'message' => $response,
                'raw' => $response,
                'finish_reason' => 'stop',
                'usage' => [
                    'prompt_tokens' => null, // LLPhant doesn't return this by default 
                    'completion_tokens' => null,
                    'total_tokens' => null,
                ],
            ];
            
        } catch (\Exception $e) {
            Log::error('LLPhant OpenAI chat completion error', [
                'error' => $e->getMessage(),
                'endpoint' => $this->endpoint->id,
                'model' => $this->model,
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Get streaming response (if supported)
     */
    public function streamChatCompletion(array $messages, array $options = [])
    {
        try {
            // Set options
            $this->withOptions($options);
            
            // Create the chat model
            $chatModel = $this->createChatModel();
            
            // Format messages for LLPhant
            $formattedMessages = [];
            foreach ($messages as $message) {
                $formattedMessages[] = new ChatMessage(
                    role: $message['role'],
                    content: $message['content']
                );
            }
            
            // Generate streaming response
            return $chatModel->generateStream($formattedMessages);
            
        } catch (\Exception $e) {
            Log::error('LLPhant OpenAI streaming chat completion error', [
                'error' => $e->getMessage(),
                'endpoint' => $this->endpoint->id,
                'model' => $this->model,
            ]);
            
            throw $e;
        }
    }
}
