<?php

namespace App\Livewire\AiImageIdeaSuite;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ImageJob;
use App\Services\Ai\ImagenClient;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $this->activeModel = config('services.gemini.imagen_default_model', 'gemini-2.5-flash-image');
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

        try {
            $images = match ($this->generationMode) {
                'text-image' => $client->editImage($payload, $this->image),
                default => $client->textToImage($payload),
            };

            $this->generatedImages = $images;
            $this->selectedImageIndex = empty($images) ? null : 0;

            ImageJob::create([
                'user_id' => auth()->id(),
                'tool' => 'image-generation',
                'input_json' => array_merge($payload, ['generation_mode' => $this->generationMode]),
                'status' => 'completed',
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $this->loadRecentJobs();
            session()->flash('message', __('Images generated successfully.'));
        } catch (\Exception $e) {
            $this->isProcessing = false;
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

    public function downloadImage(): ?StreamedResponse
    {
        if ($this->selectedImageIndex === null) {
            return null;
        }

        $image = $this->generatedImages[$this->selectedImageIndex] ?? null;

        if (!$image || empty($image['data'])) {
            session()->flash('error', __('Unable to download the selected image.'));
            return null;
        }

        $filename = sprintf(
            'imagen-%s-%d.png',
            now()->format('Ymd-His'),
            $this->selectedImageIndex + 1
        );

        return response()->streamDownload(function () use ($image) {
            echo base64_decode($image['data']);
        }, $filename, [
            'Content-Type' => $image['mime'] ?? 'image/png',
        ]);
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

    public function render()
    {
        return view('livewire.ai-image-idea-suite.image-generation');
    }
}
