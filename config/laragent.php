<?php

// config for Maestroerror/LarAgent
return [
    'default_driver' => \LarAgent\Drivers\OpenAi\OpenAiDriver::class,
    'default_chat_history' => \LarAgent\History\InMemoryChatHistory::class,

    'providers' => [

        'default' => [
            'name' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'default_context_window' => 50000,
            'default_max_completion_tokens' => 100,
            'default_temperature' => 1,
        ],

        'prism' => [
            'name' => 'Prism',
            'driver' => \App\Services\LLM\PrismDriver::class,
            'models' => [
                // Format: "provider:model" 
                'anthropic:claude-3-opus' => [
                    'name' => 'Claude 3 Opus',
                    'context_window' => 200000,
                ],
                'anthropic:claude-3-sonnet' => [
                    'name' => 'Claude 3 Sonnet',
                    'context_window' => 180000,
                ],
                'anthropic:claude-3-haiku' => [
                    'name' => 'Claude 3 Haiku',
                    'context_window' => 150000,
                ],
                'openai:gpt-4o' => [
                    'name' => 'GPT-4o',
                    'context_window' => 128000,
                ],
                'openai:gpt-4-turbo' => [
                    'name' => 'GPT-4 Turbo',
                    'context_window' => 128000,
                ],
                'deepseek:deepseek-coder' => [
                    'name' => 'DeepSeek Coder',
                    'context_window' => 32768,
                ],
                'mistral:mistral-large-latest' => [
                    'name' => 'Mistral Large',
                    'context_window' => 32768,
                ],
            ]
        ],
    ],
];


/*

// Create an agent using a provider-specific model
$agent = AgentDynamicConfig::create([
    'user_id' => auth()->id(),
    'name' => 'Claude Assistant',
    'provider' => 'prism', 
    'model' => 'anthropic:claude-3-sonnet', // Provider:model format
    'temperature' => 0.7,
]);

// Store the user's API key for Anthropic
auth()->user()->providerApiKeys()->updateOrCreate(
    [
        'provider' => 'anthropic',
        'model' => 'claude-3-sonnet',
    ],
    [
        'api_key' => $request->input('anthropic_api_key')
    ]
);

// Use the agent
$response = $agent->message("Tell me about quantum computing")->respond();
*/