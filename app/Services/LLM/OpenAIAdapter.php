<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

class OpenAIAdapter
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;
    protected array $supportedModels;
    protected float $defaultTemperature;
    protected int $maxTokens;
    protected PendingRequest $http;
    
    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->baseUrl = config('services.openai.urls.base', 'https://api.openai.com/v1/');
        $this->defaultModel = config('services.openai.models.default', 'gpt-4o');
        $this->supportedModels = config('services.openai.models.supported', [
            'gpt-4o' => 'GPT-4o',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        ]);
        $this->defaultTemperature = config('services.openai.temperature', 0.7);
        $this->maxTokens = config('services.openai.max_tokens', 4000);
        
        $this->http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(60);
    }
    
    /**
     * Get chat completion from OpenAI API
     * 
     * @param array $messages Array of message objects
     * @param array $options Options for the API call
     * @return array Response with success status and data/error
     */
    public function getChatCompletion(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? $this->defaultTemperature;
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $stream = $options['stream'] ?? false;
        $tools = $options['tools'] ?? null;
        $responseFormat = $options['response_format'] ?? null;
        
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'stream' => $stream,
        ];
        
        if ($tools) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = $options['tool_choice'] ?? 'auto';
        }
        
        if ($responseFormat) {
            $payload['response_format'] = $responseFormat;
        }
        
        try {
            $response = $this->http->post($this->baseUrl . 'chat/completions', $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'data' => $data,
                    'content' => $data['choices'][0]['message']['content'] ?? null,
                    'function_call' => $data['choices'][0]['message']['function_call'] ?? null,
                    'tool_calls' => $data['choices'][0]['message']['tool_calls'] ?? null,
                ];
            } else {
                $error = $response->json();
                
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                
                return [
                    'success' => false,
                    'error' => $error['error']['message'] ?? 'Unknown error',
                    'status' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get streaming chat completion from OpenAI API
     * 
     * @param array $messages Array of message objects
     * @param callable $callback Callback function for each chunk
     * @param array $options Options for the API call
     * @return bool Success status
     */
    public function streamChatCompletion(array $messages, callable $callback, array $options = []): bool
    {
        $options['stream'] = true;
        
        try {
            $model = $options['model'] ?? $this->defaultModel;
            $temperature = $options['temperature'] ?? $this->defaultTemperature;
            $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
            $tools = $options['tools'] ?? null;
            
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'stream' => true,
            ];
            
            if ($tools) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = $options['tool_choice'] ?? 'auto';
            }
            
            if ($options['response_format'] ?? null) {
                $payload['response_format'] = $options['response_format'];
            }
            
            $response = $this->http->withOptions([
                'stream' => true,
            ])->post($this->baseUrl . 'chat/completions', $payload);
            
            $buffer = '';
            
            $response->throw()->toPsrResponse()->getBody()->rewind();
            
            $stream = $response->toPsrResponse()->getBody();
            
            while (!$stream->eof()) {
                $line = $this->readLine($stream);
                
                if (!empty($line)) {
                    // Handle SSE format
                    if (Str::startsWith($line, 'data: ')) {
                        $data = substr($line, 6);
                        
                        if ($data === '[DONE]') {
                            break;
                        }
                        
                        try {
                            $decodedData = json_decode($data, true);
                            
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $callback($decodedData);
                            }
                        } catch (\Exception $e) {
                            Log::error('Error parsing OpenAI stream data', [
                                'error' => $e->getMessage(),
                                'data' => $data,
                            ]);
                        }
                    }
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('OpenAI streaming API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Get embedding from OpenAI API
     * 
     * @param string $text Text to embed
     * @param string|null $model Embedding model to use
     * @return array Response with success status and embedding/error
     */
    public function getEmbedding(string $text, ?string $model = null): array
    {
        $model = $model ?? config('services.openai.embedding_model', 'text-embedding-3-small');
        
        try {
            $response = $this->http->post($this->baseUrl . 'embeddings', [
                'model' => $model,
                'input' => $text,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'embedding' => $data['data'][0]['embedding'],
                ];
            } else {
                $error = $response->json();
                
                Log::error('OpenAI Embedding API error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                
                return [
                    'success' => false,
                    'error' => $error['error']['message'] ?? 'Unknown error',
                    'status' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI Embedding API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
       /**
     * Read a line from a stream
     * 
     * @param \Psr\Http\Message\StreamInterface $stream The stream to read from
     * @return string|null The line read or null if EOF
     */
    private function readLine($stream): ?string
    {
        $buffer = '';
        
        while (!$stream->eof()) {
            $char = $stream->read(1);
            
            if ($char === "\n") {
                return $buffer;
            }
            
            $buffer .= $char;
        }
        
        return $buffer ?: null;
    }
    
    /**
     * Generate image from OpenAI API
     * 
     * @param string $prompt The prompt for image generation
     * @param array $options Options for the API call
     * @return array Response with success status and image data/error
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? 'dall-e-3';
        $size = $options['size'] ?? '1024x1024';
        $quality = $options['quality'] ?? 'standard';
        $n = $options['n'] ?? 1;
        
        try {
            $response = $this->http->post($this->baseUrl . 'images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'size' => $size,
                'quality' => $quality,
                'n' => $n,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'data' => $data,
                    'images' => $data['data'],
                ];
            } else {
                $error = $response->json();
                
                Log::error('OpenAI Image API error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                
                return [
                    'success' => false,
                    'error' => $error['error']['message'] ?? 'Unknown error',
                    'status' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI Image API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get supported models
     * 
     * @return array Supported model options
     */
    public function getSupportedModels(): array
    {
        return $this->supportedModels;
    }
    
    /**
     * Get default model
     * 
     * @return string Default model
     */
    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }
    
    /**
     * Generate audio speech from text
     * 
     * @param string $text Text to convert to speech
     * @param array $options Options for the API call
     * @return array Response with success status and audio/error
     */
    public function generateSpeech(string $text, array $options = []): array
    {
        $model = $options['model'] ?? 'tts-1';
        $voice = $options['voice'] ?? 'alloy';
        $responseFormat = $options['response_format'] ?? 'mp3';
        
        try {
            $response = $this->http->post($this->baseUrl . 'audio/speech', [
                'model' => $model,
                'input' => $text,
                'voice' => $voice,
                'response_format' => $responseFormat,
            ]);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'audio' => $response->body(),
                    'content_type' => 'audio/' . $responseFormat,
                ];
            } else {
                $error = $response->json();
                
                Log::error('OpenAI Speech API error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                
                return [
                    'success' => false,
                    'error' => $error['error']['message'] ?? 'Unknown error',
                    'status' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI Speech API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}