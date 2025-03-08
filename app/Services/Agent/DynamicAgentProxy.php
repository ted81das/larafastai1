<?php 
namespace App\Services\Agent;

use App\Models\AgentDynamicConfig;
use App\Services\LLM\PrismAdapter;
use App\Services\RAG\LlphantService;
use Illuminate\Support\Facades\App;
use OpenAI\Client;

class DynamicAgentProxy
{
    protected $openai;
    protected $prism;
    protected $llphantService;
    protected $modelRegistry;
    
    public function __construct(
        Client $openai,
        PrismAdapter $prism,
        LlphantService $llphantService,
        ModelRegistry $modelRegistry
    ) {
        $this->openai = $openai;
        $this->prism = $prism;
        $this->llphantService = $llphantService;
        $this->modelRegistry = $modelRegistry;
    }
    
    /**
     * Execute an agent with the provided messages
     */
    public function executeAgent(AgentDynamicConfig $agent, array $messages, bool $stream = false): array
    {
        // Get the model provider
        $provider = $this->modelRegistry->getProviderForModel($agent->model);
        
        // Prepare request parameters
        $params = [
            'model' => $agent->model,
            'messages' => $messages,
            'temperature' => $agent->temperature,
        ];
        
        // Add tools configuration if present
        $tools = $agent->getToolsConfig();
        if (!empty($tools)) {
            $params['tools'] = $tools;
            $params['tool_choice'] = 'auto';
        }
        
        // Execute based on provider
        if ($provider === 'openai') {
            return $this->executeWithOpenAI($params, $stream);
        } elseif ($provider === 'prism') {
            return $this->executeWithPrism($params, $stream);
        } else {
            throw new \Exception("Unsupported model provider: {$provider}");
        }
    }
    
    /**
     * Execute with OpenAI
     */
    protected function executeWithOpenAI(array $params, bool $stream): array
    {
        if ($stream) {
            return $this->handleStream(
                $this->openai->chat->completions->create($params + ['stream' => true])
            );
        }
        
        $response = $this->openai->chat->completions->create($params);
        return $this->formatResponse($response);
    }
    
    /**
     * Execute with Prism
     */
    protected function executeWithPrism(array $params, bool $stream): array
    {
        if ($stream) {
            return $this->handleStream(
                $this->prism->chatCompletion($params['messages'], $params['model'], $params['temperature'], $params['tools'] ?? [], true)
            );
        }
        
        $response = $this->prism->chatCompletion(
            $params['messages'],
            $params['model'],
            $params['temperature'],
            $params['tools'] ?? []
        );
        
        return $this->formatResponse($response);
    }
    
    /**
     * Format the API response to a standard format
     */
    protected function formatResponse($response): array
    {
        // Get the message from the response
        $message = $response->choices[0]->message;
        
        $result = [
            'content' => $message->content,
            'role' => $message->role,
            'model' => $response->model,
        ];
        
        // Add tool calls if present
        if (isset($message->tool_calls) && !empty($message->tool_calls)) {
            $result['tool_calls'] = $message->tool_calls;
        }
        
        return $result;
    }
    
    /**
     * Handle streaming response
     */
    protected function handleStream($stream): array
    {
        // Placeholder for working with streams in workflow context
        // In a real implementation, this would buffer the stream and return
        // the complete response after the stream ends
        throw new \Exception("Streaming is not supported in workflow nodes");
    }
    
    /**
     * Execute RAG retrieval for an agent
     */
    public function executeRetrieval(string $query, AgentDynamicConfig $agent): array
    {
        // Only proceed if RAG is enabled
        if (!$agent->rag_enabled) {
            return [];
        }
        
        // Create filter for this agent's team/user
        $filter = ['user_id' => $agent->user_id];
        if ($agent->team_id) {
            $filter['team_id'] = $agent->team_id;
        }
        
        // Get document limit from config or use default
        $limit = $agent->rag_config['max_documents'] ?? 5;
        
        // Retrieve relevant documents
        $documents = $this->llphantService->retrieveDocuments($query, $limit, $filter);
        
        // Format for context insertion
        $formattedContext = [];
        foreach ($documents as $doc) {
            $formattedContext[] = [
                'content' => $doc->pageContent,
                'source' => $doc->metadata['source'] ?? 'Unknown',
                'title' => $doc->metadata['title'] ?? 'Untitled',
            ];
        }
        
        return $formattedContext;
    }
    
    /**
     * Create a RAG-enhanced message from user query
     */
    public function createRagEnhancedMessage(string $query, array $retrievedDocs): string
    {
        // Create a context string from documents
        $contextText = '';
        foreach ($retrievedDocs as $index => $doc) {
            $contextText .= "DOCUMENT " . ($index + 1) . ":\n";
            $contextText .= "Title: " . $doc['title'] . "\n";
            $contextText .= "Source: " . $doc['source'] . "\n";
            $contextText .= "Content: " . $doc['content'] . "\n\n";
        }
        
        // Return formatted query with context
        if (empty($contextText)) {
            return $query;
        }
        
        return "I need information about the following: $query\n\n" .
               "Here is some context that might be helpful:\n\n" .
               $contextText .
               "Based on the above context, please answer: $query";
    }
}
