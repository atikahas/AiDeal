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
        Schema::create('ai_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('activity_type');
            $table->string('model')->nullable();
            $table->text('prompt')->nullable();
            $table->longText('output')->nullable();
            $table->unsignedInteger('token_count')->default(0);
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('latency_ms')->default(0);
            $table->unsignedInteger('cost_cents')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('activity_type');
            $table->index('model');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_activity_logs');
    }
};
