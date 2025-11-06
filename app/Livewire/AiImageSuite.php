<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Services\AiActivityLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

#[Layout('components.layouts.app')]
class AiImageSuite extends Component
{
    use WithFileUploads;

    public string $activeTab = 'image-generation';
    public $image;
    public $prompt = '';
    public $generatedImage = null;
    public $isGenerating = false;
    public $imageStyle = 'photorealistic';
    public $imageAspectRatio = '1:1';

    protected $rules = [
        'prompt' => 'required|string|min:10|max:1000',
        'image' => 'nullable|image|max:10240', // 10MB max
    ];

    public function render()
    {
        return view('livewire.ai-image-suite');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function generateImage()
    {
        $this->validate();
        $this->isGenerating = true;

        try {
            // Simulate API call to Google Imagen
            // In a real implementation, this would call the Google Cloud Vision API
            $imagePath = $this->image
                ? $this->image->store('ai-image-suite/temp', 'public')
                : null;

            // Generate a unique filename
            $filename = 'ai-image-' . uniqid() . '.png';
            $storagePath = 'ai-image-suite/' . $filename;
            
            // In a real implementation, this would be the actual image generation
            // For now, we'll just copy a placeholder
            if ($imagePath) {
                Storage::disk('public')->copy($imagePath, $storagePath);
                Storage::disk('public')->delete($imagePath);
            } else {
                // Generate a placeholder image
                $placeholder = imagecreatetruecolor(512, 512);
                $bgColor = imagecolorallocate($placeholder, 240, 240, 240);
                $textColor = imagecolorallocate($placeholder, 150, 150, 150);
                
                imagefill($placeholder, 0, 0, $bgColor);
                $text = 'AI Generated Image';
                $fontSize = 5;
                $textWidth = imagefontwidth($fontSize) * strlen($text);
                $x = (512 - $textWidth) / 2;
                $y = 250;
                
                imagestring($placeholder, $fontSize, $x, $y, $text, $textColor);
                imagepng($placeholder, storage_path('app/public/' . $storagePath));
                imagedestroy($placeholder);
            }

            $this->generatedImage = Storage::url($storagePath);

            // Log the activity
            AiActivityLogger::log(
                activityType: 'image_generated',
                model: 'imagen-4.0-generate-001',
                prompt: $this->prompt,
                output: $storagePath,
                tokenCount: mb_strlen($this->prompt) / 4, // Rough estimate
                status: 'success',
                meta: [
                    'tab' => $this->activeTab,
                    'style' => $this->imageStyle,
                    'aspect_ratio' => $this->imageAspectRatio,
                ]
            );

            session()->flash('message', 'Image generated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to generate image: ' . $e->getMessage());
            
            AiActivityLogger::log(
                activityType: 'image_generated',
                model: 'imagen-4.0-generate-001',
                prompt: $this->prompt,
                status: 'error',
                errorMessage: $e->getMessage(),
                meta: [
                    'tab' => $this->activeTab,
                    'style' => $this->imageStyle,
                    'aspect_ratio' => $this->imageAspectRatio,
                ]
            );
        } finally {
            $this->isGenerating = false;
        }
    }

    public function downloadImage()
    {
        if (!$this->generatedImage) {
            return;
        }

        $path = str_replace(url('/storage'), 'public', $this->generatedImage);
        return Storage::download($path, 'ai-generated-image.png');
    }

    public function regenerateImage()
    {
        $this->generateImage();
    }
}
