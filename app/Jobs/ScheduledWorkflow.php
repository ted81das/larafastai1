<?php
namespace App\Jobs;

use App\Models\Workflow;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScheduledWorkflow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Running scheduled workflow check');

        // Get all active scheduled workflows
        $workflows = Workflow::where('is_active', true)
            ->where('trigger_type', 'scheduled')
            ->get();

        foreach ($workflows as $workflow) {
            try {
                // Check if the workflow is due to run based on cron expression
                if ($this->shouldRunWorkflow($workflow)) {
                    Log::info('Scheduling workflow execution', [
                        'workflow_id' => $workflow->id,
                        'name' => $workflow->name,
                    ]);

                    // Dispatch the workflow execution job
                    ExecuteWorkflow::dispatch($workflow, [
                        'scheduled' => true,
                        'scheduled_at' => now()->toIso8601String(),
                    ]);

                    // Update the last scheduled run time
                    $workflow->update([
                        'last_scheduled_run' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error scheduling workflow', [
                    'workflow_id' => $workflow->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Determine if a workflow should run based on its schedule
     */
    protected function shouldRunWorkflow(Workflow $workflow): bool
    {
        // Get the cron expression
        $cronExpression = $this->getCronExpression($workflow);
        
        if (empty($cronExpression)) {
            return false;
        }

        // Parse the cron expression
        $cron = new CronExpression($cronExpression);
        
        // Get the last run time, or use creation time if never run
        $lastRun = $workflow->last_scheduled_run ?? $workflow->created_at;
        
        // Check if it's due to run
        $nextRunDate = Carbon::instance($cron->getNextRunDate($lastRun));
        $now = Carbon::now();
        
        return $nextRunDate->lte($now);
    }

    /**
     * Get the cron expression for a workflow
     */
    protected function getCronExpression(Workflow $workflow): ?string
    {
        // If cron expression is directly specified, use it
        if (!empty($workflow->cron_expression)) {
            return $workflow->cron_expression;
        }

        // Otherwise, convert the frequency to a cron expression
        switch ($workflow->schedule_frequency) {
            case 'minutely':
                return '* * * * *';
            case 'hourly':
                return '0 * * * *';
            case 'daily':
                return '0 0 * * *';
            case 'weekly':
                return '0 0 * * 0';
            case 'monthly':
                return '0 0 1 * *';
            default:
                return null;
        }
    }
}
