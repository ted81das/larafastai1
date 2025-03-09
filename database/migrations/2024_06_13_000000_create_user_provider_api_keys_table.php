<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_provider_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider');
            $table->string('model')->nullable(); // Allowing null for provider-wide keys
            $table->text('api_key');
            $table->timestamps();
            
            // Index for efficient lookups
            $table->index(['user_id', 'provider', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_provider_api_keys');
    }
};
