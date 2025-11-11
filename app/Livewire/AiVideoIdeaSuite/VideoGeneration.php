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
    public $referenceImage;
    public $generatedVideos = [];
    public $isProcessing = false;
    public $selectedVideoIndex = null;
    public $currentVideoJobId = null;

    public array $durations = ['4', '6', '8'];
    public array $aspectRatios = ['16:9', '9:16'];
    public array $styles = ['Cinematic', 'Realistic', 'Animated', 'Artistic', 'Documentary'];

    protected VeoClient $veoClient;

    public function boot(VeoClient $veoClient)
    {
        $this->veoClient = $veoClient;
    }

    protected function rules(): array
    {
        return [
            'prompt' => 'required|string|min:10|max:1000',
            'duration' => 'required|in:4,6,8',
            'aspectRatio' => 'required|string|in:16:9,9:16',
            'style' => 'required|string',
            'referenceImage' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
        ];
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

            $this->generatedVideos = $videos;
            $this->selectedVideoIndex = empty($videos) ? null : 0;

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

        if (!$video || empty($video['data'])) {
            session()->flash('error', __('Unable to save the selected video.'));
            return;
        }

        if (!$this->currentVideoJobId) {
            session()->flash('error', __('Invalid video job.'));
            return;
        }

        try {
            $userId = auth()->id();
            $timestamp = now()->format('Y-m-d_H-i-s');

            // Generate unique filename
            $filename = sprintf(
                'ai-videos/%s/veo_%s_%d.mp4',
                $userId,
                $timestamp,
                $this->selectedVideoIndex + 1
            );

            // Decode base64 video data
            $videoData = base64_decode($video['data']);

            // Save to storage
            Storage::disk('public')->put($filename, $videoData);

            $savedVideoData = [
                'path' => $filename,
                'mime' => $video['mime'] ?? 'video/mp4',
                'url' => Storage::disk('public')->url($filename),
                'size' => strlen($videoData),
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

        if (!$video || empty($video['data'])) {
            session()->flash('error', __('Unable to download the selected video.'));
            return null;
        }

        $filename = sprintf(
            'veo-%s-%d.mp4',
            now()->format('Ymd-His'),
            $index + 1
        );

        return response()->streamDownload(function () use ($video) {
            echo base64_decode($video['data']);
        }, $filename, [
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
            'referenceImage',
            'generatedVideos',
            'selectedVideoIndex',
            'currentVideoJobId',
        ]);

        $this->duration = '8';
        $this->aspectRatio = '16:9';
        $this->style = 'cinematic';
    }

    public function render()
    {
        return view('livewire.ai-video-idea-suite.video-generation');
    }
}
