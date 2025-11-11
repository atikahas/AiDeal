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

class ProductPhotos extends Component
{
    use WithFileUploads;
    public $backgrounds = [];
    public $background = '';
    public $styles = [];
    public $style = '';
    public $lightingOptions = [];
    public $lighting = '';
    public $cameraShots = [];
    public $cameraShot = '';
    public $compositions = [];
    public $composition = '';
    public $lensTypes = [];
    public $lensType = '';
    public $filmSimulations = [];
    public $filmSimulation = '';
    public $visualEffects = [];
    public $visualEffect = '';
    public $aiCreativity = 5;
    public $image;
    public $prompt = '';
    public $showAdvancedSettings = false;
    public $imageCount = 1;
    public $generatedImages = [];
    public $selectedImageIndex = null;
    public $currentImageJobId = null;
    public $activeModel;
    public $isProcessing = false;
    public $showImageModal = false;
    public $viewingImageIndex = null;

    public function mount()
    {
        $this->initializeOptions();
    }

    protected function initializeOptions()
    {
        $this->activeModel = config('services.gemini.imagen_default_model', 'imagen-4.0-generate-preview-06-06');

        $this->backgrounds = ['Random','Studio Backdrop','Tabletop/Surface','Premium Texture','Light & Shadow','Color & Palette','Nature & Organic','Urban & Industrial','Soft Daylight Studio','Pastel Clean','High-Key White','Low-Key Black','Colorful Pop','Gradient Backdrop'];
        
        $this->styles = [
            'Photorealistic',
            'Minimalist',
            'Cinematic',
            '3D Render',
            'Watercolor',
            'Oil Painting',
            'Pencil Sketch',
            'Digital Art',
            'Vintage',
            'Neon',
            'Cyberpunk',
            'Anime',
            'Comic Book',
            'Pop Art',
            'Surreal',
            'Abstract',
            'Vaporwave',
            'Claymation',
            'Chalk Drawing',
            'Silhouette'
        ];

        $this->lightingOptions = [
            'Natural Light',
            'Studio Lighting',
            'Soft Light',
            'Dramatic Lighting',
            'Rim Light',
            'Backlit',
            'Neon Glow',
            'Moody Lighting',
            'Golden Hour',
            'Blue Hour',
            'Candlelight',
            'Sunset',
            'Sunrise',
            'Overcast',
            'Foggy',
            'Rainy Day',
            'Night',
            'Dappled Light',
            'Spotlight',
            'Bokeh'
        ];

        $this->cameraShots = [
            'Close-up' => 'Extreme close-up of the subject',
            'Medium Close-up' => 'Shows head and shoulders',
            'Medium' => 'Shows subject from waist up',
            'Medium Full' => 'Shows subject from knees up',
            'Full' => 'Shows entire subject',
            'Long' => 'Shows subject with surroundings',
            'Extreme Long' => 'Shows subject from far away',
            'Over-the-Shoulder' => 'From behind a subject',
            'Point-of-View' => 'Subject\'s perspective',
            'Aerial' => 'From above looking down',
            'Low Angle' => 'Looking up at subject',
            'High Angle' => 'Looking down on subject',
            'Dutch Angle' => 'Tilted camera angle',
            'Worm\'s-eye' => 'From ground level looking up',
            'Bird\'s-eye' => 'Directly above looking down',
            'Eye Level' => 'At subject\'s eye level',
            'Shoulder Level' => 'At subject\'s shoulder height',
            'Hip Level' => 'At subject\'s hip level',
            'Knee Level' => 'At subject\'s knee level',
            'Ground Level' => 'At ground level'
        ];

        $this->compositions = [
            'Rule of Thirds' => 'Divides frame into 9 equal parts',
            'Golden Ratio' => 'Natural, balanced composition',
            'Center' => 'Subject centered in frame',
            'Symmetrical' => 'Mirrored balance',
            'Asymmetrical' => 'Balanced but not mirrored',
            'Leading Lines' => 'Lines guide viewer\'s eye',
            'Frame Within Frame' => 'Natural framing elements',
            'Fill the Frame' => 'Subject fills most of frame',
            'Negative Space' => 'Lots of empty space',
            'Pattern' => 'Repeating elements',
            'Texture' => 'Emphasizes surface quality',
            'Color Block' => 'Bold color sections',
            'Minimalist' => 'Simple, clean composition',
            'Layered' => 'Foreground, midground, background',
            'Diagonal' => 'Strong diagonal elements',
            'S-Curve' => 'Graceful S-shaped flow',
            'Triangle' => 'Triangular arrangement',
            'Golden Triangle' => 'Diagonal with triangles',
            'Golden Spiral' => 'Natural spiral composition',
            'Radial' => 'Elements radiate from center'
        ];

        $this->lensTypes = [
            'Prime' => 'Fixed focal length, sharp images',
            'Wide Angle' => 'Wider field of view',
            'Telephoto' => 'Zoomed-in, compressed perspective',
            'Macro' => 'Extreme close-up capability',
            'Fisheye' => 'Ultra-wide, distorted view',
            'Tilt-Shift' => 'Miniature effect, perspective control',
            'Portrait' => 'Shallow depth of field',
            'Zoom' => 'Variable focal length',
            'Standard' => 'Natural perspective',
            'Anamorphic' => 'Cinematic widescreen look'
        ];

        $this->filmSimulations = [
            'Kodak Portra 400' => 'Warm, natural skin tones, fine grain',
            'Fujifilm Velvia 50' => 'Vibrant colors, high saturation',
            'Ilford HP5+ 400' => 'Classic black and white, high contrast',
            'Kodak Ektachrome 100' => 'Accurate colors, fine grain',
            'Fujifilm Pro 400H' => 'Soft colors, pastel tones',
            'Kodak Gold 200' => 'Warm tones, vintage look',
            'Cinestill 800T' => 'Cinematic tungsten balance',
            'Fujifilm Superia 400' => 'Natural colors, versatile',
            'Kodak Tri-X 400' => 'Classic high-contrast B&W',
            'Fujifilm Acros 100' => 'Fine grain B&W, smooth tones',
            'LomoChrome Purple' => 'Surreal purple color shifts',
            'Kodak Ektar 100' => 'Ultra-vibrant colors',
            'Ilford Delta 3200' => 'High-speed B&W, dramatic grain',
            'Fujifilm Natura 1600' => 'Natural colors in low light',
            'Kodak Portra 800' => 'Versatile, great for portraits',
            'Fujifilm Provia 100F' => 'Neutral colors, fine detail',
            'Agfa Vista 400' => 'Warm, nostalgic look',
            'Rollei Retro 80s' => 'Fine grain, extended red sensitivity',
            'Kodak T-Max 100' => 'Ultra-fine grain B&W',
            'Fujifilm Superia 1600' => 'High-speed color, vintage grain'
        ];

        $this->visualEffects = [
            'None' => 'No special effects',
            'Bokeh' => 'Soft out-of-focus background',
            'Motion Blur' => 'Sense of movement',
            'Tilt-Shift' => 'Miniature effect',
            'Vignette' => 'Darkened edges',
            'Grain' => 'Film grain texture',
            'HDR' => 'High dynamic range',
            'Cross Process' => 'Vintage color shift',
            'Infrared' => 'False-color effect',
            'Double Exposure' => 'Layered images',
            'Lens Flare' => 'Natural light flares',
            'Chromatic Aberration' => 'Color fringing',
            'VHS' => 'Retro video effect',
            'Glitch' => 'Digital distortion',
            'Halftone' => 'Printed dot pattern',
            'Duotone' => 'Two-tone color',
            'High Contrast' => 'Dramatic tonal range',
            'Soft Focus' => 'Dreamy, glowing effect',
            'Tilt Blur' => 'Selective focus',
            'Zoom Blur' => 'Radial motion effect'
        ];
    }

