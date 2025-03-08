<?php
namespace App\Services\Workflow\NodeTypes;

use App\Models\WorkflowExecution;
use App\Models\WorkflowNode;

class ReturnNode implements NodeTypeInterface
{
    /**
     * Execute the Return node
     */
    public function execute(WorkflowNode $node, array $input, WorkflowExecution $execution): array
    {
        $config = $node->config;
        
        // By default, return all input data
        if (empty($config['return_type']) || $config['return_type'] === 'all') {
            return $input;
        }
        
        // Return specific variables
        if ($config['return_type'] === 'specific' && !empty($config['variables'])) {
            $result = [];
            
            foreach ($config['variables'] as $variable) {
                $key = $variable['key'] ?? null;
                $path = $variable['path'] ?? null;
                
                if ($key && $path) {
                    // Extract the value from input using the specified path
                    $value = $this->getValueByPath($input, $path);
                    $result[$key] = $value;
                }
            }
            
            return $result;
        }
        
        // Return static value
        if ($config['return_type'] === 'static' && isset($config['static_value'])) {
            return [
                'result' => $config['static_value']
            ];
        }
        
        // Return custom value (JSON)
        if ($config['return_type'] === 'custom' && !empty($config['custom_json'])) {
            try {
                $customValue = json_decode($config['custom_json'], true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $customValue;
                }
            } catch (\Exception $e) {
                // If JSON parsing fails, return as string
                return [
                    'result' => $config['custom_json']
                ];
            }
        }
        
        // Default fallback: return all input
        return $input;
    }
    
    /**
     * Get JSON Schema for return configuration
     */
    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'return_type' => [
                    'type' => 'string',
                    'title' => 'Return Type',
                    'enum' => ['all', 'specific', 'static', 'custom'],
                    'default' => 'all',
                ],
                'variables' => [
                    'type' => 'array',
                    'title' => 'Variables to Return',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'key' => [
                                'type' => 'string',
                                'title' => 'Output Key',
                            ],
                            'path' => [
                                'type' => 'string',
                                'title' => 'Input Path',
                            ],
                        ],
                    ],
                ],
                'static_value' => [
                    'type' => 'string',
                    'title' => 'Static Value',
                ],
                'custom_json' => [
                    'type' => 'string',
                    'title' => 'Custom JSON',
                    'x-control' => 'code-editor',
                    'x-language' => 'json',
                ],
            ],
        ];
    }
    
    /**
     * Validate return configuration
     */
    public function validateConfig(array $config): bool
    {
        // All configurations are valid for return node
        return true;
    }
    
    /**
     * Get value from nested array by dot notation path
     */
    private function getValueByPath(array $data, string $path)
    {
        // Handle empty path
        if (empty($path)) {
            return null;
        }
        
        // Split path into segments
        $segments = explode('.', $path);
        $current = $data;
        
        // Navigate through path segments
        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } elseif (is_object($current) && property_exists($current, $segment)) {
                $current = $current->{$segment};
            } else {
                return null; // Path segment not found
            }
        }
        
        return $current;
    }
}
