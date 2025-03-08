<?php 
namespace App\Services\Workflow\NodeTypes;

use App\Models\WorkflowExecution;
use App\Models\WorkflowNode;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ConditionNode implements NodeTypeInterface
{
    protected $expressionLanguage;
    
    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }
    
    /**
     * Execute the Condition node
     */
    public function execute(WorkflowNode $node, array $input, WorkflowExecution $execution): array
    {
        $config = $node->config;
        $conditions = $config['conditions'] ?? [];
        
        // Evaluate each condition
        foreach ($conditions as $condition) {
            if ($this->evaluateCondition($condition['expression'], $input)) {
                // Return with condition result
                return [
                    'result' => true,
                    'path' => $condition['name'],
                    'input' => $input
                ];
            }
        }
        
        // If no conditions match, use default path
        return [
            'result' => false,
            'path' => 'default',
            'input' => $input
        ];
    }
    
    /**
     * Evaluate a condition expression
     */
    protected function evaluateCondition(string $expression, array $input): bool
    {
        try {
            // Convert dot notation to nested access
            $expressionWithVars = preg_replace_callback(
                '/input\.([a-zA-Z0-9_.]+)/',
                function ($matches) {
                    return '$input["' . str_replace('.', '"]["', $matches[1]) . '"]';
                },
                $expression
            );
            
            // Evaluate the expression
            return (bool) $this->expressionLanguage->evaluate($expressionWithVars, ['input' => $input]);
        } catch (\Exception $e) {
            // Log error and return false on failure
            \Log::error('Condition evaluation failed', [
                'expression' => $expression,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get JSON Schema for condition configuration
     */
    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['conditions'],
            'properties' => [
                'conditions' => [
                    'type' => 'array',
                    'title' => 'Conditions',
                    'description' => 'List of conditions to evaluate',
                    'items' => [
                        'type' => 'object',
                        'required' => ['name', 'expression'],
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'title' => 'Path Name',
                                'description' => 'Name for this condition path',
                            ],
                            'expression' => [
                                'type' => 'string',
                                'title' => 'Condition Expression',
                                'description' => 'Expression to evaluate. Use input.field to access input values.',
                                'examples' => ['input.status == "success"', 'input.count > 10'],
                            ],
                        ],
                    ],
                ],
                'default_path' => [
                    'type' => 'string',
                    'title' => 'Default Path',
                    'description' => 'Path to follow if no conditions match',
                    'default' => 'default',
                ],
            ],
        ];
    }
    
    /**
     * Validate condition configuration
     */
    public function validateConfig(array $config): bool
    {
        if (empty($config['conditions']) || !is_array($config['conditions'])) {
            return false;
        }
        
        foreach ($config['conditions'] as $condition) {
            if (empty($condition['name']) || empty($condition['expression'])) {
                return false;
            }
        }
        
        return true;
    }
}
