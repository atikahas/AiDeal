<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestGeminiConnection extends Command
{
    protected $signature = 'gemini:test-connection';
    protected $description = 'Test the Gemini API connection';

    public function handle()
    {
        // Check if we have an active Gemini API key
        $apiKey = ApiKey::where('provider', 'gemini')
            ->where('is_active', true)
            ->orderBy('user_id', 'desc')
            ->first();

        if (!$apiKey) {
            $this->error('No active Gemini API key found.');
            return 1;
        }

        $this->info('Found Gemini API key: ' . substr($apiKey->secret, 0, 5) . '...');

        // Test the connection
        $geminiService = new GeminiService($apiKey->secret);
        
        $this->info('Testing connection to Gemini API...');
        
        if ($geminiService->testConnection()) {
            $this->info('✅ Successfully connected to Gemini API!');
            return 0;
        } else {
            $this->error('❌ Failed to connect to Gemini API. Please check your API key and internet connection.');
            
            // Check the last error from the logs
            $log = file_get_contents(storage_path('logs/laravel.log'));
            if (str_contains($log, 'Gemini API error')) {
                $this->line('Last error from log:');
                $this->line($log);
            }
            
            return 1;
        }
    }
}
