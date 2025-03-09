<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class UserProviderApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'api_key'
    ];

    protected $hidden = [
        'api_key'
    ];

    /**
     * Automatically encrypt the API key when it's set
     */
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    /**
     * Automatically decrypt the API key when it's accessed
     */
    public function getApiKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
