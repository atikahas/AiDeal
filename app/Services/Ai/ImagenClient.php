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
        $this->defaultModel = Config::get('services.gemini.imagen_default_model', 'imagen-4.0-generate-preview-06-06');
        $this->timeout = (int) Config::get('services.gemini.image_timeout', 90);
        $this->apiKey = $apiKey ?? $this->resolveApiKey();
    }

    public function textToImage(array $options): array
    {
        $model = $this->resolveModel($options);
        
        // Check if this is a Gemini model (uses generateContent) or Imagen model (uses predict)
        if ($this->isGeminiModel($model)) {
            return $this->generateContentWithGemini($options);
        }
        
        // Imagen models use predict endpoint
        $payload = $this->buildPayload($options);
        
        return $this->send(
            $model . ':predict',
            $payload
        );
    }

    public function editImage(array $options, UploadedFile $image, ?UploadedFile $additionalImage = null): array
    {
        // Try using the generateContent endpoint with image(s)
        try {
            $images = [$image];
            if ($additionalImage) {
                $images[] = $additionalImage;
            }
            return $this->generateWithImages($options, $images);
        } catch (\Exception $e) {
            // Fallback to text-only generation
            Log::warning('Image-based generation failed, falling back to text-only', [
                'error' => $e->getMessage()
            ]);

            if (!empty($options['prompt'])) {
                $options['prompt'] = 'Create an image: ' . $options['prompt'];
            }

            return $this->textToImage($options);
        }
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

    protected function buildPayload(array $options, array $instanceOverrides = []): array
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
        $segments = [];

        // Add main prompt
        $mainPrompt = trim((string) ($options['prompt'] ?? ''));
        if (!empty($mainPrompt)) {
            $segments[] = $mainPrompt;
        }

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

        // Add aspect ratio instruction with strong emphasis
        if (!empty($options['aspect_ratio'])) {
            $aspectRatio = $options['aspect_ratio'];

            // Convert aspect ratio to dimensions description for better understanding
            $dimensionMap = [
                '1:1' => 'square format (1:1)',
                '4:3' => 'horizontal 4:3 aspect ratio',
                '16:9' => 'widescreen 16:9 aspect ratio',
                '9:16' => 'vertical portrait 9:16 aspect ratio',
                '3:2' => 'classic 3:2 aspect ratio',
                '3:4' => 'vertical portrait 3:4 aspect ratio',
            ];

            $description = $dimensionMap[$aspectRatio] ?? "aspect ratio {$aspectRatio}";
            $segments[] = "IMPORTANT: The output image MUST be in {$description}";
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
    
    protected function isGeminiModel(string $model): bool
    {
        return str_starts_with($model, 'gemini-');
    }
    
    protected function generateContentWithGemini(array $options, array $inputImages = []): array
    {
        $parts = [
            [
                'text' => $this->composePrompt($options)
            ]
        ];

        // Add images if provided
        foreach ($inputImages as $image) {
            if ($image instanceof UploadedFile) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $image->getMimeType() ?: 'image/jpeg',
                        'data' => base64_encode(file_get_contents($image->getRealPath()))
                    ]
                ];
            }
        }

        $imageCount = (int) ($options['image_count'] ?? 1);
        $images = [];

        // Build generation config
        // Note: Gemini models don't support aspectRatio in generationConfig
        // Aspect ratio must be specified in the prompt instead
        // Note: responseMimeType only supports text formats (text/plain, application/json, etc.)
        $generationConfig = [
            'temperature' => 0.8,
            'topK' => 40,
            'topP' => 0.95,
            'candidateCount' => 1, // Always use 1 for compatibility
            'maxOutputTokens' => 8192,
        ];

        // Most Gemini models don't support multiple candidates for image generation
        // So we need to make multiple requests
        for ($i = 0; $i < $imageCount; $i++) {
            $payload = [
                'contents' => [
                    [
                        'parts' => $parts
                    ]
                ],
                'generationConfig' => $generationConfig
            ];
            
            try {
                $response = $this->send(
                    $this->resolveModel($options) . ':generateContent',
                    $payload
                );
                
                // Merge the results
                $images = array_merge($images, $response);
                
            } catch (\Exception $e) {
                // If we got at least one image, don't fail completely
                if (!empty($images)) {
                    Log::warning('Failed to generate some images in batch', [
                        'error' => $e->getMessage(),
                        'index' => $i,
                        'total_requested' => $imageCount
                    ]);
                    break;
                } else {
                    throw $e;
                }
            }
        }
        
        return $images;
    }
    
    protected function generateWithImages(array $options, array $images): array
    {
        $model = $this->resolveModel($options);

        // Gemini models support multimodal input
        if ($this->isGeminiModel($model)) {
            return $this->generateContentWithGemini($options, $images);
        }

        // Imagen models don't support image input, fallback to text-only
        Log::info('Model does not support image input, using text-only generation');
        return $this->textToImage($options);
    }

    protected function generateWithImage(array $options, UploadedFile $image): array
    {
        return $this->generateWithImages($options, [$image]);
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

        $sanitizedPayload = $this->sanitize($payload);
        
        // Log the request for debugging
        Log::info('Imagen API request', [
            'endpoint' => $endpoint,
            'payload_keys' => array_keys($sanitizedPayload),
            'instance_keys' => isset($sanitizedPayload['instances'][0]) ? array_keys($sanitizedPayload['instances'][0]) : [],
        ]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $sanitizedPayload);

        if ($response->failed()) {
            Log::error('Imagen API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
                'payload_structure' => [
                    'instances' => isset($sanitizedPayload['instances'][0]) ? array_keys($sanitizedPayload['instances'][0]) : [],
                    'parameters' => isset($sanitizedPayload['parameters']) ? $sanitizedPayload['parameters'] : [],
                ]
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

        // Handle Gemini generateContent response
        if (!empty($response['candidates'])) {
            foreach ($response['candidates'] as $candidate) {
                if (isset($candidate['content']['parts'])) {
                    foreach ($candidate['content']['parts'] as $part) {
                        // Check for inline image data
                        if (isset($part['inlineData']['data'])) {
                            $images[] = [
                                'data' => $part['inlineData']['data'],
                                'mime' => $part['inlineData']['mimeType'] ?? 'image/png',
                            ];
                        }
                        // Check for text that might contain base64 image
                        elseif (isset($part['text']) && preg_match('/^data:image\/(\w+);base64,(.+)$/', $part['text'], $matches)) {
                            $images[] = [
                                'data' => $matches[2],
                                'mime' => 'image/' . $matches[1],
                            ];
                        }
                    }
                }
            }
            
            // If no images found but we have text, this might be a text response
            // Log it for debugging
            if (empty($images) && isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                Log::info('Gemini returned text instead of image', [
                    'text' => substr($response['candidates'][0]['content']['parts'][0]['text'], 0, 200)
                ]);
            }
        }

        // Handle Imagen predict response
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

        if (!empty($response['responses'])) {
            foreach ($response['responses'] as $entry) {
                $generated = $entry['generatedImages'] ?? [];
                foreach ($generated as $image) {
                    $encoded = data_get($image, 'image.inlineData.data')
                        ?? data_get($image, 'image.inline_data.data')
                        ?? data_get($image, 'image.imageBytes')
                        ?? data_get($image, 'image.image_bytes');

                    if ($encoded) {
                        $images[] = [
                            'data' => $encoded,
                            'mime' => data_get($image, 'image.mimeType', 'image/png'),
                        ];
                    }
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
