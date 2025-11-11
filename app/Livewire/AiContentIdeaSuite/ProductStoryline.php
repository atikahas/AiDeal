<?php

namespace App\Livewire\AiContentIdeaSuite;

use App\Services\GeminiService;
use App\Services\AiActivityLogger;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductStoryline extends Component
{
    use WithFileUploads;

    public ?\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $productPhoto = null;
    public string $productDescription = '';
    public string $storyVibe = 'Random';
    public string $storyLighting = 'Random';
    public string $storyContentType = 'Random';
    public string $storyLanguage = 'English';
    public array $storyOutput = [];
    public ?string $storyRawResponse = null;

    public array $languages = ['English', 'Malay', 'Chinese', 'Tamil'];
    public array $storyVibes = ['Random', 'Inspirational', 'Bold', 'Playful', 'Premium'];
    public array $storyLightings = ['Random', 'Bright', 'Moody', 'Natural', 'Studio'];
    public array $storyContentTypes = ['Random', 'Product Ad', 'Founder Story', 'Tutorial', 'Lifestyle'];

    protected GeminiService $geminiService;

    public function boot(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function generateStoryline(): void
    {
        $this->validate([
            'productPhoto' => ['nullable', 'image', 'max:5120'],
            'productDescription' => ['required', 'string', 'min:10'],
        ]);

        if (!$this->geminiService->testConnection()) {
            $this->addError('productDescription', 'Unable to connect to Gemini API. Please check your API key.');
            return;
        }

        $prompt = $this->buildStorylinePrompt();
        $startTime = microtime(true);

        try {
            $response = $this->geminiService->generateContent($prompt);
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);

            if ($response) {
                $this->storyRawResponse = $response;
                $this->parseStorylineResponse($response);

                // Log successful generation
                AiActivityLogger::log(
                    activityType: 'product_storyline_generated',
                    model: 'gemini-2.5-flash',
                    prompt: $prompt,
                    output: json_encode($this->storyOutput, JSON_PRETTY_PRINT),
                    tokenCount: mb_strlen($prompt) + mb_strlen($response),
                    status: 'success',
                    latencyMs: $latencyMs,
                    meta: [
                        'product' => substr($this->productDescription, 0, 100),
                        'vibe' => $this->storyVibe,
                        'lighting' => $this->storyLighting,
                        'content_type' => $this->storyContentType,
                        'language' => $this->storyLanguage,
                        'scenes_count' => count($this->storyOutput)
                    ]
                );

                session()->flash('message', __('Storyline generated successfully.'));
            } else {
                throw new \Exception('Failed to generate storyline');
            }
        } catch (\Exception $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();
            $this->addError('productDescription', $errorMessage);

            // Log failure
            AiActivityLogger::log(
                activityType: 'product_storyline_generated',
                model: 'gemini-2.5-flash',
                prompt: $prompt,
                status: 'error',
                errorMessage: $errorMessage,
                latencyMs: $latencyMs,
                meta: [
                    'product' => substr($this->productDescription, 0, 100),
                    'vibe' => $this->storyVibe,
                    'lighting' => $this->storyLighting,
                    'content_type' => $this->storyContentType,
                    'language' => $this->storyLanguage
                ]
            );

            session()->flash('error', 'Failed to generate storyline: ' . $errorMessage);
        }
    }

    protected function buildStorylinePrompt(): string
    {
        $vibe = $this->storyVibe !== 'Random' ? " with a {$this->storyVibe} vibe" : '';
        $lighting = $this->storyLighting !== 'Random' ? " with {$this->storyLighting} lighting" : '';
        $contentType = $this->storyContentType !== 'Random' ? " in the style of a {$this->storyContentType}" : '';

        return "Create a 3-scene video ad concept for: {$this->productDescription}\n\n" .
               "**Style:**{$vibe}{$lighting}{$contentType}\n" .
               "**Language:** {$this->storyLanguage}\n\n" .
               "For each scene, provide:\n" .
               "1. A brief visual description\n" .
               "2. Suggested camera angles/movements\n" .
               "3. Key text or voiceover points\n\n" .
               "Format as a numbered list with clear scene separators.";
    }

    protected function parseStorylineResponse(string $response): void
    {
        $scenes = [];
        $currentScene = [];
        $sceneNumber = 1;
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            $line = trim($line);

            // Look for scene markers (e.g., "Scene 1:", "1.", etc.)
            if (preg_match('/^(?:Scene\s*)?(\d+)[:\.]\s*(.+)?/i', $line, $matches)) {
                if (!empty($currentScene)) {
                    $scenes[] = $this->formatScene($currentScene, $sceneNumber++);
                }
                $currentScene = [
                    'label' => __('Scene :number', ['number' => $matches[1]]),
                    'description' => $matches[2] ?? '',
                    'details' => []
                ];
            } elseif (!empty($currentScene)) {
                // Add details to the current scene
                if (preg_match('/-\s*(.+)/', $line, $detailMatch)) {
                    $currentScene['details'][] = $detailMatch[1];
                } elseif (!empty($line)) {
                    $currentScene['description'] .= (empty($currentScene['description']) ? '' : ' ') . $line;
                }
            }
        }

        // Add the last scene if it exists
        if (!empty($currentScene)) {
            $scenes[] = $this->formatScene($currentScene, $sceneNumber);
        }

        // Ensure we have at least 3 scenes
        while (count($scenes) < 3) {
            $scenes[] = [
                'label' => __('Scene :number', ['number' => count($scenes) + 1]),
                'description' => __('Scene description will be generated here.'),
                'details' => []
            ];
        }

        $this->storyOutput = array_slice($scenes, 0, 3);
    }

    protected function formatScene(array $scene, int $number): array
    {
        $description = trim($scene['description']);

        // Add details to the description if available
        if (!empty($scene['details'])) {
            $description .= "\n\n" . implode("\n", array_map(fn($d) => "• {$d}", $scene['details']));
        }

        return [
            'label' => __('Scene :number', ['number' => $number]),
            'description' => $description,
        ];
    }

    public function resetStoryline(): void
    {
        $this->productPhoto = null;
        $this->productDescription = '';
        $this->storyVibe = 'Random';
        $this->storyLighting = 'Random';
        $this->storyContentType = 'Random';
        $this->storyLanguage = 'English';
        $this->storyOutput = [];
        $this->storyRawResponse = null;
    }

    public function render()
    {
        return view('livewire.ai-content-idea-suite.product-storyline');
    }
}
