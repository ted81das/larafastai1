<?php

namespace App\Models;

use App\Jobs\ProcessDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class KnowledgeDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'collection_id',
        'filename',
        'file_path',
        'mime_type',
        'file_size',
        'text_content',
        'status',
        'metadata',
        'chunk_size',
        'chunk_overlap',
        'processed_at',
        'error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
        'error' => 'array',
        'chunk_size' => 'integer',
        'chunk_overlap' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Auto-process document when created
        static::created(function (KnowledgeDocument $document) {
            $document->process();
        });

        // Delete file when document is deleted
        static::deleted(function (KnowledgeDocument $document) {
            if ($document->file_path) {
                Storage::disk('documents')->delete($document->file_path);
            }
        });
    }

    /**
     * Get the collection that owns the document.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(DocumentCollection::class, 'collection_id');
    }

    /**
     * Get the chunks for this document.
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class, 'document_id');
    }

    /**
     * Queue document for processing.
     */
    public function process(): void
    {
        // Update status
        $this->update(['status' => 'pending']);

        // Queue processing job
        ProcessDocument::dispatch($this);
    }

    /**
     * Get the document text content.
     */
    public function getContent(): ?string
    {
        if ($this->text_content) {
            return $this->text_content;
        }

        if (!$this->file_path) {
            return null;
        }

        try {
            // Get file contents if available but not stored in the model
            return Storage::disk('documents')->get($this->file_path);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the full file path for the document.
     */
    public function getFullPath(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::disk('documents')->path($this->file_path);
    }

    /**
     * Set document as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'error' => null,
        ]);
    }

    /**
     * Set document as failed.
     */
    public function markAsFailed(string $errorMessage, ?array $errorDetails = null): void
    {
        $error = [
            'message' => $errorMessage,
        ];

        if ($errorDetails) {
            $error['details'] = $errorDetails;
        }

        $this->update([
            'status' => 'error',
            'error' => $error,
        ]);
    }
}
