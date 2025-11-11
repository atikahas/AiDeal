<?php

namespace App\Services\Ai;

use App\Models\ApiKey;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VeoClient
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;
    protected int $timeout;
    protected int $maxPollingAttempts = 60; // 5 minutes max (5s interval)
    protected int $pollingIntervalSeconds = 5;

    public function __construct(?string $apiKey = null)
    {
        $this->baseUrl = rtrim(Config::get('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->defaultModel = Config::get('services.gemini.veo_model', 'veo-3.1-generate-preview');
        $this->timeout = (int) Config::get('services.gemini.video_timeout', 120);
        $this->apiKey = $apiKey ?? $this->resolveApiKey();
    }

    /**
     * Generate video from text prompt
     */
    public function textToVideo(array $options): array
    {
        $model = $this->resolveModel($options);
        $payload = $this->buildPayload($options);

        // Start the long-running operation
        $operation = $this->startOperation($model . ':predictLongRunning', $payload);

        // Poll for completion
        $result = $this->pollOperation($operation['name']);

        // Download and return video
        return $this->extractVideos($result);
    }

    /**
     * Generate video from text prompt with reference image
     */
    public function imageToVideo(array $options, UploadedFile $image): array
    {
        $options['referenceImage'] = $image;
        return $this->textToVideo($options);
    }

    /**
     * Start a long-running video generation operation
     */
    protected function startOperation(string $endpoint, array $payload): array
    {
        $this->ensureApiKey();

        $url = sprintf(
            '%s/models/%s',
            $this->baseUrl,
            ltrim($endpoint, '/')
        );

        Log::info('Veo API request', [
            'endpoint' => $endpoint,
            'payload_keys' => array_keys($payload),
        ]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('Veo API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ]);

            $message = $response->json('error.message') ?? 'Unable to generate video with Veo.';
            throw new \RuntimeException($message);
        }

        $data = $response->json();

        if (empty($data['name'])) {
            throw new \RuntimeException('No operation name returned from Veo API');
        }

        return $data;
    }

    /**
     * Poll operation until completion
     */
    protected function pollOperation(string $operationName): array
    {
        $attempts = 0;

        while ($attempts < $this->maxPollingAttempts) {
            $url = sprintf(
                '%s/%s',
                $this->baseUrl,
                ltrim($operationName, '/')
            );

            $response = Http::timeout(30)
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->get($url);

            if ($response->failed()) {
                Log::error('Veo operation polling failed', [
                    'operation' => $operationName,
                    'status' => $response->status(),
                    'body' => $response->json() ?: $response->body(),
                ]);
                throw new \RuntimeException('Failed to poll video generation operation');
            }

            $data = $response->json();

            if (!empty($data['done'])) {
                Log::info('Veo operation completed', [
                    'operation' => $operationName,
                    'attempts' => $attempts + 1,
                ]);
                return $data;
            }

            if (isset($data['error'])) {
                Log::error('Veo operation failed', [
                    'operation' => $operationName,
                    'error' => $data['error'],
                ]);
                throw new \RuntimeException($data['error']['message'] ?? 'Video generation failed');
            }

            $attempts++;
            sleep($this->pollingIntervalSeconds);
        }

        throw new \RuntimeException('Video generation timeout - operation did not complete in time');
    }

    /**
     * Extract video URLs from operation result
     */
    protected function extractVideos(array $result): array
    {
        $videos = [];

        Log::info('Extracting videos from result', [
            'has_response' => isset($result['response']),
            'response_keys' => isset($result['response']) ? array_keys($result['response']) : [],
        ]);

        // Navigate through the response structure
        $samples = $result['response']['generateVideoResponse']['generatedSamples'] ?? [];

        Log::info('Found samples', ['count' => count($samples)]);

        foreach ($samples as $index => $sample) {
            $videoUri = $sample['video']['uri'] ?? null;

            if ($videoUri) {
                // Download the video
                $videoData = $this->downloadVideo($videoUri);

                $videos[] = [
                    'data' => $videoData,
                    'uri' => $videoUri,
                    'mime' => 'video/mp4',
                ];

                Log::info('Video added to array', [
                    'index' => $index,
                    'data_length' => strlen($videoData),
                    'has_data' => !empty($videoData),
                ]);
            }
        }

        if (empty($videos)) {
            Log::warning('No videos found in Veo response', ['response' => $result]);
            throw new \RuntimeException('No videos were generated');
        }

        Log::info('Total videos extracted', ['count' => count($videos)]);

        return $videos;
    }

    /**
     * Download video from URI
     */
    protected function downloadVideo(string $uri): string
    {
        Log::info('Attempting to download video', ['uri' => $uri]);

        // Try with API key in URL parameter
        $downloadUrl = $uri . (str_contains($uri, '?') ? '&' : '?') . 'key=' . $this->apiKey;

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->get($downloadUrl);

            if ($response->failed()) {
                Log::error('Failed to download generated video', [
                    'uri' => $uri,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers(),
                ]);
                throw new \RuntimeException('Failed to download generated video: HTTP ' . $response->status());
            }

            $body = $response->body();
            Log::info('Video downloaded successfully', [
                'size' => strlen($body),
                'content_type' => $response->header('Content-Type'),
            ]);

            return base64_encode($body);
        } catch (\Exception $e) {
            Log::error('Exception during video download', [
                'uri' => $uri,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to download generated video: ' . $e->getMessage());
        }
    }

    /**
     * Build payload for video generation
     */
    protected function buildPayload(array $options): array
    {
        $instance = [
            'prompt' => $options['prompt'] ?? '',
        ];

        // Add reference image if provided
        if (!empty($options['referenceImage']) && $options['referenceImage'] instanceof UploadedFile) {
            $instance['image'] = $this->makeImagePayload($options['referenceImage']);
        }

        $parameters = [
            'aspectRatio' => $options['aspectRatio'] ?? '16:9',
            'durationSeconds' => (int) ($options['duration'] ?? 8),
        ];

        // Add negative prompt if provided
        if (!empty($options['negativePrompt'])) {
            $parameters['negativePrompt'] = $options['negativePrompt'];
        }

        // Add resolution if specified
        if (!empty($options['resolution'])) {
            $parameters['resolution'] = $options['resolution'];
        }

        return $this->sanitize([
            'instances' => [$instance],
            'parameters' => $parameters,
        ]);
    }

    /**
     * Convert image to base64 payload
     */
    protected function makeImagePayload(UploadedFile $image): array
    {
        $data = base64_encode(file_get_contents($image->getRealPath()));
        $mime = $image->getMimeType() ?: 'image/png';

        return [
            'bytesBase64Encoded' => $data,
            'mimeType' => $mime,
        ];
    }

    /**
     * Resolve API key from user or system
     */
    protected function resolveApiKey(): ?string
    {
        $user = Auth::user();

        if ($user && $user->relationLoaded('activeApiKey')) {
            $apiKey = $user->activeApiKey?->secret;
            if (!empty($apiKey)) {
                return $apiKey;
            }
        } elseif ($user && $user->activeApiKey?->secret) {
            return $user->activeApiKey->secret;
        }

        return ApiKey::whereNull('user_id')
            ->where('provider', 'gemini')
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->value('secret');
    }

    /**
     * Resolve model name
     */
    protected function resolveModel(array $options): string
    {
        $model = $options['model'] ?? $this->defaultModel;
        return trim($model) !== '' ? $model : $this->defaultModel;
    }

    /**
     * Ensure API key is available
     */
    protected function ensureApiKey(): void
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('No active Gemini API key is configured.');
        }
    }

    /**
     * Remove null and empty values from payload
     */
    protected function sanitize(array $payload): array
    {
        return collect($payload)
            ->reject(function ($value) {
                if (is_array($value)) {
                    return empty($value);
                }

                return $value === null || $value === '';
            })
            ->all();
    }
}
