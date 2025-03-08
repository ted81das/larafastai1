<?php

namespace App\Services\RAG;

use App\Services\RAG\EmbeddingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VectorStoreService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $defaultCollection;
    protected EmbeddingService $embeddingService;
    protected int $vectorSize;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->baseUrl = rtrim(config('services.qdrant.url', 'http://localhost:6333'), '/');
        $this->apiKey = config('services.qdrant.api_key', '');
        $this->defaultCollection = config('services.qdrant.default_collection', 'documents');
        $this->embeddingService = $embeddingService;
        $this->vectorSize = $embeddingService->getEmbeddingSize();
    }

    /**
     * Get HTTP client for Qdrant API
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function client()
    {
        $client = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
            
        if (!empty($this->apiKey)) {
            $client = $client->withHeaders([
                'api-key' => $this->apiKey,
            ]);
        }
        
        return $client;
    }

    /**
     * Create a new collection
     *
     * @param string $collectionName Collection name
     * @param array $options Collection options
     * @return bool Success
     */
    public function createCollection(string $collectionName, array $options = []): bool
    {
        try {
            $vectorSize = $options['vector_size'] ?? $this->vectorSize;
            $distance = $options['distance'] ?? 'Cosine';
            $onDisk = $options['on_disk'] ?? false;
            $shardNumber = $options['shard_number'] ?? null;
            $replicationFactor = $options['replication_factor'] ?? null;
            
            $payload = [
                'vectors' => [
                    'size' => $vectorSize,
                    'distance' => $distance,
                    'on_disk' => $onDisk,
                ],
            ];
            
            if ($shardNumber !== null) {
                $payload['shard_number'] = $shardNumber;
            }
            
            if ($replicationFactor !== null) {
                $payload['replication_factor'] = $replicationFactor;
            }
            
            // Define payload for optimizers if provided in options
            if (isset($options['optimizers'])) {
                $payload['optimizers_config'] = $options['optimizers'];
            }
            
            // Define payload for vector index if provided in options
            if (isset($options['hnsw_config'])) {
                $payload['hnsw_config'] = $options['hnsw_config'];
            }
            
            $response = $this->client()->put("{$this->baseUrl}/collections/{$collectionName}", $payload);
            
            if ($response->successful()) {
                // Check if we need to create any indexes
                if (isset($options['indexes']) && is_array($options['indexes'])) {
                    foreach ($options['indexes'] as $field) {
                        $this->createPayloadIndex($collectionName, $field);
                    }
                }
                
                return true;
            } else {
                Log::error('Failed to create Qdrant collection', [
                    'collection' => $collectionName,
                    'response' => $response->json(),
                    'status' => $response->status(),
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception creating Qdrant collection', [
                'collection' => $collectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Delete a collection
     *
     * @param string $collectionName Collection name
     * @return bool Success
     */
    public function deleteCollection(string $collectionName): bool
    {
        try {
            $response = $this->client()->delete("{$this->baseUrl}/collections/{$collectionName}");
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception deleting Qdrant collection', [
                'collection' => $collectionName,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Check if a collection exists
     *
     * @param string $collectionName Collection name
     * @return bool Exists
     */
    public function collectionExists(string $collectionName): bool
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/collections/{$collectionName}");
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Create a payload index for filtering
     *
     * @param string $collectionName Collection name
     * @param string $field Field name to index
     * @param string $fieldType Type of field ('keyword' or 'integer')
     * @return bool Success
     */
    public function createPayloadIndex(string $collectionName, string $field, string $fieldType = 'keyword'): bool
    {
        try {
            $payload = [
                'field_name' => $field,
                'field_schema' => $fieldType,
            ];
            
            $response = $this->client()->put(
                "{$this->baseUrl}/collections/{$collectionName}/index", 
                $payload
            );
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception creating Qdrant payload index', [
                'collection' => $collectionName,
                'field' => $field,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Upsert vectors into a collection
     *
     * @param string|int $documentId Document ID
     * @param array $chunks Document chunks with text and embeddings
     * @param array $metadata Additional metadata to store
     * @param string|null $collectionName Collection name (default from config if null)
     * @return bool Success
     */
    public function upsertVectors($documentId, array $chunks, array $metadata = [], ?string $collectionName = null): bool
    {
        $collectionName = $collectionName ?? $this->defaultCollection;
        
        // Ensure collection exists
        if (!$this->collectionExists($collectionName)) {
            if (!$this->createCollection($collectionName)) {
                return false;
            }
        }
        
        try {
            $points = [];
            
            foreach ($chunks as $index => $chunk) {
                if (empty($chunk['embedding'])) {
                    continue;
                }
                
                // Create a unique ID for this chunk
                $chunkId = "{$documentId}_{$index}";
                
                // Prepare the point data
                $point = [
                    'id' => $chunkId,
                    'vector' => $chunk['embedding'],
                    'payload' => array_merge([
                        'document_id' => $documentId,
                        'chunk_index' => $index,
                        'text' => $chunk['text'],
                    ], $metadata),
                ];
                
                $points[] = $point;
            }
            
            if (empty($points)) {
                Log::warning('No valid points to upsert into Qdrant', [
                    'document_id' => $documentId,
                    'collection' => $collectionName,
                ]);
                
                return false;
            }
            
            // Batch upsert in groups of 100 to avoid too large requests
            $batches = array_chunk($points, 100);
            
            foreach ($batches as $batch) {
                $response = $this->client()->put(
                    "{$this->baseUrl}/collections/{$collectionName}/points", 
                    ['points' => $batch]
                );
                
                if (!$response->successful()) {
                    Log::error('Failed to upsert vectors to Qdrant', [
                        'document_id' => $documentId,
                        'collection' => $collectionName,
                        'response' => $response->json(),
                        'status' => $response->status(),
                    ]);
                    
                    return false;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Exception upserting vectors to Qdrant', [
                'document_id' => $documentId,
                'collection' => $collectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Search for similar vectors
     *
     * @param string $query Query text
     * @param array $options Search options
     * @return array Search results
     */
    public function search(string $query, array $options = []): array
    {
        $collectionName = $options['collection'] ?? $this->defaultCollection;
        $limit = $options['limit'] ?? 5;
        $filters = $options['filters'] ?? null;
        $withPayload = $options['with_payload'] ?? true;
        
        try {
            // Generate embedding for the query
            $embedding = $this->embeddingService->generateEmbedding($query);
            
            if (empty($embedding)) {
                return [
                    'success' => false,
                    'error' => 'Failed to generate embedding for query',
                    'results' => [],
                ];
                if (empty($embedding)) {
                    return [
                        'success' => false,
                        'error' => 'Failed to generate embedding for query',
                        'results' => [],
                    ];
                }
                
                $payload = [
                    'vector' => $embedding,
                    'limit' => $limit,
                    'with_payload' => $withPayload,
                ];
                
                // Add filter if provided
                if ($filters) {
                    $payload['filter'] = $filters;
                }
                
                $response = $this->client()->post(
                    "{$this->baseUrl}/collections/{$collectionName}/points/search", 
                    $payload
                );
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Format results for easier consumption
                    $results = [];
                    
                    foreach ($data['result'] as $item) {
                        $results[] = [
                            'id' => $item['id'],
                            'score' => $item['score'],
                            'payload' => $item['payload'] ?? [],
                            'text' => $item['payload']['text'] ?? '',
                            'document_id' => $item['payload']['document_id'] ?? null,
                            'metadata' => array_diff_key($item['payload'] ?? [], ['text' => 1]),
                        ];
                    }
                    
                    return [
                        'success' => true,
                        'results' => $results,
                    ];
                } else {
                    Log::error('Failed to search vectors in Qdrant', [
                        'collection' => $collectionName,
                        'response' => $response->json(),
                        'status' => $response->status(),
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => 'Failed to search vectors: ' . ($response->json()['status']['error'] ?? 'Unknown error'),
                        'results' => [],
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Exception searching vectors in Qdrant', [
                    'collection' => $collectionName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Exception searching vectors: ' . $e->getMessage(),
                    'results' => [],
                ];
            }
        }
        
        /**
         * Delete vectors for a document
         *
         * @param string|int $documentId Document ID
         * @param string|null $collectionName Collection name
         * @return bool Success
         */
        public function deleteDocumentVectors($documentId, ?string $collectionName = null): bool
        {
            $collectionName = $collectionName ?? $this->defaultCollection;
            
            try {
                $filter = [
                    'must' => [
                        [
                            'key' => 'document_id',
                            'match' => [
                                'value' => $documentId,
                            ],
                        ],
                    ],
                ];
                
                $payload = [
                    'filter' => $filter,
                ];
                
                $response = $this->client()->post(
                    "{$this->baseUrl}/collections/{$collectionName}/points/delete", 
                    $payload
                );
                
                if ($response->successful()) {
                    return true;
                } else {
                    Log::error('Failed to delete document vectors from Qdrant', [
                        'document_id' => $documentId,
                        'collection' => $collectionName,
                        'response' => $response->json(),
                        'status' => $response->status(),
                    ]);
                    
                    return false;
                }
            } catch (\Exception $e) {
                Log::error('Exception deleting document vectors from Qdrant', [
                    'document_id' => $documentId,
                    'collection' => $collectionName,
                    'error' => $e->getMessage(),
                ]);
                
                return false;
            }
        }
        
        /**
         * Get collection info
         *
         * @param string $collectionName Collection name
         * @return array|null Collection info or null on failure
         */
        public function getCollectionInfo(string $collectionName): ?array
        {
            try {
                $response = $this->client()->get("{$this->baseUrl}/collections/{$collectionName}");
                
                if ($response->successful()) {
                    return $response->json()['result'];
                }
                
                return null;
            } catch (\Exception $e) {
                Log::error('Exception getting Qdrant collection info', [
                    'collection' => $collectionName,
                    'error' => $e->getMessage(),
                ]);
                
                return null;
            }
        }
        
        /**
         * List all collections
         *
         * @return array List of collections
         */
        public function listCollections(): array
        {
            try {
                $response = $this->client()->get("{$this->baseUrl}/collections");
                
                if ($response->successful()) {
                    return $response->json()['result']['collections'] ?? [];
                }
                
                return [];
            } catch (\Exception $e) {
                Log::error('Exception listing Qdrant collections', [
                    'error' => $e->getMessage(),
                ]);
                
                return [];
            }
        }
    }
}    