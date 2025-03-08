<?php 

namespace App\Services\Workflow\NodeTypes;

use App\Models\WorkflowExecution;
use App\Models\WorkflowNode;

class WebhookNode implements NodeTypeInterface
{
    /**
     * Execute the webhook node
     * For webhook, execution is just passing through the received data
     */
    public function execute(WorkflowNode $node, array $input, WorkflowExecution $execution): array
    {
        // Webhook nodes just pass through their input
        return $input;
    }
    
    /**
     * Get JSON Schema for webhook configuration
     */
    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'description' => [
                    'type' => 'string',
                    'title' => 'Description',
                    'description' => 'Description of the expected webhook payload',
                ],
                'expectedFields' => [
                    'type' => 'array',
                    'title' => 'Expected Fields',
                    'description' => 'Fields expected in the webhook payload',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'title' => 'Field Name',
                            ],
                            'type' => [
                                'type' => 'string',
                                'title' => 'Field Type',
                                'enum' => ['string', 'number', 'boolean', 'object', 'array'],
                            ],
                            'description' => [
                                'type' => 'string',
                                'title' => 'Description',
                            ],
                            'required' => [
                                'type' => 'boolean',
                                'title' => 'Required',
                                'default' => false,
                            ],
                        ],
                        'required' => ['name', 'type'],
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Validate webhook configuration
     */
    public function validateConfig(array $config): bool
    {
        // Webhook config is always valid
        return true;
    }
}
