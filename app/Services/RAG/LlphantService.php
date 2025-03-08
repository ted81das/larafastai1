<?php
namespace App\Services\RAG;

use Illuminate\Support\Facades\Log;
use Llphant\Contracts\ChatModelInterface;
use Llphant\Contracts\VectorStoreInterface;
use Llphant\Formatting\ChatMessage;
use Llphant\Llphant;
use Llphant\Retrievers\VectorStoreRetriever;

class LlphantService
{
    protected $llphant;
    protected $vectorStore;
    protected $model;
    
    public function __construct(
        Llphant $llphant,
        VectorStoreInterface $vectorStore,
        ChatModelInterface $model
    ) {
        $this->llphant = $llphant;
        $this->vectorStore = $vectorStore;
        $this->model = $model;
    }
    
    /**
     * Generate a RAG-enhanced response to a user query
     */
    public function generateResponse(string $query, array $systemInstructions = [], int $maxResults = 5, float $threshold = 0.7): string
    {
        try {
            // Set up vector store retriever
            $retriever = new VectorStoreRetriever(
                $this->vectorStore,
                maxResults: $maxResults,
                threshold: $threshold
            );
            
            // Retrieve relevant documents
            $relevantDocs = $retriever->retrieve($query);
            
            // Create context from documents
            $context = '';
            
            foreach ($relevantDocs as $doc) {
                $context .= "Document: " . ($doc->metadata['title'] ?? 'Untitled') . "\n";
                $context .= "Content: {$doc->content}\n\n";
            }
            
            // Build system message with context
            $systemContent = "You are a helpful assistant with access to the following documents:\n\n";
            $systemContent .= $context;
            $systemContent .= "\nUse this information to answer the user's questions. If the answer cannot be found in the provided documents, say so clearly.";
            
            // Add custom instructions if provided
            if (!empty($systemInstructions)) {
                $systemContent .= "\n\nAdditional instructions:\n";
                $systemContent .= implode("\n", $systemInstructions);
            }
            
            // Create chat messages
            $messages = [
                new ChatMessage(role: 'system', content: $systemContent),
                new ChatMessage(role: 'user', content: $query),
            ];
            
            // Generate response
            $response = $this->model->generate($messages);
            
            // Return content
            return $response->content;
        } catch (\Exception $e) {
            Log::error('RAG query failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }    
    /**
     * Generate a streaming RAG-enhanced response to a user query
     */
    public function generateStreamingResponse(string $query, array $systemInstructions = [], int $maxResults = 5, float $threshold = 0.7)
    {
        try {
            // Set up vector store retriever
            $retriever = new VectorStoreRetriever(
                $this->vectorStore,
                maxResults: $maxResults,
                threshold: $threshold
            );
            
            // Retrieve relevant documents
            $relevantDocs = $retriever->retrieve($query);
            
            // Create context from documents
            $context = '';
            
            foreach ($relevantDocs as $doc) {
                $context .= "Document: " . ($doc->metadata['title'] ?? 'Untitled') . "\n";
                $context .= "Content: " . $doc->content . "\n\n";
            }
            
            // Build system message with context
            $systemContent = "You are a helpful assistant with access to the following documents:\n\n";
            $systemContent .= $context;
            $systemContent .= "\nUse this information to answer the user's questions. If the answer cannot be found in the provided documents, say so clearly.";
            
            // Add custom instructions if provided
            if (!empty($systemInstructions)) {
                $systemContent .= "\n\nAdditional instructions:\n";
                $systemContent .= implode("\n", $systemInstructions);
            }
            
            // Create chat messages
            $messages = [
                new ChatMessage(role: 'system', content: $systemContent),
                new ChatMessage(role: 'user', content: $query),
            ];
            
            // Generate streaming response
            return $this->model->generateStream($messages);
        } catch (\Exception $e) {
            Log::error('RAG streaming query failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }    
    /**
     * Get information about the sources used for the response
     */
    public function getSourcesForQuery(string $query, int $maxResults = 5, float $threshold = 0.7): array
    {
        // Set up vector store retriever
        $retriever = new VectorStoreRetriever(
            $this->vectorStore,
            maxResults: $maxResults,
            threshold: $threshold
        );
        
        // Retrieve relevant documents
        $relevantDocs = $retriever->retrieve($query);
        
        // Format source information
        $sources = [];
        
        foreach ($relevantDocs as $doc) {
            $sources[] = [
                'title' => $doc->metadata['title'] ?? 'Untitled',
                'file_name' => $doc->metadata['file_name'] ?? null,
                'document_id' => $doc->metadata['document_id'] ?? null,
                'similarity' => $doc->metadata['similarity'] ?? null,
                'created_at' => $doc->metadata['created_at'] ?? null,
            ];
        }
        
        return $sources;
    }
}
