<?php

namespace App\Services\Workflow;

use App\Models\WorkflowNode;
use App\Services\Workflow\NodeTypes\AgentNode;
use App\Services\Workflow\NodeTypes\ConditionNode;
use App\Services\Workflow\NodeTypes\HttpNode;
use App\Services\Workflow\NodeTypes\NodeTypeInterface;
use App\Services\Workflow\NodeTypes\ReturnNode;
use App\Services\Workflow\NodeTypes\SchedulerNode;
use App\Services\Workflow\NodeTypes\TransformNode;
use App\Services\Workflow\NodeTypes\WebhookNode;
use Illuminate\Support\Facades\App;

class NodeRegistry
{
    protected array $nodeTypes = [];
    
    /**
     * Register the default node types
     */
    public function __construct()
    {
        $this->registerCoreNodeTypes();
    }
    
    /**
     * Register the core node types
     */
    protected function registerCoreNodeTypes(): void
    {
        $this->registerNodeType(WorkflowNode::TYPE_WEBHOOK, WebhookNode::class, [
            'name' => 'Webhook',
            'description' => 'Receive data from external systems',
            'category' => 'Trigger',
            'icon' => 'globe',
            'color' => 'blue',
        ]);
        
        $this->registerNodeType(WorkflowNode::TYPE_HTTP, HttpNode::class, [
            'name' => 'HTTP Request',
            'description' => 'Make HTTP requests to external APIs',
            'category' => 'Integration',
            'icon' => 'arrow-up-right',
            'color' => 'green',
        ]);
        
        $this->registerNodeType(WorkflowNode::TYPE_AGENT, AgentNode::class, [
            'name' => 'AI Agent',
            'description' => 'Process inputs using AI agents',
            'category' => 'AI',
            'icon' => 'robot',
            'color' => 'purple',
        ]);
        
        $this->registerNodeType(WorkflowNode::TYPE_CONDITION, ConditionNode::class, [
            'name' => 'Condition',
            'description' => 'Branch workflow based on conditions',
            'category' => 'Logic',
            'icon' => 'git-branch',
            'color' => 'orange',
        ]);
        
        $this->registerNodeType(WorkflowNode::TYPE_TRANSFORM, TransformNode::class, [
            'name' => 'Transform',
            'description' => 'Transform data with JavaScript',
            'category' => 'Processing',
            'icon' => 'code',
            'color' => 'indigo',
        ]);
        
        $this->registerNodeType(WorkflowNode::TYPE_SCHEDULER, SchedulerNode::class, [
            'name' => 'Scheduler',
            'description' => 'Schedule workflows to run periodically',
            'category' => 'Trigger',
            'icon' => 'calendar',
            'color' => 'yellow',
        ]);
        
        $this->registerNodeType(WorkflowNode::TYPE_RETURN, ReturnNode::class, [
            'name' => 'Return',
            'description' => 'Return data from the workflow',
            'category' => 'Logic',
            'icon' => 'reply',
            'color' => 'gray',
        ]);
    }
    
    /**
     * Register a node type
     */
    public function registerNodeType(string $type, string $handlerClass, array $metadata = []): void
    {
        $this->nodeTypes[$type] = [
            'type' => $type,
            'handler' => $handlerClass,
            'metadata' => $metadata,
        ];
    }
    
    /**
     * Get all registered node types
     */
    public function getNodeTypes(): array
    {
        $result = [];
        
        foreach ($this->nodeTypes as $type => $info) {
            $handler = App::make($info['handler']);
            
            $result[$type] = array_merge($info['metadata'], [
                'type' => $type,
                'config_schema' => $handler->getConfigSchema(),
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get a node handler instance
     */
    public function getNodeHandler(string $type): NodeTypeInterface
    {
        if (!isset($this->nodeTypes[$type])) {
            throw new \InvalidArgumentException("Unknown node type: {$type}");
        }
        
        return App::make($this->nodeTypes[$type]['handler']);
    }
    
    /**
     * Check if a node type exists
     */
    public function hasNodeType(string $type): bool
    {
        return isset($this->nodeTypes[$type]);
    }
    
    /**
     * Get metadata for a node type
     */
    public function getNodeTypeMetadata(string $type): array
    {
        if (!isset($this->nodeTypes[$type])) {
            throw new \InvalidArgumentException("Unknown node type: {$type}");
        }
        
        return $this->nodeTypes[$type]['metadata'];
    }
}
