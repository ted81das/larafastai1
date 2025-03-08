<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\RAG\DocumentProcessor;
use App\Services\RAG\EmbeddingService;
use App\Services\RAG\VectorStoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes
    public int $tries = 3;
    
    protected KnowledgeDocument $document;
    protected array $options;

    /**
     * Create a new job instance.
     *
     * @param KnowledgeDocument $document The document to process
     * @param array $options Processing options
     */
    public function __construct(KnowledgeDocument $document, array $options = [])
    {
        $this->document = $document;
        $this->options = array_merge([
            'extractMetadata' => true,
            'splitIntoChunks' => true,
            'chunkSize' => 1000,
            'generateEmbeddings' => true,
        ], $options);
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentProcessor $processor, EmbeddingService $embeddingService, VectorStoreService $vectorStore): void
    {
        Log::info('Processing document', [
            'document_id' => $this->document->id,
            'filename' => $this->document->filename,
            'options' => $this->options,
        ]);
        
        try {
            // Update document status
            $this->document->status = 'processing';
            $this->document->save();
            
            // Get file path
            $filePath = Storage::disk('documents')->path($this->document->file_path);
            
            if (!file_exists($filePath)) {
                throw new \Exception("Document file not found: {$filePath}");
            }
            
            // Extract text content from document
            $content = $processor->extractText($filePath, $this->document->mime_type);
            
            if (empty($content)) {
                throw new \Exception("Failed to extract text from document");
            }
            
            // Save the raw content
            $this->document->content = $content;
            
            // Extract metadata if requested
            if ($this->options['extractMetadata']) {
                $metadata = $processor->extractMetadata($filePath, $this->document->mime_type);
                $this->document->metadata = $metadata;
            }
            
            // Generate chunks if requested
            if ($this->options['splitIntoChunks']) {
                $chunks = $processor->splitIntoChunks($content, $this->options['chunkSize']);
                $this->document->chunks = $chunks;
                
                // Generate embeddings for each chunk if requested
                if ($this->options['generateEmbeddings'] && !empty($chunks)) {
                    Log::info('Generating embeddings for chunks', [
                        'document_id' => $this->document->id,
                        'chunks_count' => count($chunks),
                    ]);
                    
                    $chunkTexts = array_column($chunks, 'text');
                    $embeddings = $embeddingService->generateEmbeddingsBatch($chunkTexts);
                    
                    // Add embeddings to chunks
                    foreach ($chunks as $index => $chunk) {
                        if (isset($embeddings[$index])) {
                            $chunks[$index]['embedding'] = $embeddings[$index];
                        }
                    }
                    
                    $this->document->chunks = $chunks;
                    
                    // Store vectors in vector database
                    $vectorStore->upsertVectors(
                        $this->document->id,
                        $chunks,
                        [
                            'document_id' => $this->document->id,
                            'filename' => $this->document->filename,
                            'collection_id' => $this->document->collection_id,
                        ]
                    );
                }
            }
            
            // Update document status
            $this->document->status = 'processed';
            $this->document->processed_at = now();
            $this->document->save();
            
            Log::info('Document processed successfully', [
                'document_id' => $this->document->id,
                'filename' => $this->document->filename,
            ]);
        } catch (\Exception $e) {
            // Update document status to error
            $this->document->status = 'error';
            $this->document->error = $e->getMessage();
            $this->document->save();
            
            Log::error('Error processing document', [
                'document_id' => $this->document->id,
                'filename' => $this->document->filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Rethrow the exception to trigger the job's failed method
            throw $e;
        }
    }
    
    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Document processing job failed', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
        ]);
        
        // Ensure document is marked as failed
        $this->document->status = 'error';
        $this->document->error = $exception->getMessage();
        $this->document->save();
    }
}
