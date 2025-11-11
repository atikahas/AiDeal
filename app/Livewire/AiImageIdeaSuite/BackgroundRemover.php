<?php

namespace App\Livewire\AiImageIdeaSuite;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ImageJob;
use App\Services\Ai\ImagenClient;
use App\Services\AiActivityLogger;
use Illuminate\Support\Facades\Storage;

class BackgroundRemover extends Component
{
    use WithFileUploads;

    public $image;
    public $generatedImages = [];
    public $selectedImageIndex = null;
    public $currentImageJobId = null;
    public $activeModel;
    public $isProcessing = false;
    public $showImageModal = false;
    public $viewingImageIndex = null;

    public function mount()
    {
        $this->activeModel = config('services.gemini.imagen_default_model', 'imagen-4.0-generate-preview-06-06');
    }

    protected function rules(): array
    {
        return [
            'image' => 'required|file|max:2048|mimes:png,jpg,jpeg,webp',
        ];
    }

    public function removeBackground()
    {
        $this->validate();
        $this->isProcessing = true;
        $this->resetErrorBag();
        $this->selectedImageIndex = null;
        $this->generatedImages = [];

        $payload = $this->buildPayload();
        $client = app(ImagenClient::class);

        $startTime = microtime(true);

        try {
            // Use editImage method with the uploaded image
            $images = $client->editImage($payload, $this->image);

            $this->generatedImages = $images;
            $this->selectedImageIndex = empty($images) ? null : 0;

            // Create ImageJob record
            $imageJob = ImageJob::create([
                'user_id' => auth()->id(),
                'tool' => 'background-remover',
                'input_json' => $payload,
                'generated_images' => null,
                'status' => 'completed',
                'is_saved' => false,
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $this->currentImageJobId = $imageJob->id;

            // Log to AI Activity
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            AiActivityLogger::log(
                activityType: 'background_removal',
                model: $payload['model'] ?? $this->activeModel,
                prompt: 'Background removal',
                output: 'Generated ' . count($images) . ' image(s) with transparent background',
                tokenCount: 0,
                status: 'success',
                latencyMs: $latencyMs,
                meta: [
                    'image_count' => count($images),
                    'image_job_id' => $imageJob->id,
                ]
            );

            session()->flash('message', __('Background removed successfully.'));
        } catch (\Exception $e) {
            $this->isProcessing = false;

            // Log failure to AI Activity
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            AiActivityLogger::log(
                activityType: 'background_removal',
                model: $payload['model'] ?? $this->activeModel,
                prompt: 'Background removal',
                output: null,
                tokenCount: 0,
                status: 'error',
                errorMessage: $e->getMessage(),
                latencyMs: $latencyMs,
                meta: []
            );

            report($e);
            session()->flash('error', __('Failed to remove background: :message', [
                'message' => $e->getMessage(),
            ]));
            return;
        }

        $this->isProcessing = false;
    }

    protected function buildPayload(): array
    {
        return [
            'prompt' => 'Remove the background from the provided image. The output should be a clean PNG with a transparent background. Isolate the main subject perfectly.',
            'image_count' => 1,
            'model' => $this->activeModel,
        ];
    }

    public function resetForm(): void
    {
        $this->reset([
            'image',
            'generatedImages',
            'selectedImageIndex',
            'currentImageJobId',
        ]);
    }

    public function updatedImage(): void
    {
        if ($this->image) {
            try {
                $this->validateOnly('image');
            } catch (\Exception $e) {
                $this->image = null;
                throw $e;
            }
        }
    }

    public function selectImage($index): void
    {
        $this->selectedImageIndex = (int)$index;
    }

    public function viewImage($index): void
    {
        $this->viewingImageIndex = (int)$index;
        $this->showImageModal = true;
    }

    public function closeImageModal(): void
    {
        $this->showImageModal = false;
        $this->viewingImageIndex = null;
    }

    public function saveImage($index): void
    {
        $this->selectedImageIndex = (int)$index;
        $image = $this->generatedImages[$this->selectedImageIndex] ?? null;

        if (!$image || empty($image['data'])) {
            session()->flash('error', __('Unable to save the selected image.'));
            return;
        }

        if (!$this->currentImageJobId) {
            session()->flash('error', __('Invalid image job.'));
            return;
        }

        try {
            $userId = auth()->id();
            $timestamp = now()->format('Y-m-d_H-i-s');

            // Generate unique filename
            $filename = sprintf(
                'ai-images/%s/bg-removed_%s.png',
                $userId,
                $timestamp
            );

            // Decode base64 image data
            $imageData = base64_decode($image['data']);

            // Save to storage
            Storage::disk('public')->put($filename, $imageData);

            $savedImageData = [
                'path' => $filename,
                'mime' => $image['mime'] ?? 'image/png',
                'size' => strlen($imageData),
            ];

            // Update the ImageJob record
            $imageJob = ImageJob::find($this->currentImageJobId);
            if ($imageJob) {
                $existingImages = $imageJob->generated_images ?? [];
                $existingImages[] = $savedImageData;

                $imageJob->update([
                    'generated_images' => $existingImages,
                    'is_saved' => true,
                ]);
            }

            session()->flash('message', __('Image saved successfully.'));

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', __('Failed to save the image.'));
        }
    }

    public function downloadImage($index = null)
    {
        $index = $index ?? $this->selectedImageIndex;

        if ($index === null) {
            session()->flash('error', __('Please select an image to download.'));
            return null;
        }

        $image = $this->generatedImages[$index] ?? null;

        if (!$image || empty($image['data'])) {
            session()->flash('error', __('Unable to download the selected image.'));
            return null;
        }

        $filename = sprintf(
            'bg-removed-%s.png',
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($image) {
            echo base64_decode($image['data']);
        }, $filename, [
            'Content-Type' => 'image/png',
        ]);
    }

    public function render()
    {
        return view('livewire.ai-image-idea-suite.background-remover');
    }
}
