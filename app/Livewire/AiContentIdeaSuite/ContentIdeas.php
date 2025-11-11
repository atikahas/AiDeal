<?php

namespace App\Livewire\AiContentIdeaSuite;

use App\Services\GeminiService;
use App\Services\AiActivityLogger;
use Livewire\Component;

class ContentIdeas extends Component
{
    public string $contentTopic = '';
    public string $contentLanguage = 'English';
    public array $contentIdeasOutput = [];

    public array $languages = ['English', 'Malay', 'Chinese', 'Tamil'];

    protected GeminiService $geminiService;

    public function boot(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function generateContentIdeas(): void
    {
        $this->validate([
            'contentTopic' => ['required', 'string', 'min:4'],
            'contentLanguage' => ['required', 'string', 'in:English,Malay,Chinese,Tamil'],
        ]);

        if (!$this->geminiService->testConnection()) {
            $this->addError('contentTopic', 'Unable to connect to Gemini API. Please check your API key.');
            return;
        }

        $prompt = $this->buildContentIdeasPrompt();
        $startTime = microtime(true);

        try {
            $response = $this->geminiService->generateContent($prompt);
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);

            if ($response) {
                $this->parseContentIdeasResponse($response);

                // Log successful generation
                AiActivityLogger::log(
                    activityType: 'content_ideas_generated',
                    model: 'gemini-2.5-flash',
                    prompt: $prompt,
                    output: json_encode($this->contentIdeasOutput, JSON_PRETTY_PRINT),
                    tokenCount: mb_strlen($prompt) + mb_strlen($response),
                    status: 'success',
                    latencyMs: $latencyMs,
                    meta: [
                        'topic' => $this->contentTopic,
                        'language' => $this->contentLanguage,
                        'ideas_count' => count($this->contentIdeasOutput)
                    ]
                );

                session()->flash('message', __('Content ideas generated successfully.'));
            } else {
                throw new \Exception('Failed to generate content ideas');
            }
        } catch (\Exception $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();
            $this->addError('contentTopic', $errorMessage);

            // Log failure
            AiActivityLogger::log(
                activityType: 'content_ideas_generated',
                model: 'gemini-2.5-flash',
                prompt: $prompt,
                status: 'error',
                errorMessage: $errorMessage,
                latencyMs: $latencyMs,
                meta: [
                    'topic' => $this->contentTopic,
                    'language' => $this->contentLanguage
                ]
            );

            session()->flash('error', 'Failed to generate content ideas: ' . $errorMessage);
        }
    }

    protected function buildContentIdeasPrompt(): string
    {
        return "Generate 4 engaging content ideas about: {$this->contentTopic}\n\n" .
               "For each idea, provide:\n" .
               "1. A catchy title (max 10 words)\n" .
               "2. A unique angle or perspective (1-2 sentences)\n" .
               "3. A hook to capture attention (1 sentence)\n\n" .
               "Format the response as a numbered list with each idea separated by two newlines.\n" .
               "Output in {$this->contentLanguage} language.\n" .
               "IMPORTANT: All output must be in {$this->contentLanguage} language, including all titles, angles, and hooks.";
    }

    protected function parseContentIdeasResponse(string $response): void
    {
        $ideas = [];
        $lines = explode("\n", $response);
        $lines = array_filter($lines, function($line) {
            return !empty(trim($line));
        });
        $currentIdea = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if (!empty($currentIdea)) {
                    $ideas[] = $currentIdea;
                    $currentIdea = [];
                }
                continue;
            }

            if (preg_match('/^\d+\.\s*(.+)/', $line, $matches)) {
                if (!empty($currentIdea)) {
                    $ideas[] = $currentIdea;
                }
                $currentIdea = ['title' => $matches[1]];
            } elseif (preg_match('/^[A-Za-z\s]+:/', $line)) {
                // Skip section headers
                continue;
            } elseif (!empty($currentIdea)) {
                if (!isset($currentIdea['angle'])) {
                    $currentIdea['angle'] = $line;
                } elseif (!isset($currentIdea['hook'])) {
                    $currentIdea['hook'] = $line;
                }
            }
        }

        if (!empty($currentIdea)) {
            $ideas[] = $currentIdea;
        }

        $this->contentIdeasOutput = array_slice($ideas, 0, 5);
    }

    public function resetContentIdeas(): void
    {
        $this->contentTopic = '';
        $this->contentLanguage = 'English';
        $this->contentIdeasOutput = [];
    }

    public function render()
    {
        return view('livewire.ai-content-idea-suite.content-ideas');
    }
}
