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
        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('node_type');
            $table->json('config')->nullable();
            $table->json('position')->nullable();
            $table->string('node_id')->unique(); // Unique identifier for frontend
            $table->json('connections')->nullable(); // Store connections to other nodes
            $table->integer('order')->default(0); // For sequential execution
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_nodes');
    }
};
