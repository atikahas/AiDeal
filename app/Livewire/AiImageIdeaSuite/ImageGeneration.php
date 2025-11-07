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
    public $isProcessing = false;
    public $recentJobs = [];
    public $activeTab = 'text-to-image';

    protected $rules = [
        'prompt' => 'required|string|max:1000',
        'negativePrompt' => 'nullable|string|max:1000',
        'imageCount' => 'required|integer|min:1|max:4',
        'aspectRatio' => 'required|in:1:1,4:3,16:9,9:16',
        'style' => 'required|string',
        'sourceImage' => 'nullable|image|max:10240',
    ];

    public function mount()
    {
        $this->loadRecentJobs();
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
