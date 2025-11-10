<?php

namespace App\Services\Ai;

use App\Models\ApiKey;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImagenClient
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;
    protected int $timeout;

    public function __construct(?string $apiKey = null)
    {
        $this->baseUrl = rtrim(Config::get('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->defaultModel = Config::get('services.gemini.imagen_default_model', 'imagen-4.0-generate-001');
        $this->timeout = (int) Config::get('services.gemini.image_timeout', 90);
        $this->apiKey = $apiKey ?? $this->resolveApiKey();
    }

    public function textToImage(array $options): array
    {
        $payload = $this->buildPredictPayload($options);

        return $this->send(
            $this->resolveModel($options) . ':predict',
            $payload
        );
    }

    public function editImage(array $options, UploadedFile $image): array
    {
        $payload = $this->buildPredictPayload(
            $options,
            ['image' => $this->makeImagePayload($image)]
        );

        return $this->send(
            $this->resolveModel($options) . ':predict',
            $payload
        );
    }

    public function imageVariations(array $options, UploadedFile $image): array
    {
        $instance = ['image' => $this->makeImagePayload($image)];

        if (!empty($options['prompt'])) {
            $instance['prompt'] = $this->composePrompt($options);
        }

        $payload = $this->buildPredictPayload($options, $instance);

        return $this->send(
            $this->resolveModel($options) . ':predict',
            $payload
        );
    }

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

    protected function buildPredictPayload(array $options, array $instanceOverrides = []): array
    {
        $count = max(1, min(5, (int) ($options['image_count'] ?? 1)));

        $instance = array_merge([
            'prompt' => $this->composePrompt($options),
        ], $instanceOverrides);

        if (!empty($options['negative_prompt'])) {
            $instance['negativePrompt'] = $options['negative_prompt'];
        }

        $parameters = $this->sanitize([
            'sampleCount' => $count,
            'aspectRatio' => $options['aspect_ratio'] ?? '1:1',
        ]);

        return [
            'instances' => [$instance],
            'parameters' => $parameters,
        ];
    }

    protected function composePrompt(array $options): string
    {
        $segments = [trim((string) ($options['prompt'] ?? ''))];

        $details = array_filter([
            $options['style'] ?? null ? 'Style: ' . $options['style'] : null,
            $options['lighting'] ?? null ? 'Lighting: ' . $options['lighting'] : null,
            $options['angle'] ?? null ? 'Angle: ' . $options['angle'] : null,
            $options['composition'] ?? null ? 'Composition: ' . $options['composition'] : null,
            $options['lensType'] ?? null ? 'Lens: ' . $options['lensType'] : null,
            $options['filmSimulation'] ?? null ? 'Film: ' . $options['filmSimulation'] : null,
        ]);

        if (!empty($details)) {
            $segments[] = implode(', ', $details);
        }

        if (!empty($options['aspect_ratio'])) {
            $segments[] = 'Aspect Ratio: ' . $options['aspect_ratio'];
        }

        return trim(implode('. ', array_filter($segments)));
    }

    protected function makeImagePayload(UploadedFile $image): array
    {
        $data = base64_encode(file_get_contents($image->getRealPath()));
        $mime = $image->getMimeType() ?: 'image/png';

        return [
            'bytesBase64Encoded' => $data,
            'mimeType' => $mime,
        ];
    }

    protected function resolveModel(array $options): string
    {
        $model = $options['model'] ?? $this->defaultModel;

        return trim($model) !== '' ? $model : $this->defaultModel;
    }

    protected function ensureApiKey(): void
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('No active Gemini API key is configured.');
        }
    }

    protected function send(string $endpoint, array $payload): array
    {
        $this->ensureApiKey();

        $url = sprintf(
            '%s/models/%s?key=%s',
            $this->baseUrl,
            ltrim($endpoint, '/'),
            $this->apiKey
        );

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $this->sanitize($payload));

        if ($response->failed()) {
            Log::error('Imagen API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ]);

            $message = $response->json('error.message') ?? 'Unable to generate images with Imagen.';
            throw new \RuntimeException($message);
        }

        return $this->normalizeResponse($response->json());
    }

    protected function normalizeResponse(?array $response): array
    {
        if (!is_array($response)) {
            return [];
        }

        $images = [];

        if (!empty($response['predictions'])) {
            foreach ($response['predictions'] as $prediction) {
                $encoded = $prediction['bytesBase64Encoded']
                    ?? $prediction['bytes_base64_encoded']
                    ?? data_get($prediction, 'image.inlineData.data');

                if ($encoded) {
                    $images[] = [
                        'data' => $encoded,
                        'mime' => $prediction['mimeType'] ?? 'image/png',
                    ];
                }
            }
        }

        if (!empty($response['images'])) {
            foreach ($response['images'] as $image) {
                $encoded = $image['image']
                    ?? $image['imageBytes']
                    ?? $image['image_bytes']
                    ?? data_get($image, 'inlineData.data')
                    ?? data_get($image, 'inline_data.data');

                if ($encoded) {
                    $images[] = [
                        'data' => $encoded,
                        'mime' => $image['mimeType'] ?? 'image/png',
                    ];
                }
            }
        }

        if (empty($images) && !empty($response['candidates'])) {
            foreach ($response['candidates'] as $candidate) {
                $parts = $candidate['content']['parts'] ?? [];
                foreach ($parts as $part) {
                    $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;

                    if ($inline && !empty($inline['data'])) {
                        $images[] = [
                            'data' => $inline['data'],
                            'mime' => $inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png',
                        ];
                    }
                }
            }
        }

        return $images;
    }

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
