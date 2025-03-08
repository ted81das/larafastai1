<?php 

namespace App\Services\Workflow\NodeTypes;

use App\Jobs\ScheduledWorkflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowNode;
use Carbon\Carbon;

class SchedulerNode implements NodeTypeInterface
{
    /**
     * Execute the Scheduler node
     */
    public function execute(WorkflowNode $node, array $input, WorkflowExecution $execution): array
    {
        $config = $node->config;
        
        // Check if this is a scheduled execution or just configuration
        $scheduledRun = $input['_scheduled'] ?? false;
        
        if ($scheduledRun) {
            // If this is a scheduled run, just pass through input
            return $input;
        } else {
            // This is just configuring the schedule, return schedule info
            $schedule = $this->getScheduleInfo($config);
            
            // Schedule the job based on the configuration
            $this->scheduleWorkflow($node, $config);
            
            return [
                'scheduled' => true,
                'schedule_type' => $config['schedule_type'],
                'next_run' => $schedule['next_run'],
                'description' => $schedule['description'],
            ];
        }
    }
    
    /**
     * Schedule the workflow to run
     */
    protected function scheduleWorkflow(WorkflowNode $node, array $config): void
    {
        $workflow = $node->workflow;
        
        // Only schedule if the workflow is active
        if (!$workflow->is_active) {
            return;
        }
        
        $schedule = $this->getScheduleInfo($config);
        
        // Dispatch the job with appropriate delay
        ScheduledWorkflow::dispatch($workflow, $node->id)
            ->delay($schedule['next_run']);
    }
    
    /**
     * Get schedule information based on configuration
     */
    protected function getScheduleInfo(array $config): array
    {
        $now = Carbon::now();
        $scheduleType = $config['schedule_type'] ?? 'none';
        
        switch ($scheduleType) {
            case 'interval':
                $interval = $config['interval'] ?? 60;
                $unit = $config['interval_unit'] ?? 'minutes';
                $nextRun = $now->copy()->add($interval, $unit);
                return [
                    'next_run' => $nextRun,
                    'description' => "Every {$interval} {$unit}",
                ];
                
            case 'daily':
                $time = $config['time'] ?? '00:00';
                list($hour, $minute) = explode(':', $time);
                $nextRun = $now->copy()->startOfDay()->addHours($hour)->addMinutes($minute);
                if ($nextRun->isPast()) {
                    $nextRun->addDay();
                }
                return [
                    'next_run' => $nextRun,
                    'description' => "Daily at {$time}",
                ];
                
            case 'weekly':
                $day = $config['day'] ?? 1; // Monday = 1, Sunday = 7
                $time = $config['time'] ?? '00:00';
                list($hour, $minute) = explode(':', $time);
                $nextRun = $now->copy()->startOfWeek()->addDays($day - 1)->addHours($hour)->addMinutes($minute);
                if ($nextRun->isPast()) {
                    $nextRun->addWeek();
                }
                $dayName = $nextRun->format('l');
                return [
                    'next_run' => $nextRun,
                    'description' => "Weekly on {$dayName} at {$time}",
                ];
                
            case 'monthly':
                $day = $config['day'] ?? 1;
                $time = $config['time'] ?? '00:00';
                list($hour, $minute) = explode(':', $time);
                $nextRun = $now->copy()->startOfMonth()->addDays($day - 1)->addHours($hour)->addMinutes($minute);
                if ($nextRun->isPast()) {
                    $nextRun->addMonth();
                }
                return [
                    'next_run' => $nextRun,
                    'description' => "Monthly on day {$day} at {$time}",
                ];
                
            case 'cron':
                $expression = $config['cron_expression'] ?? '* * * * *';
                $nextRun = Carbon::instance(\Cron\CronExpression::factory($expression)->getNextRunDate());
                return [
                    'next_run' => $nextRun,
                    'description' => "Cron: {$expression}",
                ];
                
            default:
                return [
                    'next_run' => null,
                    'description' => 'Not scheduled',
                ];
        }
    }
    
    /**
     * Get JSON Schema for scheduler configuration
     */
    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['schedule_type'],
            'properties' => [
                'schedule_type' => [
                    'type' => 'string',
                    'title' => 'Schedule Type',
                    'enum' => ['none', 'interval', 'daily', 'weekly', 'monthly', 'cron'],
                    'default' => 'none',
                ],
                'interval' => [
                    'type' => 'integer',
                    'title' => 'Interval',
                    'minimum' => 1,
                    'default' => 60,
                ],
                'interval_unit' => [
                    'type' => 'string',
                    'title' => 'Interval Unit',
                    'enum' => ['minutes', 'hours', 'days'],
                    'default' => 'minutes',
                ],
                'time' => [
                    'type' => 'string',
                    'title' => 'Time',
                    'format' => 'time',
                    'default' => '00:00',
                ],
                'day' => [
                    'type' => 'integer',
                    'title' => 'Day',
                    'minimum' => 1,
                    'maximum' => 31,
                    'default' => 1,
                ],
                'cron_expression' => [
                    'type' => 'string',
                    'title' => 'Cron Expression',
                    'pattern': '^(\\*|\\d+)(\\s+(\\*|\\d+)){4}$',
                    'default' => '* * * * *',
                ],
                'input_data' => [
                    'type' => 'object',
                    'title' => 'Input Data',
                    'description' => 'Data to pass to the workflow when triggered',
                    'additionalProperties' => true,
                ],
            ],
        ];
    }
    
    /**
     * Validate scheduler configuration
     */
    public function validateConfig(array $config): bool
    {
        if (empty($config['schedule_type']) || !in_array($config['schedule_type'], ['none', 'interval', 'daily', 'weekly', 'monthly', 'cron'])) {
            return false;
        }
        
        // Validate based on schedule type
        switch ($config['schedule_type']) {
            case 'interval':
                return isset($config['interval']) && is_numeric($config['interval']) && $config['interval'] > 0;
                
            case 'cron':
                return isset($config['cron_expression']) && $this->isValidCronExpression($config['cron_expression']);
                
            default:
                return true;
        }
    }
    
    /**
     * Validate a cron expression
     */
    protected function isValidCronExpression(string $expression): bool
    {
        try {
            \Cron\CronExpression::factory($expression);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
