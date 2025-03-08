<?php

namespace App\Services\Workflow\NodeTypes;

use App\Models\WorkflowExecution;
use App\Models\WorkflowNode;
use Illuminate\Support\Facades\Http;

class HttpNode implements NodeTypeInterface
{
    /**
     * Execute the HTTP node
     */
    public function execute(WorkflowNode $node, array $input, WorkflowExecution $execution): array
    {
        $config = $node->config;
        
        // Create request
        $request = Http::withHeaders($config['headers'] ?? []);
        
        // Add basic auth if configured
        if (!empty($config['auth']) && $config['auth']['type'] === 'basic') {
            $request = $request->withBasicAuth(
                $config['auth']['username'] ?? '',
                $config['auth']['password'] ?? ''
            );
        }
        
        // Add bearer token if configured
        if (!empty($config['auth']) && $config['auth']['type'] === 'bearer') {
            $request = $request->withToken($config['auth']['token'] ?? '');
        }
        
        // Prepare body
        $body = $config['body'] ?? null;
        
        // Make request based on method
        $method = strtoupper($config['method'] ?? 'GET');
        $url = $config['url'] ?? '';
        
        $response = match ($method) {
            'GET' => $request->get($url),
            'POST' => $request->post($url, $body),
            'PUT' => $request->put($url, $body),
            'PATCH' => $request->patch($url, $body),
            'DELETE' => $request->delete($url, $body),
            default => throw new \Exception("Unsupported HTTP method: {$method}"),
        };
        
        // Check for error
        if ($response->failed()) {
            throw new \Exception("HTTP request failed: " . $response->status() . " " . $response->body());
        }
        
        // Return response data
        return [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->json() ?? $response->body(),
        ];
    }
    
    /**
     * Get JSON Schema for HTTP configuration
     */
    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['url', 'method'],
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'title' => 'URL',
                    'description' => 'URL to send the request to',
                ],
                'method' => [
                    'type' => 'string',
                    'title' => 'Method',
                    'enum' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'default' => 'GET',
                ],
                'headers' => [
                    'type' => 'object',
                    'title' => 'Headers',
                    'description' => 'HTTP headers',
                    'additionalProperties' => true,
                ],
                'body' => [
                    'type' => ['object', 'string', 'null'],
                    'title' => 'Request Body',
                    'description' => 'Request body (for POST, PUT, PATCH)',
                ],
                'auth' => [
                    'type' => 'object',
                    'title' => 'Authentication',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'title' => 'Auth Type',
                            'enum' => ['none', 'basic', 'bearer'],
                            'default' => 'none',
                        ],
                        'username' => [
                            'type' => 'string',
                            'title' => 'Username',
                            'description' => 'Basic auth username',
                        ],
                        'password' => [
                            'type' => 'string',
                            'title' => 'Password',
                            'description' => 'Basic auth password',
                        ],
                        'token' => [
                            'type' => 'string',
                            'title' => 'Token',
                            'description' => 'Bearer token',
                        ],
                    ],
                
            ],
        ];
    }
    
    /**
     * Validate HTTP configuration
     */
    public function validateConfig(array $config): bool
    {
        if (empty($config['url'])) {
            return false;
        }
        
        if (empty($config['method']) || !in_array($config['method'], ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }
        
        return true;
    }
}
