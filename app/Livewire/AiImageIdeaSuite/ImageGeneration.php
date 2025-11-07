<?php

namespace App\Livewire\AiImageIdeaSuite;

use Livewire\Component;
use App\Models\ImageJob;
use App\Services\Ai\ImagenClient;
use Illuminate\Support\Facades\Storage;

class ImageGeneration extends Component
{
    public $prompt = '';
    public $negativePrompt = '';
    public $imageCount = 1;
    public $aspectRatio = '1:1';
    public $style = 'photographic';
    public $isProcessing = false;
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
    }

    public function render()
    {
        return view('livewire.ai-image-idea-suite.image-generation');
    }
}
