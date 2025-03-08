<?php 

// app/Models/WorkflowNode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'type',
        'name',
        'sequence',
        'config',
        'input_mapping',
        'output_mapping',
    ];

    protected $casts = [
        'config' => 'array',
        'input_mapping' => 'array',
        'output_mapping' => 'array',
    ];

    // Node types constants
    public const TYPE_WEBHOOK = 'webhook';
    public const TYPE_HTTP = 'http';
    public const TYPE_AGENT = 'agent';
    public const TYPE_CONDITION = 'condition';
    public const TYPE_TRANSFORM = 'transform';
    public const TYPE_SCHEDULER = 'scheduler';
    public const TYPE_RETURN = 'return';

    // Relationship to workflow
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
