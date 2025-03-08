<?php

namespace App\Services\RAG;

use App\Services\LLM\OpenAIAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected OpenAIAdapter $openAIAdapter;
    protected string $defaultModel;
    protected int $embeddingDimension;
    protected int $cacheTtl;

    public function __construct(OpenAIAdapter $openAIAdapter)
    {
        $this->openAIAdapter = $openAIAdapter;
        $this->defaultModel = config('services.openai.embedding_model', 'text-embedding-3-small');
        
        // Set dimension based on model
        $this->embeddingDimension = $this->getEmbeddingDimension($this->defaultModel);
        
        // Cache embeddings for 24 hours by default
        $this->cacheTtl = config('services.rag.embedding_cache_ttl', 86400); // 24 hours
    }

    /**
     * Generate embeddings for a text
     *
     * @param string $text The text to embed
     * @param string|null $model The embedding model to use
     * @param bool $useCache Whether to use the cache
     * @return array|null The embedding vector or null on failure
     */
    public function generateEmbedding(string $text, ?string $model = null, bool $useCache = true): ?array
    {
        $model = $model ?? $this->defaultModel;
        
        // Create a cache key based on the text and model
        $cacheKey = 'embedding_' . md5($text . '_' . $model);
        
        // Return from cache if available and cache is enabled
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            // Call the OpenAI API to generate the embedding
            $response = $this->openAIAdapter->getEmbedding($text, $model);
            
            if (!$response['success']) {
                Log::error('Failed to generate embedding', [
                    'error' => $response['error'] ?? 'Unknown error',
                    'text_length' => strlen($text)
                ]);
                return null;
            }
            
            $embedding = $response['embedding'];
            
            // Cache the embedding
            if ($useCache) {
                Cache::put($cacheKey, $embedding, $this->cacheTtl);
            }
            
            return $embedding;
        } catch (\Exception $e) {
            Log::error('Error generating embedding', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Generate embeddings for multiple texts in batch
     *
     * @param array $texts Array of texts to embed
     * @param string|null $model The embedding model to use
     * @param bool $useCache Whether to use the cache
     * @return array Array of embeddings or empty arrays for failed embeddings
     */
    public function generateEmbeddingsBatch(array $texts, ?string $model = null, bool $useCache = true): array
    {
        $model = $model ?? $this->defaultModel;
        $embeddings = [];
        
        foreach ($texts as $text) {
            $embedding = $this->generateEmbedding($text, $model, $useCache);
            $embeddings[] = $embedding ?? array_fill(0, $this->embeddingDimension, 0);
        }
        
        return $embeddings;
    }
    
    /**
     * Calculate cosine similarity between two vectors
     *
     * @param array $vec1 First vector
     * @param array $vec2 Second vector
     * @return float Cosine similarity (between -1 and 1)
     */
    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        if (empty($vec1) || empty($vec2) || count($vec1) !== count($vec2)) {
            return 0.0;
        }
        
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Find most similar texts based on embeddings
     *
     * @param string $query Query text
     * @param array $documents Array of documents with 'text' and 'embedding' keys
     * @param int $topK Number of results to return
     * @return array Most similar documents with similarity scores
     */
    public function findSimilarTexts(string $query, array $documents, int $topK = 5): array
    {
        // Generate embedding for the query
        $queryEmbedding = $this->generateEmbedding($query);
        
        if (!$queryEmbedding) {
            return [];
        }
        
        $results = [];
        
        // Calculate similarity for each document
        foreach ($documents as $index => $document) {
            // Skip documents without embeddings
            if (empty($document['embedding'])) {
                continue;
            }
            
            $similarity = $this->cosineSimilarity($queryEmbedding, $document['embedding']);
            
            $results[] = [
                'document' => $document,
                'similarity' => $similarity,
                'index' => $index
            ];
        }
        
        // Sort by similarity (highest first)
        usort($results, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        // Return top K results
        return array_slice($results, 0, $topK);
    }
    
    /**
     * Get the embedding dimension for a given model
     *
     * @param string $model The embedding model
     * @return int The dimension
     */
    private function getEmbeddingDimension(string $model): int
    {
        $dimensions = [
            'text-embedding-ada-002' => 1536,
            'text-embedding-3-small' => 1536,
            'text-embedding-3-large' => 3072
        ];
        
        return $dimensions[$model] ?? 1536; // Default to 1536 if model not found
    }
    
    /**
     * Get the dimension of the embeddings generated by the current model
     *
     * @return int The dimension
     */
    public function getEmbeddingSize(): int
    {
        return $this->embeddingDimension;
    }
    
    /**
     * Get the current model being used
     *
     * @return string The model name
     */
    public function getCurrentModel(): string
    {
        return $this->defaultModel;
    }
}
