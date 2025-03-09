<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LarAgent\Agent;
use LarAgent\Message;
use LarAgent\Tool;
use Illuminate\Support\Facades\Log;

class AgentDynamicConfig extends Model
{
    use HasFactory;

    // Keep your Eloquent model functionality
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'team_id',
        'instruction',
        'model',
        'temperature',
        'tools',
        'rag_enabled',
        'rag_config',
        'metadata',
        'response_schema',
        'provider', //added for prism provider
        'api_key', //added for prism compatibility 
        'provider_settings'
    ];

    protected $casts = [
        'tools' => 'array',
        'rag_config' => 'array',
        'metadata' => 'array',
        'rag_enabled' => 'boolean',
        'temperature' => 'float',
        'response_schema' => 'array',
         'provider_settings' => 'array',
    ];
    
    // Internal LarAgent instance
    protected ?Agent $agentInstance = null;
    
    /**
     * Get or create the internal LarAgent instance
     */
    protected function getAgent(): Agent
    {
        if (!$this->agentInstance) {
            $sessionKey = "agent_{$this->id}";
            $this->agentInstance = Agent::for($sessionKey);
            
            // Configure the agent with our properties
            if ($this->instruction) {
                $this->agentInstance->withInstructions($this->instruction);
            }
            
            if ($this->model) {
                $this->agentInstance->setModel($this->model);
            }
            
            if ($this->temperature) {
                $this->agentInstance->temperature($this->temperature);
            }
            
            if ($this->response_schema) {
                $this->agentInstance->structured($this->response_schema);
            }
            
            // Register tools
            $this->registerLarAgentTools();
        }
        
        return $this->agentInstance;
    }
    
    /**
     * Register tools with LarAgent
     */
    protected function registerLarAgentTools(): void
    {
        if (!$this->agentInstance || empty($this->tools)) {
            return;
        }
        
        // Get tools configuration
        $tools = $this->getToolsConfig();
        
        // Register each tool with LarAgent
        foreach ($tools as $toolConfig) {
            if ($toolConfig instanceof Tool) {
                $this->agentInstance->withTool($toolConfig);
            } elseif (is_array($toolConfig) && isset($toolConfig['type']) && $toolConfig['type'] === 'function') {
                // Convert array config to Tool instance
                $functionConfig = $toolConfig['function'] ?? [];
                $tool = Tool::create(
                    $functionConfig['name'] ?? 'unnamed_tool',
                    $functionConfig['description'] ?? ''
                );
                
                // Add parameters
                if (isset($functionConfig['parameters']['properties'])) {
                    foreach ($functionConfig['parameters']['properties'] as $name => $prop) {
                        $tool->addProperty(
                            $name,
                            $prop['type'] ?? 'string',
                            $prop['description'] ?? '',
                            $prop['enum'] ?? []
                        );
                        
                        // Set as required if needed
                        if (isset($functionConfig['parameters']['required']) && 
                            in_array($name, $functionConfig['parameters']['required'])) {
                            $tool->setRequired($name);
                        }
                    }
                }
                
                // Set callback based on tool name
                $tool->setCallback([$this, 'handleToolCall']);
                
                $this->agentInstance->withTool($tool);
            }
        }
    }
    
    // [Keep all your existing getToolsConfig(), handleRetrieval(), etc. methods]
    
    /**
     * Send a message to the agent and get its response
     */
    public function message($userMessage)
    {
        return $this->getAgent()->message($userMessage);
    }
    
    /**
     * Execute chat completion with dynamic config
     */
    public function executeCompletion(array $messages, array $options = [])
    {
        try {
            // Get the last message as the current prompt
            $lastMessage = end($messages);
            $userMessage = $lastMessage['content'] ?? '';
            
            $agent = $this->getAgent();
            
            // Apply temperature if provided
            if (isset($options['temperature'])) {
                $agent->temperature($options['temperature']);
            }
            
            // Apply model if provided
            if (isset($options['model'])) {
                $agent->setModel($options['model']);
            }
            
            // Create the message
            $message = Message::user($userMessage);
            
            // If there are images in the message
            if (isset($lastMessage['images']) && is_array($lastMessage['images'])) {
                foreach ($lastMessage['images'] as $imageUrl) {
                    $message = $message->withImage($imageUrl);
                }
            }
            
            // Execute the LarAgent respond method
            $response = $agent->withMessage($message)->respond();
            
            // Format the response to match your expected format
            return [
                'success' => true,
                'content' => is_string($response) ? $response : json_encode($response),
                'data' => [
                    'choices' => [
                        [
                            'message' => [
                                'content' => is_string($response) ? $response : json_encode($response),
                                'role' => 'assistant'
                            ]
                        ]
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error in executeCompletion', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Relationship to user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    
    /**
     * Create a new agent instance for a specific user
     */
    public static function forUser(User $user, array $config = []): self
    {
        $config['user_id'] = $user->id;
        return self::create($config);
    }

//for prism clients and models
  /*  public function getModelClient()
    {
        if ($this->provider === 'prism') {
            return new PrismAdapter([
                'api_key' => $this->api_key ?? config('services.prism.api_key'),
                'url' => config('services.prism.url'),
                'settings' => $this->provider_settings
            ]);
        }
        
        return parent::getModelClient();
    }*/


     /**
     * Get the LLM client for this agent configuration
     */
    public function getModelClient()
    {
        if ($this->provider === 'prism') {
            // Parse the model string to get provider and model
            // Format: "provider:model" (e.g. "anthropic:claude-3-sonnet")
            if (str_contains($this->model, ':')) {
                [$provider, $model] = explode(':', $this->model, 2);
                
                return (new PrismDriver($provider, $model))
                    ->setUser($this->user)
                    ->withOptions([
                        'temperature' => $this->temperature ?? 0.7
                    ]);
            }
            
            // Fallback for legacy format that doesn't include provider
            return parent::getModelClient();
        }
        
        return parent::getModelClient();
    }
    
    /**
     * The user who owns this agent configuration
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new agent instance for a specific team
     */
    public static function forTeam(Team $team, array $config = []): self
    {
        $config['team_id'] = $team->id;
        return self::create($config);
    }
}


/*
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LarAgent\Agent;
use LarAgent\Message;
use LarAgent\Tool;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable;

class AgentDynamicConfig extends Agent
{
    use HasFactory;

    // Database configuration
    protected $table = 'agent_dynamic_configs';
    public $timestamps = true;

    // Properties from both AgentDynamicConfig and LarAgent
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'team_id',
        'instruction',
        'model',
        'temperature',
        'tools',
        'rag_enabled',
        'rag_config',
        'metadata',
        'response_schema'
    ];

    protected $casts = [
        'tools' => 'array',
        'rag_config' => 'array',
        'metadata' => 'array',
        'rag_enabled' => 'boolean',
        'temperature' => 'float',
        'response_schema' => 'array',
    ];

    
    // Constructor that initializes both Eloquent and LarAgent
     
    public function __construct(array $attributes = [])
    {
        // Generate a unique session key for this agent
        $sessionKey = isset($attributes['id']) 
            ? "agent_{$attributes['id']}" 
            : 'agent_' . (auth()->id() ?? 'guest') . '_' . uniqid();
        
        // Initialize LarAgent with session key
        parent::__construct($sessionKey);
        
        // Set Eloquent attributes
        $this->fill($attributes);
        
        // Configure LarAgent properties
        if (isset($attributes['instruction'])) {
            $this->instructions = $attributes['instruction'];
        }
        
        if (isset($attributes['model'])) {
            $this->setModel($attributes['model']);
        }
        
        if (isset($attributes['temperature'])) {
            $this->temperature($attributes['temperature']);
        }
        
        if (isset($attributes['response_schema'])) {
            $this->responseSchema = $attributes['response_schema'];
        }
        
        // Register tools if present
        if (isset($attributes['tools']) && is_array($attributes['tools'])) {
            $this->registerLarAgentTools();
        }
    }

    
    // Override instructions method for LarAgent
     
    public function instructions()
    {
        return $this->instruction ?? '';
    }

    /**
     * Override structuredOutput method for LarAgent
     
    public function structuredOutput()
    {
        return $this->responseSchema ?? null;
    }

    /**
     * Relationship to user
     
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to team
     
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Register tools with LarAgent
     
    protected function registerLarAgentTools(): void
    {
        // Get tools configuration
        $tools = $this->getToolsConfig();
        
        // Register each tool with LarAgent
        foreach ($tools as $toolConfig) {
            if ($toolConfig instanceof Tool) {
                $this->withTool($toolConfig);
            } elseif (is_array($toolConfig) && isset($toolConfig['type']) && $toolConfig['type'] === 'function') {
                // Convert array config to Tool instance
                $functionConfig = $toolConfig['function'] ?? [];
                $tool = Tool::create(
                    $functionConfig['name'] ?? 'unnamed_tool',
                    $functionConfig['description'] ?? ''
                );
                
                // Add parameters
                if (isset($functionConfig['parameters']['properties'])) {
                    foreach ($functionConfig['parameters']['properties'] as $name => $prop) {
                        $tool->addProperty(
                            $name,
                            $prop['type'] ?? 'string',
                            $prop['description'] ?? '',
                            $prop['enum'] ?? []
                        );
                        
                        // Set as required if needed
                        if (isset($functionConfig['parameters']['required']) && 
                            in_array($name, $functionConfig['parameters']['required'])) {
                            $tool->setRequired($name);
                        }
                    }
                }
                
                // Set callback based on tool name
                $tool->setCallback([$this, 'handleToolCall']);
                
                $this->withTool($tool);
            }
        }
    }

    /**
     * Get tools configuration
   
    public function getToolsConfig(): array
    {
        $toolsConfig = [];
        $tools = $this->tools ?? [];
        
        // Handle various tool types
        foreach ($tools as $tool) {
            $type = $tool['type'] ?? '';
            
            if ($type === 'retrieval' && $this->rag_enabled) {
                // Add RAG retrieval tool
                $toolsConfig[] = Tool::create(
                    'search_knowledge_base',
                    'Search for information in the knowledge base'
                )->addProperty(
                    'query', 
                    'string', 
                    'The search query to find relevant information'
                )->setCallback([$this, 'handleRetrieval']);
            } elseif ($type === 'function') {
                // Add custom function tool
                $toolsConfig[] = [
                    'type' => 'function',
                    'function' => $tool['function'] ?? [],
                ];
            }
        }
        
        return $toolsConfig;
    }

    /**
     * Handle RAG retrieval tool calls
     
    public function handleRetrieval($args)
    {
        try {
            $query = $args['query'] ?? '';
            
            if (empty($query)) {
                return [
                    'error' => 'Empty query provided',
                    'results' => []
                ];
            }
            
            // Use RAG configuration to perform retrieval
            $ragConfig = $this->rag_config ?? [];
            $collectionName = $ragConfig['collection'] ?? 'default';
            $limit = $ragConfig['limit'] ?? 5;
            
            // This would be your implementation to query the vector store
            // For example, using QdrantClient or similar
            
            // Placeholder for actual implementation
            Log::info('RAG retrieval requested', [
                'query' => $query,
                'collection' => $collectionName,
                'limit' => $limit
            ]);
            
            return [
                'results' => [
                    'content' => "This is placeholder content from RAG retrieval for query: {$query}",
                    'source' => 'knowledge_base'
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error in RAG retrieval', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'error' => 'Error during retrieval: ' . $e->getMessage(),
                'results' => []
            ];
        }
    }

    /**
     * Generic handler for tool calls
     
    public function handleToolCall($args)
    {
        // Implement appropriate logic based on tool name
        $toolName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['args'][0] ?? null;
        
        Log::info('Tool called', [
            'tool' => $toolName,
            'args' => $args
        ]);
        
        // Route to specific handlers based on tool name
        if ($toolName === 'search_knowledge_base') {
            return $this->handleRetrieval($args);
        }
        
        // Default handler for other tools
        // This would be extended based on your specific tools
        return [
            'result' => "Tool {$toolName} executed with arguments: " . json_encode($args)
        ];
    }

    /**
     * Execute chat completion with dynamic config
     * This preserves your existing API while using LarAgent under the hood
     
    public function executeCompletion(array $messages, array $options = [])
    {
        try {
            // Get the last message as the current prompt
            $lastMessage = end($messages);
            $userMessage = $lastMessage['content'] ?? '';
            
            // Apply temperature if provided
            if (isset($options['temperature'])) {
                $this->temperature($options['temperature']);
            }
            
            // Apply model if provided
            if (isset($options['model'])) {
                $this->setModel($options['model']);
            }
            
            // Create the message
            $message = Message::user($userMessage);
            
            // If there are images in the message
            if (isset($lastMessage['images']) && is_array($lastMessage['images'])) {
                foreach ($lastMessage['images'] as $imageUrl) {
                    $message = $message->withImage($imageUrl);
                }
            }
            
            // Add the message to the agent
            $this->withMessage($message);
            
            // Apply structured output schema if present
            if ($this->responseSchema) {
                $this->structured($this->responseSchema);
            }
            
            // Check for streaming option
            if (isset($options['stream']) && $options['stream']) {
                // Handle streaming (this would need to be implemented based on your needs)
                // LarAgent doesn't have built-in streaming yet
                return $this->handleStreamingResponse($options);
            }
            
            // Execute the LarAgent respond method
            $response = $this->respond();
            
            // Format the response to match your expected format
            return [
                'success' => true,
                'content' => is_string($response) ? $response : json_encode($response),
                'data' => [
                    'choices' => [
                        [
                            'message' => [
                                'content' => is_string($response) ? $response : json_encode($response),
                                'role' => 'assistant'
                            ]
                        ]
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error in executeCompletion', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle streaming responses (placeholder)
     
    protected function handleStreamingResponse(array $options)
    {
        // This is a placeholder for streaming implementation
        // LarAgent doesn't natively support streaming yet
        
        // You could implement this by using the underlying driver directly
        return [
            'success' => false,
            'error' => 'Streaming not implemented with LarAgent integration'
        ];
    }

    /**
     * Get model client based on model type (placeholder)
     
    protected function getModelClient()
    {
        // This is a placeholder that would be replaced with actual implementation
        // based on your current logic
        return null;
    }


  /**
 * Create a new agent instance for a specific user
 * This preserves the existing factory pattern while using LarAgent
 
public static function forUser(\Illuminate\Contracts\Auth\Authenticatable $user): static
{
    return new static([
        'user_id' => $user->getAuthIdentifier()
    ]);
}


    /**
 * Create a new agent instance for a specific user with additional configuration
 
public static function forUserWithConfig(\Illuminate\Contracts\Auth\Authenticatable $user, array $config = []): static
{
    $config['user_id'] = $user->getAuthIdentifier();
    return new static($config);
}


    /**
     * Create a new agent instance for a specific team
    /
    public static function forTeam(Team $team, array $config = []): self
    {
        $config['team_id'] = $team->id;
        return new static($config);
    }

    /**
     * Create a new agent instance with specific configuration
    /
    public static function withConfig(array $config): self
    {
        return new static($config);
    }
}


/* 

 

// app/Models/AgentDynamicConfig.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDynamicConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'team_id',
        'instruction',
        'model',
        'temperature',
        'tools',
        'rag_enabled',
        'rag_config',
        'metadata',
    ];

    protected $casts = [
        'tools' => 'array',
        'rag_config' => 'array',
        'metadata' => 'array',
        'rag_enabled' => 'boolean',
        'temperature' => 'float',
    ];

    // Relationship to user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to team
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // Methods for RAG integration
    public function getToolsConfig(): array
    {
        $toolsConfig = [];
        $tools = $this->tools ?? [];
        
        // Handle various tool types
        foreach ($tools as $tool) {
            $type = $tool['type'] ?? '';
            
            if ($type === 'retrieval' && $this->rag_enabled) {
                // Add RAG retrieval tool
                $toolsConfig[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => 'search_knowledge_base',
                        'description' => 'Search for information in the knowledge base',
                        'parameters' => [],
                    ],
                ];
            } elseif ($type === 'function') {
                // Add custom function tool
                $toolsConfig[] = [
                    'type' => 'function',
                    'function' => $tool['function'] ?? [],
                ];
            }
        }
        
        return $toolsConfig;
    }

    // Execute chat completion with dynamic config
    public function executeCompletion(array $messages, array $options = [])
    {
        // Get appropriate model client based on model type
        $client = $this->getModelClient();
        
        $params = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
        ];
        
        // Add tools if configured
        $tools = $this->getToolsConfig();
        if (!empty($tools)) {
            $params['tools'] = $tools;
            $params['tool_choice'] = 'auto';
        }
        
        // Add any additional options
        $params = array_merge($params, $options);
        
        // Return streaming response if requested
        if ($options['stream'] ?? false) {
            return $client->chat->completions->create($params);
        }
        
        return $client->chat->completions->create($params);
    }
}

//$response = $agent->executeCompletion($messages, $options);
//$response = $agent->message("Your question")->respond();

*/