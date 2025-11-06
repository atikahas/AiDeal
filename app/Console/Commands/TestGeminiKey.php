<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestGeminiKey extends Command
{
    protected $signature = 'gemini:test-key';
    protected $description = 'Test the Gemini API key and connection';

    public function handle()
    {
        // Get the API key from the database
        $apiKey = ApiKey::where('provider', 'gemini')
            ->where('is_active', true)
            ->orderBy('user_id', 'desc')
            ->first();

        if (!$apiKey) {
            $this->error('No active Gemini API key found in the database.');
            $this->info('Please add an API key using the settings interface.');
            return 1;
        }

        $this->info("Found Gemini API key with ID: " . $apiKey->id);
        $this->info("Key (first 5 chars): " . substr($apiKey->secret, 0, 5) . '...');
        $this->line('');

        // Test the connection
        $this->info('Testing connection to Gemini API...');
        
        $geminiService = new GeminiService($apiKey->secret);
        
        if ($geminiService->testConnection()) {
            $this->info('✅ Successfully connected to Gemini API!');
            
            // Test a simple prompt
            $this->info('\nTesting content generation with a simple prompt...');
            $response = $geminiService->generateContent('Hello, Gemini! Say something short.');
            
            if ($response) {
                $this->info('✅ Content generation successful!');
                $this->line('Response: ' . $response);
            } else {
                $this->warn('⚠️ Content generation failed. Check the logs for details.');
                $this->checkLogs();
            }
            
            return 0;
        } else {
            $this->error('❌ Failed to connect to Gemini API');
            $this->checkLogs();
            
            // Additional debug info
            $this->line('\nDebug Information:');
            $this->line('- API Key length: ' . strlen($apiKey->secret) . ' characters');
            $this->line('- Base URL: ' . config('services.gemini.base_url'));
            $this->line('- Model: ' . config('services.gemini.model'));
            
            return 1;
        }
    }
    
    protected function checkLogs(): void
    {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $log = file_get_contents($logFile);
            if (str_contains($log, 'GeminiService')) {
                $this->line('\nRecent Gemini-related logs:');
                $lines = explode("\n", $log);
                $geminiLines = array_filter($lines, fn($line) => str_contains($line, 'GeminiService'));
                $recentLines = array_slice($geminiLines, -5); // Show last 5 relevant log entries
                foreach ($recentLines as $line) {
                    $this->line($line);
                }
            }
        }
    }
}
