<?php

namespace App\Livewire\AiImageIdeaSuite;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ImageJob;
use App\Services\Ai\ImagenClient;
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
    public $sourceImage;
    public $imageGenerationPhoto = [];
    public $temporary_previews = [];
    public $isProcessing = false;
    public $uploadedFiles = [];
    public $recentJobs = [];
    public $activeTab = 'text-to-image';
    public $showAdvancedSettings = false;
    public $styles = [];
    public $lightingOptions = [];
    public $angleOptions = [];
    public $lensTypes = [];
    public $filmSimulations = [];
    public $compositions = [];
    public $composition = '';
    public $lighting = '';
    public $angle = '';
    public $lensType = '';
    public $filmSimulation = '';

    protected $rules = [
        'prompt' => 'required|string|max:1000',
        'negativePrompt' => 'nullable|string|max:1000',
        'imageCount' => 'required|integer|min:1|max:4',
        'aspectRatio' => 'required|in:1:1,4:3,16:9,9:16',
        'style' => 'required|string',
    ];
    
    protected function rulesForUploads()
    {
        return [
            'imageGenerationPhoto' => 'array|max:5',
            'imageGenerationPhoto.*' => 'image|max:5120',
        ];
    }

    protected $messages = [
        'imageGenerationPhoto.*.image' => 'Each file must be an image',
        'imageGenerationPhoto.*.max' => 'Each image must not be larger than 5MB',
        'imageGenerationPhoto.*.uploaded' => 'The file upload failed. The file may be too large or in an invalid format.',
        'imageGenerationPhoto.max' => 'You can upload a maximum of 5 images',
    ];

    protected $validationAttributes = [
        'imageGenerationPhoto.*' => 'image',
    ];

    public function mount()
    {
        $this->loadRecentJobs();
        $this->initializeOptions();
        $this->previewImages = [];
    }

    public function updatedImageGenerationPhoto()
    {
        $this->resetErrorBag();
        
        try {
            // Validate the uploaded files
            $this->validateOnly('imageGenerationPhoto');
            
            // Process each file
            $this->temporary_previews = [];
            
            foreach ($this->imageGenerationPhoto as $file) {
                $this->temporary_previews[] = [
                    'url' => $file->temporaryUrl(),
                    'name' => $file->getClientOriginalName(),
                    'size' => $this->formatBytes($file->getSize())
                ];
            }
            
            // Store the uploaded files for later use
            $this->uploadedFiles = $this->imageGenerationPhoto;
            
        } catch (\Exception $e) {
            $this->addError('imageGenerationPhoto', $e->getMessage());
            $this->imageGenerationPhoto = [];
            $this->temporary_previews = [];
        }
    }
    
    public function removeImage($index)
    {
        if (isset($this->temporary_previews[$index])) {
            // Remove the preview and corresponding file
            unset($this->temporary_previews[$index]);
            unset($this->imageGenerationPhoto[$index]);
            
            // Reset array keys
            $this->temporary_previews = array_values($this->temporary_previews);
            $this->imageGenerationPhoto = array_values($this->imageGenerationPhoto);
            
            // Clear any previous errors
            $this->resetErrorBag('imageGenerationPhoto');
        }
    }


    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    protected function initializeOptions()
    {
        $this->styles = ['Photography','3D','Anime','Cinematic','Comic','Digital Art','Fantasy Art','Line Art','Low Poly','Neo Punk','Origami','Pixel Art','Texture'];
    
        $this->compositions = ['Rule of Thirds','Center Composition','Leading Lines','Frame Within a Frame','Symmetry and Patterns'];
        
        $this->lightingOptions = ['Natural','Studio','Golden Hour','Sunset','Overcast','High Contrast','Low Key','Backlit','Dramatic','Soft'];

        $this->angleOptions = ['Front','Side','Top Down','Close-up','Wide Angle','Low Angle','High Angle','Over the Shoulder','Eye Level','Dutch Angle'];
        
        $this->lensTypes = ['Prime','Wide Angle','Telephoto','Macro','Fisheye','Tilt-Shift','Zoom','Portrait'];
        
        $this->filmSimulations = ['Kodak Portra 400','Fujifilm Provia','Kodak Ektar 100','Fujifilm Velvia 50','Ilford HP5+ 400','Kodak Tri-X 400','Fujifilm Superia 400','Kodak Gold 200','Cinestill 800T','Fujifilm Acros 100'];
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
        $this->validate();
        $this->isProcessing = true;

        try {
            $job = ImageJob::create([
                'user_id' => auth()->id(),
                'tool' => 'image-generation',
                'input_json' => [
                    'prompt' => $this->prompt,
                    'negative_prompt' => $this->negativePrompt,
                    'image_count' => $this->imageCount,
                    'aspect_ratio' => $this->aspectRatio,
                    'style' => $this->style,
                    'composition' => $this->composition,
                    'lighting' => $this->lighting,
                    'angle' => $this->angle,
                    'lens_type' => $this->lensType,
                    'film_simulation' => $this->filmSimulation,
                ],
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Store source image if provided
            if ($this->sourceImage) {
                $path = $this->sourceImage->store("ai-image-suite/" . auth()->id(), 'public');
                $job->update(['source_image_path' => $path]);
            }

            // Dispatch job to process the image generation
            // ProcessImageJob::dispatch($job);
            
            // For now, we'll simulate a success response
            $this->isProcessing = false;
            $this->loadRecentJobs();
            
            session()->flash('message', 'Image generation job started successfully!');
            
        } catch (\Exception $e) {
            $this->isProcessing = false;
            session()->flash('error', 'Failed to start image generation: ' . $e->getMessage());
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->reset(['sourceImage']);
    }

    public function render()
    {
        return view('livewire.ai-image-idea-suite.image-generation');
    }
}
