<?php

namespace App\Services\Workflow\NodeTypes;

use App\Models\AgentDynamicConfig;
use App\Services\Agent\AgentFactory;
use App\Services\Workflow\NodeInterface;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Support\Facades\Log;

class AgentNode implements NodeInterface
{
    protected AgentFactory $agentFactory;

    /**
     * Create a new agent node instance.
     *
     * @param AgentFactory $agentFactory The agent factory service
     */
    public function __construct(AgentFactory $agentFactory)
    {
        $this->agentFactory = $agentFactory;
    }

    /**
     * Execute the agent node.
     *
     * @param array $config Node configuration
     * @param array $context Current workflow context
     * @param WorkflowEngine $engine Workflow engine instance
     * @return mixed Node output
     */
    public function execute(array $config, array $context, WorkflowEngine $engine): mixed
    {
        // Check if we're using a predefined agent or a custom one
        if (!empty($config['agent_id'])) {
            // Get the agent configuration
            $agentConfig = AgentDynamicConfig::findOrFail($config['agent_id']);
            
            Log::info('Executing agent node with predefined agent', [
                'agent_id' => $agentConfig->id,
                'agent_name' => $agentConfig->name,
            ]);
        } else if (!empty($config['inline_config'])) {
            // Use inline configuration
            $agentConfig = $config['inline_config'];
            
            Log::info('Executing agent node with inline configuration');
        } else {
            throw new \Exception("Agent node requires either agent_id or inline_config");
        }
        
        // Get user input from context
        $input = $config['input'] ?? '';
        
        // Resolve input from context variables if needed
        if (!empty($config['input_from_context'])) {
            $inputVar = $config['input_from_context'];
            $input = $context[$inputVar] ?? '';
        }
        
        // Create a dynamic agent instance
        $agent = $this->agentFactory->createAgent($agentConfig);
        
        // Execute the agent with the input
        $response = $agent->execute($input, [
            'context' => $context,
            'workflow_context' => $context,
        ]);
        
        Log::info('Agent execution completed', [
            'input' => $input,
            'response_length' => strlen($response),
        ]);
        
        return $response;
    }
    
    /**
     * Get the configuration schema for this node type.
     *
     * @return array Configuration schema
     */
    public function getConfigSchema(): array
    {
        return [
            'agent_id' => [
                'type' => 'select',
                'label' => 'Agent',
                'description' => 'Select a predefined agent',
                'model' => AgentDynamicConfig::class,
                'display_field' => 'name',
                'optional' => true,
                'condition' => ['inline_config', 'empty'],
            ],
            'inline_config' => [
                'type' => 'agent_config',
                'label' => 'Custom Agent Configuration',
                'description' => 'Configure a custom agent for this node',
                'optional' => true,
                'condition' => ['agent_id', 'empty'],
            ],
            'input' => [
                'type' => 'textarea',
                'label' => 'Input',
                'description' => 'Input text to send to the agent',
                'optional' => true,
                'condition' => ['input_from_context', 'empty'],
            ],
            'input_from_context' => [
                'type' => 'text',
                'label' => 'Input from Context',
                'description' => 'Context variable to use as input',
                'optional' => true,
                'condition' => ['input', 'empty'],
            ],
            'next_node_id' => [
                'type' => 'node_select',
                'label' => 'Next Node',
                'description' => 'Next node to execute after this one',
                'optional' => true,
            ],
            'error_node_id' => [
                'type' => 'node_select',
                'label' => 'Error Handler',
                'description' => 'Node to execute if this node fails',
                'optional' => true,
            ],
        ];
    }
    
    /**
     * Get UI rendering info for this node type.
     *
     * @return array UI info
     */
    public function getUiInfo(): array
    {
        return [
            'icon' => 'robot',
            'color' => '#6366F1', // Indigo
            'label' => 'Agent',
            'description' => 'Run an AI agent to process text input',
            'category' => 'AI',
        ];
    }
}