    protected function rules(): array
    {
        return [
            'prompt' => 'nullable|string|max:1000',
            'image' => 'required|file|max:2048|mimes:png,jpg,jpeg,webp',
            'imageCount' => 'required|integer|min:1|max:4',
            'aiCreativity' => 'required|integer|min:1|max:10',
            'background' => 'nullable|string|max:255',
            'style' => 'nullable|string|max:255',
            'lighting' => 'nullable|string|max:255',
            'cameraShot' => 'nullable|string|max:255',
            'composition' => 'nullable|string|max:255',
            'lensType' => 'nullable|string|max:255',
            'filmSimulation' => 'nullable|string|max:255',
            'visualEffect' => 'nullable|string|max:255',
        ];
    }

    public function generateImageProduct()
    {
        $this->prompt = trim((string) $this->prompt);

        $this->validate();
        $this->isProcessing = true;
        $this->resetErrorBag();
        $this->selectedImageIndex = null;
        $this->generatedImages = [];

        $payload = $this->buildPayload();
        $client = app(ImagenClient::class);

        $startTime = microtime(true);

        try {
            // Use editImage method since we're uploading a product image
            $images = $client->editImage($payload, $this->image);

            $this->generatedImages = $images;
            $this->selectedImageIndex = empty($images) ? null : 0;

            // Create ImageJob record
            $imageJob = ImageJob::create([
                'user_id' => auth()->id(),
                'tool' => 'product-photos',
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
                activityType: 'product_photos',
                model: $payload['model'] ?? $this->activeModel,
                prompt: $this->prompt,
                output: 'Generated ' . count($images) . ' product photo(s)',
                tokenCount: 0,
                status: 'success',
                latencyMs: $latencyMs,
                meta: [
                    'image_count' => count($images),
                    'ai_creativity' => $this->aiCreativity,
                    'background' => $this->background,
                    'style' => $this->style,
                    'image_job_id' => $imageJob->id,
                ]
            );

            session()->flash('message', __('Product photos generated successfully.'));
        } catch (\Exception $e) {
            $this->isProcessing = false;

            // Log failure to AI Activity
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            AiActivityLogger::log(
                activityType: 'product_photos',
                model: $payload['model'] ?? $this->activeModel,
                prompt: $this->prompt,
                output: null,
                tokenCount: 0,
                status: 'error',
                errorMessage: $e->getMessage(),
                latencyMs: $latencyMs,
                meta: [
                    'ai_creativity' => $this->aiCreativity,
                    'background' => $this->background,
                    'style' => $this->style,
                ]
            );

            report($e);
            session()->flash('error', __('Failed to generate product photos: :message', [
                'message' => $e->getMessage(),
            ]));
            return;
        }

        $this->isProcessing = false;
    }

    protected function buildPayload(): array
    {
        $fullPrompt = $this->getProductPhotoPrompt();

        return [
            'prompt' => $fullPrompt,
            'image_count' => $this->imageCount,
            'aspect_ratio' => '3:4',
            'model' => $this->activeModel,
            'creativity' => $this->aiCreativity,
        ];
    }

    protected function getProductPhotoPrompt(): string
    {
        // If user provided a custom prompt, use it directly
        if (!empty(trim($this->prompt))) {
            return trim($this->prompt);
        }

        // Build detailed structured prompt
        $promptParts = [
            'Create a professional, photorealistic product photo for the uploaded image.',
            'Do not include any people, models, or text. Focus only on the product itself.',
            '',
            '**Creative Direction:**',
            '- Background / Vibe: ' . ($this->background ?: 'Random'),
            '- Artistic Style: ' . ($this->style ?: 'photorealistic'),
            '- Lighting: ' . ($this->lighting ?: 'interesting, cinematic lighting'),
            '- Camera Shot: ' . ($this->cameraShot ?: 'a dynamic angle'),
            '- Composition: ' . ($this->composition ?: 'well-composed'),
            '- Lens Type: ' . ($this->lensType ?: 'standard lens'),
            '- Film Simulation: ' . ($this->filmSimulation ?: 'modern digital look'),
            '- Visual Effect: ' . (($this->visualEffect && $this->visualEffect !== 'None') ? $this->visualEffect : 'none'),
            '- AI Creativity Level: ' . $this->aiCreativity . ' out of 10 (0 = literal, 10 = full artistic freedom)',
            '',
            '**Final Requirements:**',
            '- The result must be clean, aesthetic, and suitable for e-commerce listings or social media.',
            '- The output image must have a 3:4 aspect ratio.',
            '- CRITICAL: The final image must be purely visual. Do NOT add text, watermarks, or logos.',
        ];

        return implode("\n", $promptParts);
    }

    public function resetForm(): void
    {
        $this->reset([
            'prompt',
            'image',
            'imageCount',
            'aiCreativity',
            'background',
            'style',
            'lighting',
            'cameraShot',
            'composition',
            'lensType',
            'filmSimulation',
            'visualEffect',
            'generatedImages',
            'selectedImageIndex',
            'currentImageJobId',
        ]);

        $this->imageCount = 1;
        $this->aiCreativity = 5;
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
                'ai-images/%s/product-photo_%s_%d.png',
                $userId,
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

            session()->flash('message', __('Product photo saved successfully.'));

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', __('Failed to save the product photo.'));
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
            'product-photo-%s-%d.png',
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
        return view('livewire.ai-image-idea-suite.product-photos');
    }
}
