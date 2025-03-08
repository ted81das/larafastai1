<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_dynamic_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('model')->default('gpt-4o'); // LLM model to use
            $table->text('system_prompt')->nullable(); // System prompt for the agent
            $table->json('tools')->nullable(); // Available tools
            $table->json('memory_config')->nullable(); // Memory configuration
            $table->json('rag_config')->nullable(); // RAG configuration
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_dynamic_configs');
    }
};
