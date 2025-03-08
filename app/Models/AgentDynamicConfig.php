<?php 

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
                        'parameters' => [/* ... */],
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
