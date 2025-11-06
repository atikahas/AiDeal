<?php

namespace App\Services;

use App\Models\ApiKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class GeminiService
{
    protected ?string $apiKey = null;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    protected string $model = 'gemini-2.5-flash';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $this->getDefaultApiKey();
        $this->baseUrl = Config::get('services.gemini.base_url', $this->baseUrl);
        $this->model = Config::get('services.gemini.model', $this->model);
        
        $this->logDebug('GeminiService initialized', [
            'base_url' => $this->baseUrl,
            'model' => $this->model,
            'has_api_key' => !empty($this->apiKey),
        ]);
    }
    
    protected function logDebug(string $message, array $context = []): void
    {
        if (Config::get('app.debug')) {
            Log::debug("GeminiService: $message", $context);
        }
    }
    
    protected function logError(string $message, array $context = []): void
    {
        Log::error("GeminiService: $message", $context);
    }

    protected function getDefaultApiKey(): ?string
    {
        $apiKey = ApiKey::where('provider', 'gemini')
            ->where('is_active', true)
            ->orderBy('user_id', 'desc') // User-specific keys first
            ->value('secret');

        return $apiKey;
    }

    public function generateContent(string $prompt, array $options = []): ?string
    {
        if (!$this->apiKey) {
            $this->logError('No API key provided for content generation');
            return null;
        }

        try {
            $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";
            
            // Add language instruction to the beginning of the prompt
            $language = $options['language'] ?? 'English';
            $systemInstruction = [
                'role' => 'user',
                'parts' => [
                    ['text' => "You are a helpful assistant that responds in {$language}. " .
                              "IMPORTANT: You MUST respond in {$language} language. " .
                              "Do not include any English text in your response unless it's a proper noun or technical term that doesn't have a direct translation."]
                ]
            ];
            
            $payload = [
                'contents' => [
                    $systemInstruction,
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? 0.7,
                    'topK' => $options['top_k'] ?? 40,
                    'topP' => $options['top_p'] ?? 0.95,
                    'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                ],
            ];
            
            // Add safety settings to avoid content filtering issues
            $payload['safetySettings'] = [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_NONE'
                ]
            ];

            $this->logDebug('Sending request to Gemini API', [
                'url' => $url,
                'payload' => $payload,
            ]);
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                $this->logDebug('Gemini API response', $result);
                
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return $result['candidates'][0]['content']['parts'][0]['text'];
                }
                
                $this->logError('Unexpected response format from Gemini API', $result);
                return null;
            }

            $this->logError('Gemini API error', [
                'status' => $response->status(),
                'response' => $response->json(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logError('Gemini API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    public function testConnection(): bool
    {
        if (!$this->apiKey) {
            Log::error('No API key provided for Gemini connection test');
            return false;
        }

        try {
            // First, list available models to check connectivity
            $url = "{$this->baseUrl}/models?key={$this->apiKey}";
            $this->logDebug('Listing available models from Gemini API', ['url' => $url]);
            
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->get($url);

            $success = $response->successful();
            
            if ($success) {
                $models = $response->json();
                $this->logDebug('Successfully connected to Gemini API', [
                    'available_models' => array_map(fn($m) => $m['name'] ?? $m, $models['models'] ?? [])
                ]);
                return true;
            } else {
                $this->logError('Gemini API connection failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->logError('Gemini API connection exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
