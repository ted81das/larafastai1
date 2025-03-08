<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'execution_id',
        'node_id',
        'node_type',
        'node_name',
        'status',
        'input',
        'output',
        'error_message',
        'execution_time',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'execution_time' => 'float',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    // Relationships
    public function execution(): BelongsTo
    {
        return $this->belongsTo(WorkflowExecution::class, 'execution_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'node_id');
    }
}
