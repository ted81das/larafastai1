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
            'name' => 'prism',
            'driver' => \App\Services\LLM\PrismDriver::class,
            'url' => env('PRISM_SERVER_URL'),
            'models' => [
                'deepseek' => ['name' => 'Deepseek', 'context_window' => 8192],
                'ollama' => ['name' => 'Ollama', 'context_window' => 4096],
                'anthropic' => ['name' => 'Claude', 'context_window' => 100000],
            ]
        ],
    ],
];
