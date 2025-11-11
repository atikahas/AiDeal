<?php

namespace App\Livewire\AiVideoIdeaSuite;

use App\Models\VideoJob;
use App\Services\Ai\VeoClient;
use App\Services\AiActivityLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class VideoGeneration extends Component
{
    use WithFileUploads;

    public $prompt = '';
    public $duration = '8';
    public $aspectRatio = '16:9';
    public $style = 'cinematic';
    public $onScreenText = '';
    public $spokenDialogue = '';
    public $voiceoverLanguage = 'English';
    public $voiceoverMood = 'Normal';
    public $referenceImage;
    public $generatedVideos = [];
    public $isProcessing = false;
    public $isMagicPromptProcessing = false;
    public $selectedVideoIndex = null;
    public $currentVideoJobId = null;

    public array $durations = ['4', '6', '8'];
    public array $aspectRatios = ['16:9', '9:16'];
    public array $styles = ['Cinematic', 'Realistic', 'Animated', 'Artistic', 'Documentary'];
    public array $voiceoverLanguages = ['English', 'Malay', 'Chinese', 'Tamil'];
    public array $voiceoverMoods = ['Normal', 'Happy', 'Sad', 'Excited', 'Calm', 'Serious', 'Friendly', 'Professional', 'Energetic', 'Warm'];

    protected VeoClient $veoClient;
    protected \App\Services\GeminiService $geminiService;

    public function boot(VeoClient $veoClient, \App\Services\GeminiService $geminiService)
    {
        $this->veoClient = $veoClient;
        $this->geminiService = $geminiService;
    }

    protected function rules(): array
    {
        return [
            'prompt' => 'required|string|min:10|max:1000',
            'duration' => 'required|in:4,6,8',
            'aspectRatio' => 'required|string|in:16:9,9:16',
            'style' => 'required|string',
            'onScreenText' => 'nullable|string|max:500',
            'spokenDialogue' => 'nullable|string|max:1000',
            'voiceoverLanguage' => 'nullable|string|in:English,Malay,Chinese,Tamil',
            'voiceoverMood' => 'nullable|string|in:Normal,Happy,Sad,Excited,Calm,Serious,Friendly,Professional,Energetic,Warm',
            'referenceImage' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function generateMagicPrompt()
    {
        if (empty($this->prompt)) {
            session()->flash('error', __('Please enter a product name or description first.'));
            return;
        }

        $this->isMagicPromptProcessing = true;

        try {
            $enhancementPrompt = "Create an 8-second TikTok/Reels video concept for: {$this->prompt}\n\n";
            $enhancementPrompt .= "Format:\n";
            $enhancementPrompt .= "1. Scene: [Setting + lighting + action]\n";
            $enhancementPrompt .= "2. Shots: [Key shot types]\n";
            $enhancementPrompt .= "3. Style: [Aesthetic + colors]\n";
            $enhancementPrompt .= "4. Text: [Key text/graphics]\n\n";
            $enhancementPrompt .= "Example:\n";
            $enhancementPrompt .= "\"8s video showing [product] in use. Close-ups of [key feature] with [style]. Text: [main benefit].\"\n\n";
            $enhancementPrompt .= "💬 [Natural 1-2 line script]";

            $enhancedPrompt = $this->geminiService->generateContent($enhancementPrompt, [
                'temperature' => 0.7,
            ]);

            if ($enhancedPrompt) {
                $this->prompt = trim($enhancedPrompt);
                session()->flash('message', __('Magic prompt generated successfully!'));
            } else {
                throw new \Exception('No enhanced prompt was generated');
            }
        } catch (\Exception $e) {
            \Log::error('Magic prompt generation failed', [
                'error' => $e->getMessage(),
                'original_prompt' => $this->prompt,
            ]);

            session()->flash('error', __('Failed to generate magic prompt: :message', [
                'message' => $e->getMessage(),
            ]));
        }

        $this->isMagicPromptProcessing = false;
    }

    public function generateVideo()
    {
        $this->validate();
        $this->isProcessing = true;
        $this->generatedVideos = [];
        $this->selectedVideoIndex = null;

        $payload = $this->buildPayload();
        $startTime = microtime(true);

        try {
            // Generate video using VeoClient
            $videos = $this->referenceImage
                ? $this->veoClient->imageToVideo($payload, $this->referenceImage)
                : $this->veoClient->textToVideo($payload);

            \Log::info('Videos received from VeoClient', [
                'count' => count($videos),
                'keys' => !empty($videos) ? array_keys($videos[0] ?? []) : [],
                'has_data' => !empty($videos) && !empty($videos[0]['data'] ?? null),
            ]);

            // Store videos to temporary files immediately to avoid Livewire size limits
            $processedVideos = [];
            foreach ($videos as $index => $video) {
                if (!empty($video['data'])) {
                    $userId = auth()->id();
                    $timestamp = now()->format('Y-m-d_H-i-s');
                    $filename = sprintf('ai-videos/%s/temp_veo_%s_%d.mp4', $userId, $timestamp, $index + 1);

                    // Decode and save to storage
                    $videoData = base64_decode($video['data']);
                    Storage::disk('public')->put($filename, $videoData);

                    $processedVideos[] = [
                        'url' => Storage::disk('public')->url($filename),
                        'path' => $filename,
                        'mime' => $video['mime'] ?? 'video/mp4',
                        'size' => strlen($videoData),
                    ];
                }
            }

            $this->generatedVideos = $processedVideos;
            $this->selectedVideoIndex = empty($processedVideos) ? null : 0;

            \Log::info('Videos processed and stored', [
                'generatedVideos_count' => count($this->generatedVideos),
                'selectedVideoIndex' => $this->selectedVideoIndex,
            ]);

            // Create VideoJob record
            $videoJob = VideoJob::create([
                'user_id' => auth()->id(),
                'tool' => 'video-generation',
                'input_json' => $payload,
                'generated_videos' => null,
                'status' => 'completed',
                'is_saved' => false,
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $this->currentVideoJobId = $videoJob->id;

            // Log to AI Activity
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            AiActivityLogger::log(
                activityType: 'video_generation',
                model: 'veo-3.1-generate-preview',
                prompt: $this->prompt,
                output: 'Generated ' . count($videos) . ' video(s)',
                tokenCount: 0,
                status: 'success',
                latencyMs: $latencyMs,
                meta: [
                    'duration' => $this->duration,
                    'aspect_ratio' => $this->aspectRatio,
                    'style' => $this->style,
                    'video_job_id' => $videoJob->id,
                ]
            );

            session()->flash('message', __('Video generated successfully.'));
        } catch (\Exception $e) {
            $this->isProcessing = false;

            // Log failure to AI Activity
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            AiActivityLogger::log(
                activityType: 'video_generation',
                model: 'veo-3.1-generate-preview',
                prompt: $this->prompt,
                output: null,
                tokenCount: 0,
                status: 'error',
                errorMessage: $e->getMessage(),
                latencyMs: $latencyMs,
                meta: [
                    'duration' => $this->duration,
                    'aspect_ratio' => $this->aspectRatio,
                    'style' => $this->style,
                ]
            );

            report($e);
            session()->flash('error', __('Failed to generate video: :message', [
                'message' => $e->getMessage(),
            ]));
            return;
        }

        $this->isProcessing = false;
    }

    protected function buildPayload(): array
    {
        $promptText = $this->prompt;

        // Add style context to prompt
        if (!empty($this->style)) {
            $promptText = "{$this->style} style: {$promptText}";
        }

        // Add on-screen text
        if (!empty($this->onScreenText)) {
            $promptText .= "\n\nOn-screen text to display: \"{$this->onScreenText}\"";
        }

        // Add spoken dialogue with voiceover details
        if (!empty($this->spokenDialogue)) {
            $voiceDetails = [];
            if (!empty($this->voiceoverLanguage)) {
                $voiceDetails[] = "language: {$this->voiceoverLanguage}";
            }
            if (!empty($this->voiceoverMood)) {
                $voiceDetails[] = "mood: {$this->voiceoverMood}";
            }

            $voiceContext = !empty($voiceDetails) ? ' (' . implode(', ', $voiceDetails) . ')' : '';
            $promptText .= "\n\nVoiceover{$voiceContext}: \"{$this->spokenDialogue}\"";
        }

        return [
            'prompt' => $promptText,
            'duration' => (int) $this->duration,
            'aspectRatio' => $this->aspectRatio,
            'model' => 'veo-3.1-generate-preview',
        ];
    }

    public function selectVideo($index): void
    {
        $this->selectedVideoIndex = (int)$index;
    }

    public function saveVideo($index): void
    {
        $this->selectedVideoIndex = (int)$index;
        $video = $this->generatedVideos[$this->selectedVideoIndex] ?? null;

        if (!$video || empty($video['path'])) {
            session()->flash('error', __('Unable to save the selected video.'));
            return;
        }

        if (!$this->currentVideoJobId) {
            session()->flash('error', __('Invalid video job.'));
            return;
        }

        try {
            $savedVideoData = [
                'path' => $video['path'],
                'mime' => $video['mime'] ?? 'video/mp4',
                'url' => $video['url'],
                'size' => $video['size'],
            ];

            // Update the VideoJob record
            $videoJob = VideoJob::find($this->currentVideoJobId);
            if ($videoJob) {
                $existingVideos = $videoJob->generated_videos ?? [];
                $existingVideos[] = $savedVideoData;

                $videoJob->update([
                    'generated_videos' => $existingVideos,
                    'is_saved' => true,
                ]);
            }

            session()->flash('message', __('Video saved successfully.'));

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', __('Failed to save the video.'));
        }
    }

    public function downloadVideo($index = null)
    {
        $index = $index ?? $this->selectedVideoIndex;

        if ($index === null) {
            session()->flash('error', __('Please select a video to download.'));
            return null;
        }

        $video = $this->generatedVideos[$index] ?? null;

        if (!$video || empty($video['path'])) {
            session()->flash('error', __('Unable to download the selected video.'));
            return null;
        }

        $filename = sprintf(
            'veo-%s-%d.mp4',
            now()->format('Ymd-His'),
            $index + 1
        );

        // Get the file from storage
        $filePath = Storage::disk('public')->path($video['path']);

        if (!file_exists($filePath)) {
            session()->flash('error', __('Video file not found.'));
            return null;
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => $video['mime'] ?? 'video/mp4',
        ]);
    }

    public function resetForm()
    {
        $this->reset([
            'prompt',
            'duration',
            'aspectRatio',
            'style',
            'onScreenText',
            'spokenDialogue',
            'voiceoverLanguage',
            'voiceoverMood',
            'referenceImage',
            'generatedVideos',
            'selectedVideoIndex',
            'currentVideoJobId',
        ]);

        $this->duration = '8';
        $this->aspectRatio = '16:9';
        $this->style = 'cinematic';
        $this->voiceoverLanguage = 'English';
        $this->voiceoverMood = 'Normal';
    }

    public function render()
    {
        return view('livewire.ai-video-idea-suite.video-generation');
    }
}
