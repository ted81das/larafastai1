<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class CustomOpenAIEndpoint extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'base_url',
        'api_key',
        'models_endpoint',
        'context_window',
        'prompt_price_per_1k_tokens',
        'completion_price_per_1k_tokens',
        'max_tokens',
        'is_active'
    ];

    protected $hidden = [
        'api_key'
    ];

    protected $casts = [
        'context_window' => 'integer',
        'prompt_price_per_1k_tokens' => 'float',
        'completion_price_per_1k_tokens' => 'float',
        'max_tokens' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Encrypt API key when setting
     */
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt API key when getting
     */
    public function getApiKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Fetch available models from the endpoint
     */
    public function fetchAvailableModels(): array
    {
        try {
            $endpoint = rtrim($this->base_url, '/') . '/' . ltrim($this->models_endpoint, '/');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ])->get($endpoint);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            \Log::error('Failed to fetch models from custom OpenAI endpoint', [
                'endpoint_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
}
