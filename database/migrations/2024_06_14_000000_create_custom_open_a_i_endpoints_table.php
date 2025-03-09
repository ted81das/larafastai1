<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_open_a_i_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('base_url');
            $table->text('api_key');
            $table->string('models_endpoint')->default('v1/models');
            $table->integer('context_window')->default(4096);
            $table->float('prompt_price_per_1k_tokens')->default(0.001);
            $table->float('completion_price_per_1k_tokens')->default(0.002);
            $table->integer('max_tokens')->default(2048);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_open_a_i_endpoints');
    }
};
