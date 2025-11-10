<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ListGeminiModels extends Command
{
    protected $signature = 'gemini:models {--api-key=}';
    protected $description = 'List available Gemini models and their capabilities';

    public function handle()
    {
        $apiKey = $this->option('api-key') ?: config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            $this->error('No API key provided. Use --api-key option or set GEMINI_API_KEY in .env');
            return 1;
        }

        $this->info('Fetching available models...');
        
        $response = Http::get('https://generativelanguage.googleapis.com/v1beta/models', [
            'key' => $apiKey
        ]);

        if ($response->failed()) {
            $this->error('Failed to fetch models: ' . $response->json('error.message', 'Unknown error'));
            return 1;
        }

        $models = $response->json('models', []);
        
        $this->info("\nAvailable Models:");
        $this->info(str_repeat('=', 80));
        
        $imageModels = [];
        $textModels = [];
        
        foreach ($models as $model) {
            $name = $model['name'] ?? 'Unknown';
            $displayName = $model['displayName'] ?? 'Unknown';
            $description = $model['description'] ?? 'No description';
            $supportedMethods = $model['supportedGenerationMethods'] ?? [];
            
            // Check if it supports image generation
            $supportsImageGeneration = false;
            $supportsImageInput = false;
            
            if (stripos($name, 'imagen') !== false || stripos($displayName, 'imagen') !== false) {
                $supportsImageGeneration = true;
            }
            
            if (stripos($description, 'image') !== false || stripos($displayName, 'image') !== false) {
                $supportsImageInput = true;
            }
            
            // Check supported methods
            $methods = implode(', ', $supportedMethods);
            
            if ($supportsImageGeneration || $supportsImageInput) {
                $imageModels[] = [
                    'name' => str_replace('models/', '', $name),
                    'display' => $displayName,
                    'methods' => $methods,
                    'description' => $description,
                ];
            } else {
                $textModels[] = [
                    'name' => str_replace('models/', '', $name),
                    'display' => $displayName,
                    'methods' => $methods,
                    'description' => $description,
                ];
            }
        }
        
        // Display image-related models first
        if (!empty($imageModels)) {
            $this->info("\n📷 Image Generation/Processing Models:");
            $this->table(
                ['Model Name', 'Display Name', 'Supported Methods'],
                array_map(function ($m) {
                    return [$m['name'], $m['display'], $m['methods']];
                }, $imageModels)
            );
            
            $this->info("\nDetailed Info for Image Models:");
            foreach ($imageModels as $model) {
                $this->info("\n• " . $model['name']);
                $this->info("  Display: " . $model['display']);
                $this->info("  Methods: " . $model['methods']);
                $this->info("  Description: " . substr($model['description'], 0, 100) . '...');
            }
        }
        
        // Test specific models
        $this->info("\n\nTesting specific models for image capabilities...");
        $testModels = [
            'imagen-3.0-generate-001',
            'imagen-4.0-generate-preview-06-06',
            'gemini-2.5-flash-image',
            'gemini-2.0-flash',
            'gemini-1.5-flash',
            'gemini-1.5-pro',
        ];
        
        foreach ($testModels as $modelName) {
            $this->testModel($modelName, $apiKey);
        }
        
        return 0;
    }
    
    private function testModel($modelName, $apiKey)
    {
        $this->info("\nTesting: " . $modelName);
        
        // Try to get model info
        $response = Http::get("https://generativelanguage.googleapis.com/v1beta/models/{$modelName}", [
            'key' => $apiKey
        ]);
        
        if ($response->successful()) {
            $model = $response->json();
            $methods = $model['supportedGenerationMethods'] ?? [];
            $this->info("✓ Available - Methods: " . implode(', ', $methods));
            
            // Check for specific capabilities
            if (in_array('generateContent', $methods)) {
                $this->info("  → Supports generateContent (multimodal input possible)");
            }
            if (in_array('generateImage', $methods)) {
                $this->info("  → Supports generateImage (image generation)");
            }
            if (in_array('predict', $methods)) {
                $this->info("  → Supports predict (Imagen-style generation)");
            }
        } else {
            $this->error("✗ Not available or accessible");
        }
    }
}