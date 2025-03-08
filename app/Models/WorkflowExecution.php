<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'user_id',
        'trigger_type',
        'input_data',
        'output_data',
        'status',
        'executed_at',
        'completed_at',
        'error_message',
        'execution_time',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'executed_at' => 'datetime',
        'completed_at' => 'datetime',
        'execution_time' => 'float',
    ];

    // Execution status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // Trigger type constants
    public const TRIGGER_WEBHOOK = 'webhook';
    public const TRIGGER_SCHEDULE = 'schedule';
    public const TRIGGER_MANUAL = 'manual';

    // Relationships
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExecutionLog::class);
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
