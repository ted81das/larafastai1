<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'team_id',
        'status',
        'webhook_url',
        'webhook_secret',
        'response_template',
        'is_scheduled',
        'schedule',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'response_template' => 'array',
        'is_scheduled' => 'boolean',
    ];

    // Generate unique webhook URL when creating
    protected static function booted()
    {
        static::creating(function ($workflow) {
            if (empty($workflow->webhook_url)) {
                $workflow->webhook_url = Str::uuid();
            }
            if (empty($workflow->webhook_secret)) {
                $workflow->webhook_secret = Str::random(32);
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(WorkflowNode::class)
            ->orderBy('sequence');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }
}
