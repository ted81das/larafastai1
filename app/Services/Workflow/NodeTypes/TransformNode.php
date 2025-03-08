<?php 
namespace App\Services\Workflow\NodeTypes;

use App\Models\WorkflowExecution;
use App\Models\WorkflowNode;
use V8Js;

class TransformNode implements NodeTypeInterface
{
    /**
     * Execute the Transform node
     */
    public function execute(WorkflowNode $node, array $input, WorkflowExecution $execution): array
    {
        $config = $node->config;
        $script = $config['script'] ?? '';
        
        if (empty($script)) {
            return $input;
        }
        
        // Execute JavaScript transformation
        try {
            // Prepare input data as JSON
            $inputJson = json_encode($input);
            
            // Build full script with input data
            $fullScript = "const input = {$inputJson};\n" .
                          "let output = input;\n" .
                          "{$script}\n" .
                          "JSON.stringify(output);";
            
            // Execute with V8Js
            $v8 = new V8Js();
            $result = $v8->executeString($fullScript);
            
            // Parse result
            return json_decode($result, true) ?? $input;
        } catch (\Exception $e) {
            throw new \Exception("Transform script execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get JSON Schema for transform configuration
     */
    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['script'],
            'properties' => [
                'script' => [
                    'type' => 'string',
                    'title' => 'Transform Script',
                    'description' => 'JavaScript code to transform the input. The input is available as "input" variable, and the result should be assigned to "output".',
                    'x-control' => 'code-editor',
                    'x-language' => 'javascript',
                ],
            ],
        ];
    }
    
    /**
     * Validate transform configuration
     */
    public function validateConfig(array $config): bool
    {
        return isset($config['script']) && is_string($config['script']);
    }
}
