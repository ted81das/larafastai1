<?php

namespace App\Jobs;

use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteWorkflow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Workflow $workflow;
    protected ?array $input;
    protected ?WorkflowExecution $execution;
    protected bool $async;
    protected ?string $triggerType;
    protected ?string $triggerSource;

    /**
     * Create a new job instance.
     *
     * @param Workflow $workflow The workflow to execute
     * @param array|null $input Input data for the workflow
     * @param WorkflowExecution|null $execution An existing execution record, or null to create one
     * @param bool $async Whether to run asynchronously or not
     * @param string|null $triggerType The type of trigger (webhook, schedule, manual, etc)
     * @param string|null $triggerSource Additional information about the trigger source
     */
    public function __construct(
        Workflow $workflow, 
        ?array $input = [], 
        ?WorkflowExecution $execution = null,
        bool $async = true,
        ?string $triggerType = null,
        ?string $triggerSource = null
    ) {
        $this->workflow = $workflow;
        $this->input = $input ?? [];
        $this->execution = $execution;
        $this->async = $async;
        $this->triggerType = $triggerType ?? 'manual';
        $this->triggerSource = $triggerSource;
    }

    /**
     * Execute the job.
     */
    public function handle(WorkflowEngine $engine): void
    {
        try {
            // Create execution record if not provided
            if (!$this->execution) {
                $this->execution = WorkflowExecution::create([
                    'workflow_id' => $this->workflow->id,
                    'status' => 'running',
                    'input' => $this->input,
                    'output' => null,
                    'trigger_type' => $this->triggerType,
                    'trigger_source' => $this->triggerSource,
                    'started_at' => now(),
                ]);
            } else {
                // Update existing execution
                $this->execution->status = 'running';
                $this->execution->started_at = now();
                $this->execution->save();
            }
            
            Log::info('Workflow execution started', [
                'workflow_id' => $this->workflow->id,
                'workflow_name' => $this->workflow->name,
                'execution_id' => $this->execution->id,
                'trigger_type' => $this->triggerType,
            ]);
            
            // Run the workflow
            $result = $engine->execute($this->workflow, $this->input, $this->execution);
            
            // Update execution record
            $this->execution->status = 'completed';
            $this->execution->output = $result;
            $this->execution->completed_at = now();
            $this->execution->save();
            
            Log::info('Workflow execution completed', [
                'workflow_id' => $this->workflow->id,
                'workflow_name' => $this->workflow->name,
                'execution_id' => $this->execution->id,
                'trigger_type' => $this->triggerType,
            ]);
            
        } catch (\Throwable $e) {
            // Log the error
            Log::error('Workflow execution failed', [
                'workflow_id' => $this->workflow->id,
                'workflow_name' => $this->workflow->name,
                'execution_id' => $this->execution->id ?? null,
                'trigger_type' => $this->triggerType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Update execution record with error
            if ($this->execution) {
                $this->execution->status = 'failed';
                $this->execution->error = [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ];
                $this->execution->completed_at = now();
                $this->execution->save();
            }
            
            // Rethrow if this is a fatal error that should stop the queue
            if ($e instanceof \Error) {
                throw $e;
            }
        }
    }
    
    /**
     * The job failed to process.
     */
    public function failed(\Throwable $e): void
    {
        // Update execution record if it exists
        if ($this->execution) {
            $this->execution->status = 'failed';
            $this->execution->error = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
            $this->execution->completed_at = now();
            $this->execution->save();
        }
        
        Log::error('Workflow execution job failed', [
            'workflow_id' => $this->workflow->id,
            'workflow_name' => $this->workflow->name,
            'execution_id' => $this->execution->id ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
