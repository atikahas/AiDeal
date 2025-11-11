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
        Schema::create('video_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tool'); // e.g., 'video-generation', 'video-storyboard', etc.
            $table->json('input_json')->nullable();
            $table->string('reference_image_path')->nullable();
            $table->json('generated_videos')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->boolean('is_saved')->default(false);
            $table->text('error_message')->nullable();
            $table->string('operation_name')->nullable(); // Veo operation ID for polling
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_jobs');
    }
};
