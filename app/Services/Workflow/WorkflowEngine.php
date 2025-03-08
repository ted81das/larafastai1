<?php

namespace App\Services\Workflow;

use App\Models\ExecutionLog;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowNode;
use App\Services\Workflow\NodeRegistry;
use App\Services\Workflow\VariableResolver;
use Illuminate\Support\Facades\Log;

class WorkflowEngine
{
    protected NodeRegistry $nodeRegistry;
    protected VariableResolver $variableResolver;
    protected array $context = [];
    protected array $executionHistory = [];
    protected ?WorkflowExecution $executionRecord = null;

    /**
     * Create a new workflow engine instance.
     *
     * @param NodeRegistry $nodeRegistry Registry of available node types
     * @param VariableResolver $variableResolver Variable resolver for handling expressions
     */
    public function __construct(NodeRegistry $nodeRegistry, VariableResolver $variableResolver)
    {
        $this->nodeRegistry = $nodeRegistry;
        $this->variableResolver = $variableResolver;
    }

    /**
     * Execute a workflow
     *
     * @param Workflow $workflow The workflow to execute
     * @param array $input Input data for the workflow
     * @param WorkflowExecution|null $executionRecord Execution record for logging
     * @return array The workflow output
     */
    public function execute(Workflow $workflow, array $input = [], ?WorkflowExecution $executionRecord = null): array
    {
        $this->context = ['input' => $input];
        $this->executionHistory = [];
        $this->executionRecord = $executionRecord;
        
        // Get the starting node (entry point)
        $entryNode = $workflow->nodes()->where('type', 'entry')->first();
        
        if (!$entryNode) {
            throw new \Exception("Workflow does not have an entry node");
        }
        
        Log::info('Starting workflow execution', [
            'workflow_id' => $workflow->id,
            'workflow_name' => $workflow->name,
            'entry_node_id' => $entryNode->id,
        ]);
        
        // Execute from the entry node
        $result = $this->executeNode($entryNode);
        
        // Store the final context as the output
        return array_merge(
            ['result' => $result],
            ['context' => $this->context],
            ['execution_history' => $this->executionHistory]
        );
    }
    
    /**
     * Execute a specific node
     *
     * @param WorkflowNode $node The node to execute
     * @return mixed The node's output
     */
    public function executeNode(WorkflowNode $node): mixed
    {
        // Get the node implementation
        $nodeType = $this->nodeRegistry->getNodeType($node->type);
        
        if (!$nodeType) {
            $this->logNodeExecution($node, 'error', null, "Unknown node type: {$node->type}");
            throw new \Exception("Unknown node type: {$node->type}");
        }
        
        // Resolve variables in the node config
        $resolvedConfig = $this->resolveConfig($node->config);
        
        // Log node execution start
        $this->logNodeExecution($node, 'running', null, null);
        
        try {
            // Execute the node
            $result = $nodeType->execute($resolvedConfig, $this->context, $this);
            
            // Store the result in the context if the node has an output name
            if (!empty($node->output_name)) {
                $this->context[$node->output_name] = $result;
            }
            
            // Log node execution success
            $this->logNodeExecution($node, 'completed', $result, null);
            
            // Determine the next node to execute
            $nextNode = $this->determineNextNode($node, $result);
            
            // If there's a next node, execute it
            if ($nextNode) {
                return $this->executeNode($nextNode);
            }
            
            // Return the result if there's no next node
            return $result;
        } catch (\Throwable $e) {
            // Log node execution error
            $errorDetails = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
            
            $this->logNodeExecution($node, 'failed', null, $errorDetails);
            
            // Determine if there's an error handler node
            $errorNode = $this->findErrorHandler($node);
            
            if ($errorNode) {
                // Store error info in context
                $this->context['error'] = [
                    'message' => $e->getMessage(),
                    'node_id' => $node->id,
                    'node_type' => $node->type,
                ];
                
                // Execute the error handler
                return $this->executeNode($errorNode);
            }
            
            // Rethrow if no error handler
            throw $e;
        }
    }
    
    /**
     * Determine the next node to execute
     *
     * @param WorkflowNode $currentNode The current node
     * @param mixed $result The result of the current node
     * @return WorkflowNode|null The next node to execute, or null if none
     */
    protected function determineNextNode(WorkflowNode $currentNode, mixed $result): ?WorkflowNode
    {
        // If it's a condition node, determine the next node based on the result
        if ($currentNode->type === 'condition') {
            $trueNodeId = $currentNode->config['true_node_id'] ?? null;
            $falseNodeId = $currentNode->config['false_node_id'] ?? null;
            
            $targetNodeId = $result ? $trueNodeId : $falseNodeId;
            
            if ($targetNodeId) {
                return WorkflowNode::find($targetNodeId);
            }
            
            return null;
        }
        
        // For regular nodes, find the next node based on connections
        $nextNodeId = $currentNode->config['next_node_id'] ?? null;
        
        if ($nextNodeId) {
            return WorkflowNode::find($nextNodeId);
        }
        
        return null;
    }
    
    /**
     * Find an error handler for a node
     *
     * @param WorkflowNode $node The node to find an error handler for
     * @return WorkflowNode|null The error handler node, or null if none
     */
    protected function findErrorHandler(WorkflowNode $node): ?WorkflowNode
    {
        $errorNodeId = $node->config['error_node_id'] ?? null;
        
        if ($errorNodeId) {
            return WorkflowNode::find($errorNodeId);
        }
        
        return null;
    }
    
    /**
     * Resolve variables in a configuration object
     *
     * @param array $config Configuration to resolve
     * @return array Resolved configuration
     */
    protected function resolveConfig(array $config): array
    {
        return $this->variableResolver->resolveVariables($config, $this->context);
    }
    
    /**
     * Log a node execution
     *
     * @param WorkflowNode $node The node being executed
     * @param string $status The execution status
     * @param mixed $output The node output
     * @param mixed $error Error details if any
     */
    protected function logNodeExecution(WorkflowNode $node, string $status, mixed $output = null, mixed $error = null): void
    {
        // Add to execution history
        $executionRecord = [
            'node_id' => $node->id,
            'node_type' => $node->type,
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ];
        
        if ($output !== null) {
            $executionRecord['output'] = $output;
        }
        
        if ($error !== null) {
            $executionRecord['error'] = $error;
        }
        
        $this->executionHistory[] = $executionRecord;
        
        // Save to database if we have an execution record
        if ($this->executionRecord) {
            ExecutionLog::create([
                'execution_id' => $this->executionRecord->id,
                'node_id' => $node->id,
                'status' => $status,
                'output' => $output,
                'error' => $error,
            ]);
        }
    }
    
    /**
     * Get the current execution context
     *
     * @return array The current context
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Update the execution context
     *
     * @param string $key Context key
     * @param mixed $value Value to set
     */
    public function setContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }
}
