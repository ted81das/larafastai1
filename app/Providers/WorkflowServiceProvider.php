<?php

namespace App\Providers;

use App\Services\Workflow\NodeRegistry;
use App\Services\Workflow\NodeTypes\AgentNode;
use App\Services\Workflow\NodeTypes\ConditionNode;
use App\Services\Workflow\NodeTypes\HttpNode;
use App\Services\Workflow\NodeTypes\ReturnNode;
use App\Services\Workflow\NodeTypes\SchedulerNode;
use App\Services\Workflow\NodeTypes\TransformNode;
use App\Services\Workflow\NodeTypes\WebhookNode;
use App\Services\Workflow\VariableResolver;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register workflow components
        $this->app->singleton(NodeRegistry::class, function ($app) {
            $registry = new NodeRegistry();
            
            // Register core node types
            $registry->registerNodeType('webhook', WebhookNode::class);
            $registry->registerNodeType('http', HttpNode::class);
            $registry->registerNodeType('agent', AgentNode::class);
            $registry->registerNodeType('condition', ConditionNode::class);
            $registry->registerNodeType('transform', TransformNode::class);
            $registry->registerNodeType('scheduler', SchedulerNode::class);
            $registry->registerNodeType('return', ReturnNode::class);
            
            return $registry;
        });
        
        $this->app->singleton(VariableResolver::class, function ($app) {
            return new VariableResolver();
        });
        
        $this->app->singleton(WorkflowEngine::class, function ($app) {
            return new WorkflowEngine(
                $app->make(NodeRegistry::class),
                $app->make(VariableResolver::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
