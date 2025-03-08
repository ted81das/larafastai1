<?phpnamespace App\Providers;

use App\Services\LLM\OpenAIAdapter;
use App\Services\LLM\PrismAdapter;
use App\Services\RAG\DocumentProcessor;
use App\Services\RAG\EmbeddingService;
use App\Services\RAG\LlphantService;
use App\Services\RAG\VectorStoreService;
use Illuminate\Support\ServiceProvider;
use Llphant\Contracts\ChatModelInterface;
use Llphant\Contracts\VectorStoreInterface;
use Llphant\Llphant;
use Llphant\Models\OpenAIChatModel;
use Llphant\VectorStores\QdrantVectorStore;

class LlphantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register OpenAI Chat Model
        $this->app->bind(ChatModelInterface::class, function ($app) {
            $model = config('services.openai.chat_model', 'gpt-4o');
            $apiKey = config('services.openai.api_key');
            
            return new OpenAIChatModel($apiKey, $model);
        });

        // Register Qdrant Vector Store
        $this->app->bind(VectorStoreInterface::class, function ($app) {
            $url = config('services.qdrant.url', 'http://localhost:6333');
            $collection = config('services.qdrant.collection', 'documents');
            
            return new QdrantVectorStore($url, $collection);
        });

        // Register Llphant
        $this->app->singleton(Llphant::class, function ($app) {
            return new Llphant(
                $app->make(ChatModelInterface::class)
            );
        });

        // Register RAG Services
        $this->app->singleton(EmbeddingService::class, function ($app) {
            return new EmbeddingService(
                $app->make(OpenAIAdapter::class),
                $app->make(PrismAdapter::class),
                config('services.embeddings.provider', 'openai'),
                config('services.embeddings.model', 'text-embedding-3-small')
            );
        });

        $this->app->singleton(VectorStoreService::class, function ($app) {
            return new VectorStoreService(
                config('services.qdrant.url'),
                config('services.qdrant.collection'),
                config('services.qdrant.api_key')
            );
        });

        $this->app->singleton(LlphantService::class, function ($app) {
            return new LlphantService(
                $app->make(Llphant::class),
                $app->make(VectorStoreInterface::class),
                $app->make(ChatModelInterface::class)
            );
        });

        $this->app->singleton(DocumentProcessor::class, function ($app) {
            return new DocumentProcessor(
                $app->make(EmbeddingService::class),
                $app->make(VectorStoreService::class),
                config('services.rag.chunk_size', 1000),
                config('services.rag.chunk_overlap', 200)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 
    }
}
