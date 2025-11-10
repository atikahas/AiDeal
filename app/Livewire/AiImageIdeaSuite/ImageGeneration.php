<?php

namespace App\Livewire\AiImageIdeaSuite;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ImageJob;
use App\Services\Ai\ImagenClient;
use App\Services\AiActivityLogger;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageGeneration extends Component
{
    use WithFileUploads;

    public $prompt = '';
    public $negativePrompt = '';
    public $imageCount = 1;
    public $aspectRatio = '1:1';
    public $style = 'photographic';
    public $isProcessing = false;
    public $recentJobs = [];
    public $showAdvancedSettings = false;
    public $styles = [];
    public $lightingOptions = [];
    public $angleOptions = [];
    public $lensTypes = [];
    public $filmSimulations = [];
    public $activeModel;
    public $compositions = [];
    public $composition = '';
    public $lighting = '';
    public $angle = '';
    public $lensType = '';
    public $filmSimulation = '';
    public $image;
    public $generatedImages = [];
    public $selectedImageIndex = null;
    public $generationMode = 'text-to-image';
    public $imagePreviewUrl;
    public $currentImageJobId = null;

    public function mount()
    {
        $this->loadRecentJobs();
        $this->initializeOptions();
    }

    protected function initializeOptions()
    {
        $this->styles = ['Photography','3D','Anime','Cinematic','Comic','Digital Art','Fantasy Art','Line Art','Low Poly','Neo Punk','Origami','Pixel Art','Texture'];
    
        $this->compositions = ['Rule of Thirds','Center Composition','Leading Lines','Frame Within a Frame','Symmetry and Patterns'];
        
        $this->lightingOptions = ['Natural','Studio','Golden Hour','Sunset','Overcast','High Contrast','Low Key','Backlit','Dramatic','Soft'];

        $this->angleOptions = ['Front','Side','Top Down','Close-up','Wide Angle','Low Angle','High Angle','Over the Shoulder','Eye Level','Dutch Angle'];
        
        $this->lensTypes = ['Prime','Wide Angle','Telephoto','Macro','Fisheye','Tilt-Shift','Zoom','Portrait'];
        
        $this->filmSimulations = ['Kodak Portra 400','Fujifilm Provia','Kodak Ektar 100','Fujifilm Velvia 50','Ilford HP5+ 400','Kodak Tri-X 400','Fujifilm Superia 400','Kodak Gold 200','Cinestill 800T','Fujifilm Acros 100'];

        $this->activeModel = config('services.gemini.imagen_default_model', 'imagen-4.0-generate-preview-06-06');
    }

    protected function rules(): array
    {
        $rules = [
            'prompt' => 'required|string|max:1000',
            'negativePrompt' => 'nullable|string|max:1000',
            'imageCount' => 'required|integer|min:1|max:5',
            'aspectRatio' => 'required|in:1:1,4:3,16:9,9:16,3:2',
            'style' => 'nullable|string|max:255',
            'composition' => 'nullable|string|max:255',
            'lighting' => 'nullable|string|max:255',
            'angle' => 'nullable|string|max:255',
            'lensType' => 'nullable|string|max:255',
            'filmSimulation' => 'nullable|string|max:255',
            'image' => 'nullable|file|max:2048',
            'generationMode' => 'required|string|in:text-to-image,text-image',
        ];

        if ($this->generationMode !== 'text-to-image') {
            $rules['image'] = 'required|file|max:2048';
        }

        return $rules;
    }

    public function loadRecentJobs()
    {
        $this->recentJobs = ImageJob::where('user_id', auth()->id())
            ->where('tool', 'image-generation')
            ->latest()
            ->take(5)
            ->get();
    }

    public function generateImage()
    {
        $this->prompt = trim((string) $this->prompt);
        $this->negativePrompt = trim((string) $this->negativePrompt);
        
        // Set default prompt if empty
        if ($this->prompt === '') {
            $this->prompt = $this->defaultPrompt();
        }

        $this->validate();
        $this->isProcessing = true;
        $this->resetErrorBag();
        $this->selectedImageIndex = null;
        $this->generatedImages = [];

        $payload = $this->buildPayload();
        $client = app(ImagenClient::class);

        $startTime = microtime(true);
        
        try {
            $images = match ($this->generationMode) {
                'text-image' => $client->editImage($payload, $this->image),
                default => $client->textToImage($payload),
            };

            $this->generatedImages = $images;
            $this->selectedImageIndex = empty($images) ? null : 0;

            // Create ImageJob record without saved paths
            $imageJob = ImageJob::create([
                'user_id' => auth()->id(),
                'tool' => 'image-generation',
                'input_json' => array_merge($payload, ['generation_mode' => $this->generationMode]),
                'generated_images' => null, // Will be populated when user saves
                'status' => 'completed',
                'is_saved' => false, // Not saved by default
                'started_at' => now(),
                'finished_at' => now(),
            ]);
            
            // Store the current image job ID
            $this->currentImageJobId = $imageJob->id;

            // Log to AI Activity
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            AiActivityLogger::log(
                activityType: 'image_generation',
                model: $payload['model'] ?? $this->activeModel,
                prompt: $this->prompt,
                output: 'Generated ' . count($images) . ' image(s)',
                tokenCount: 0, // Image generation doesn't have token count
                status: 'success',
                latencyMs: $latencyMs,
                meta: [
                    'generation_mode' => $this->generationMode,
                    'image_count' => count($images),
                    'aspect_ratio' => $this->aspectRatio,
                    'style' => $this->style,
                    'image_job_id' => $imageJob->id,
                ]
            );

            $this->loadRecentJobs();
            session()->flash('message', __('Images generated successfully.'));
        } catch (\Exception $e) {
            $this->isProcessing = false;
            
            // Log failure to AI Activity
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            AiActivityLogger::log(
                activityType: 'image_generation',
                model: $payload['model'] ?? $this->activeModel,
                prompt: $this->prompt,
                output: null,
                tokenCount: 0,
                status: 'error',
                errorMessage: $e->getMessage(),
                latencyMs: $latencyMs,
                meta: [
                    'generation_mode' => $this->generationMode,
                    'aspect_ratio' => $this->aspectRatio,
                    'style' => $this->style,
                ]
            );
            
            report($e);
            session()->flash('error', __('Failed to generate images: :message', [
                'message' => $e->getMessage(),
            ]));
            return;
        }

        $this->isProcessing = false;
    }

    public function setGenerationMode(string $mode): void
    {
        if (!in_array($mode, ['text-to-image', 'text-image'], true)) {
            return;
        }

        $this->generationMode = $mode;
        $this->resetErrorBag();
        $this->resetValidation();
        
        // Clear the uploaded image when switching to text-to-image mode
        if ($mode === 'text-to-image') {
            $this->image = null;
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'prompt',
            'negativePrompt',
            'imageCount',
            'aspectRatio',
            'style',
            'composition',
            'lighting',
            'angle',
            'lensType',
            'filmSimulation',
            'image',
            'generatedImages',
            'selectedImageIndex',
            'currentImageJobId',
        ]);

        $this->imageCount = 1;
        $this->aspectRatio = '1:1';
        $this->style = 'photographic';
        $this->generationMode = 'text-to-image';
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

    protected function buildPayload(): array
    {
        return [
            'prompt' => $this->prompt,
            'negative_prompt' => $this->negativePrompt,
            'image_count' => $this->imageCount,
            'aspect_ratio' => $this->aspectRatio,
            'style' => $this->style,
            'composition' => $this->composition,
            'lighting' => $this->lighting,
            'angle' => $this->angle,
            'lensType' => $this->lensType,
            'filmSimulation' => $this->filmSimulation,
            'model' => $this->activeModel,
        ];
    }

    protected function defaultPrompt(): string
    {
        return __('High quality photo');
    }

    public function selectImage($index): void
    {
        $this->selectedImageIndex = (int)$index;
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
                'ai-images/%s/%s_%s_%d.png',
                $userId,
                Str::slug($this->activeModel),
                $timestamp,
                $this->selectedImageIndex + 1
            );
            
            // Decode base64 image data
            $imageData = base64_decode($image['data']);
            
            // Save to storage
            Storage::disk('public')->put($filename, $imageData);
            
            $savedImageData = [
                'path' => $filename,
                'mime' => $image['mime'] ?? 'image/png',
                'url' => Storage::disk('public')->url($filename),
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
            'imagen-%s-%d.png',
            now()->format('Ymd-His'),
            $index + 1
        );

        return response()->streamDownload(function () use ($image) {
            echo base64_decode($image['data']);
        }, $filename, [
            'Content-Type' => $image['mime'] ?? 'image/png',
        ]);
    }

    public function render()
    {
        return view('livewire.ai-image-idea-suite.image-generation');
    }
}
