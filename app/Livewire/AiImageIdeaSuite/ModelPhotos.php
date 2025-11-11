<?php

namespace App\Livewire\AiImageIdeaSuite;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ImageJob;
use App\Services\Ai\ImagenClient;
use App\Services\AiActivityLogger;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;

class ModelPhotos extends Component
{
    use WithFileUploads;

    public $productImage;
    public $modelFaceImage;
    public $prompt = '';
    public $modelGender = '';
    public $modelEthnicity = '';
    public $artisticStyle = '';
    public $lighting = '';
    public $cameraShot = '';
    public $bodyPose = '';
    public $backgroundVibe = '';
    public $composition = '';
    public $lensType = '';
    public $filmSimulation = '';
    public $aiCreativity = 5;
    public $imageCount = 1;
    public $generatedImages = [];
    public $selectedImageIndex = null;
    public $currentImageJobId = null;
    public $activeModel;
    public $isProcessing = false;
    public $showAdvancedSettings = false;

    // Options arrays
    public $ethnicities = [];
    public $styles = [];
    public $lightingOptions = [];
    public $cameraShots = [];
    public $bodyPoses = [];
    public $backgroundVibes = [];
    public $compositions = [];
    public $lensTypes = [];
    public $filmSimulations = [];

    public function mount()
    {
        $this->initializeOptions();
    }

    protected function initializeOptions()
    {
        $this->activeModel = config('services.gemini.imagen_default_model', 'imagen-4.0-generate-preview-06-06');

        $this->ethnicities = [
            'Random',
            'Malay',
            'Chinese Malaysian',
            'Indian Malaysian',
            'Bumiputera',
            'Orang Asli',
            'Kadazan-Dusun',
            'Iban',
            'Bidayuh',
            'Eurasian',
            'Mixed',
        ];

        $this->styles = [
            'Random',
            'Photorealistic',
            'Fashion Editorial',
            'Commercial',
            'Lifestyle',
            'High Fashion',
            'Street Style',
            'Vintage',
            'Modern',
            'Cinematic',
            'Natural',
            'Glamour',
            'Minimalist',
        ];

        $this->lightingOptions = [
            'Random',
            'Natural Light',
            'Studio Lighting',
            'Soft Light',
            'Dramatic Lighting',
            'Rim Light',
            'Backlit',
            'Golden Hour',
            'Sunset',
            'Overcast',
            'Ring Light',
            'Butterfly Lighting',
            'Rembrandt Lighting',
            'Split Lighting',
        ];

        $this->cameraShots = [
            'Random',
            'Full Body',
            'Three Quarter',
            'Half Body',
            'Head and Shoulders',
            'Close-up',
            'Wide Shot',
            'Medium Shot',
            'Over-the-Shoulder',
        ];

        $this->bodyPoses = [
            'Random',
            'Standing',
            'Walking',
            'Sitting',
            'Leaning',
            'Dynamic',
            'Relaxed',
            'Confident',
            'Casual',
            'Formal',
            'Fashion Pose',
            'Natural Pose',
        ];

        $this->backgroundVibes = [
            'Random',
            'Studio Backdrop',
            'Urban Street',
            'Nature/Outdoor',
            'Modern Interior',
            'Vintage Setting',
            'Industrial',
            'Minimalist White',
            'Colorful',
            'Textured',
            'Gradient',
        ];

        $this->compositions = [
            'Random',
            'Rule of Thirds',
            'Center',
            'Golden Ratio',
            'Symmetrical',
            'Asymmetrical',
            'Leading Lines',
            'Frame Within Frame',
            'Negative Space',
        ];

        $this->lensTypes = [
            'Random',
            '35mm',
            '50mm',
            '85mm Portrait',
            '135mm',
            'Wide Angle',
            'Telephoto',
        ];

        $this->filmSimulations = [
            'Random',
            'Kodak Portra 400',
            'Fuji Pro 400H',
            'Kodak Gold',
            'Ilford HP5',
            'Cinestill 800T',
            'Digital Modern',
        ];
    }

    protected function rules(): array
    {
        $rules = [
            'productImage' => 'required|file|max:2048|mimes:png,jpg,jpeg,webp',
            'modelFaceImage' => 'nullable|file|max:2048|mimes:png,jpg,jpeg,webp',
            'prompt' => 'nullable|string|max:1000',
            'imageCount' => 'required|integer|min:1|max:4',
            'aiCreativity' => 'required|integer|min:1|max:10',
            'modelGender' => 'nullable|in:female,male',
            'modelEthnicity' => 'nullable|string|max:255',
            'artisticStyle' => 'nullable|string|max:255',
            'lighting' => 'nullable|string|max:255',
            'cameraShot' => 'nullable|string|max:255',
            'bodyPose' => 'nullable|string|max:255',
            'backgroundVibe' => 'nullable|string|max:255',
            'composition' => 'nullable|string|max:255',
            'lensType' => 'nullable|string|max:255',
            'filmSimulation' => 'nullable|string|max:255',
        ];

        // Require gender selection when no model face is uploaded
        if (!$this->modelFaceImage) {
            $rules['modelGender'] = 'required|in:female,male';
        }

        return $rules;
    }

    public function generateModelPhotos()
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
            // Use editImage method with product image and optional model face image
            $images = $client->editImage($payload, $this->productImage, $this->modelFaceImage);

            $this->generatedImages = $images;
            $this->selectedImageIndex = empty($images) ? null : 0;

            // Create ImageJob record
            $imageJob = ImageJob::create([
                'user_id' => auth()->id(),
                'tool' => 'model-photos',
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
                activityType: 'model_photos',
                model: $payload['model'] ?? $this->activeModel,
                prompt: $this->getPromptForLogging(),
                output: 'Generated ' . count($images) . ' model photo(s)',
                tokenCount: 0,
                status: 'success',
                latencyMs: $latencyMs,
                meta: [
                    'image_count' => count($images),
                    'ai_creativity' => $this->aiCreativity,
                    'model_gender' => $this->modelGender,
                    'image_job_id' => $imageJob->id,
                ]
            );

            session()->flash('message', __('Model photos generated successfully.'));
        } catch (\Exception $e) {
            $this->isProcessing = false;

            // Log failure to AI Activity
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            AiActivityLogger::log(
                activityType: 'model_photos',
                model: $payload['model'] ?? $this->activeModel,
                prompt: $this->getPromptForLogging(),
                output: null,
                tokenCount: 0,
                status: 'error',
                errorMessage: $e->getMessage(),
                latencyMs: $latencyMs,
                meta: [
                    'ai_creativity' => $this->aiCreativity,
                    'model_gender' => $this->modelGender,
                ]
            );

            report($e);
            session()->flash('error', __('Failed to generate model photos: :message', [
                'message' => $e->getMessage(),
            ]));
            return;
        }

        $this->isProcessing = false;
    }

    protected function buildPayload(): array
    {
        $fullPrompt = $this->getModelPhotoPrompt();

        return [
            'prompt' => $fullPrompt,
            'image_count' => $this->imageCount,
            'aspect_ratio' => '3:4',
            'model' => $this->activeModel,
            'creativity' => $this->aiCreativity,
        ];
    }

    protected function getModelPhotoPrompt(): string
    {
        $promptParts = [];

        // Always add context about images when model face is uploaded
        if ($this->modelFaceImage) {
            $promptParts[] = 'IMPORTANT: You are given two images:';
            $promptParts[] = '1. The FIRST image is the PRODUCT (item/clothing/accessory/makeup) that the model should wear or showcase.';
            $promptParts[] = '2. The SECOND image shows the MODEL\'S FACE that MUST be used in the generated photo.';
            $promptParts[] = '';
        }

        // If user provided a custom prompt, add it after the image context
        if (!empty(trim($this->prompt))) {
            if ($this->modelFaceImage) {
                $promptParts[] = 'TASK: ' . trim($this->prompt);
                $promptParts[] = '';
                $promptParts[] = 'CRITICAL: Use the face from the second image and make the model wear/showcase the product from the first image.';
            } else {
                $promptParts[] = trim($this->prompt);
            }
            return implode("\n", $promptParts);
        }

        // Build detailed structured prompt
        if ($this->modelFaceImage) {
            $promptParts[] = 'TASK: Create a professional, photorealistic photo of a model with the face from the second image, wearing or showcasing the product from the first image.';
            $promptParts[] = 'The model should naturally display the product in a lifestyle or fashion context.';
        } else {
            $promptParts[] = 'Create a professional, photorealistic model photo wearing or showcasing the uploaded product.';
            $promptParts[] = 'The model should naturally display the product in a lifestyle or fashion context.';
        }

        $promptParts[] = '';
        $promptParts[] = '**Model Specifications:**';

        // Only include gender and ethnicity if no model face image is uploaded
        if (!$this->modelFaceImage) {
            $promptParts[] = '- Gender: ' . ucfirst($this->modelGender);
            $promptParts[] = '- Ethnicity: ' . ($this->modelEthnicity ?: 'diverse');
        }

        $promptParts = array_merge($promptParts, [
            '- Body Pose: ' . ($this->bodyPose ?: 'natural and confident'),
            '',
            '**Creative Direction:**',
            '- Artistic Style: ' . ($this->artisticStyle ?: 'photorealistic fashion'),
            '- Lighting: ' . ($this->lighting ?: 'professional studio lighting'),
            '- Camera Shot: ' . ($this->cameraShot ?: 'full body or three quarter shot'),
            '- Background/Vibe: ' . ($this->backgroundVibe ?: 'clean and professional'),
            '- Composition: ' . ($this->composition ?: 'well-composed'),
            '- Lens Type: ' . ($this->lensType ?: 'portrait lens'),
            '- Film Simulation: ' . ($this->filmSimulation ?: 'modern digital look'),
            '- AI Creativity Level: ' . $this->aiCreativity . ' out of 10 (0 = literal, 10 = full artistic freedom)',
            '',
            '**Final Requirements:**',
            '- The model must look natural and professional',
            '- The product should be clearly visible and well-integrated into the scene',
            '- The output image must have a 3:4 aspect ratio (portrait orientation)',
            '- CRITICAL: The final image must be purely visual. Do NOT add text, watermarks, or logos.',
        ]);

        return implode("\n", $promptParts);
    }

    protected function getPromptForLogging(): string
    {
        if (!empty(trim($this->prompt))) {
            return trim($this->prompt);
        }

        if ($this->modelFaceImage) {
            return sprintf(
                'Model with custom face wearing product - %s style, %s lighting',
                $this->artisticStyle ?: 'fashion',
                $this->lighting ?: 'professional'
            );
        }

        return sprintf(
            '%s model wearing product - %s style, %s lighting',
            $this->modelGender ? ucfirst($this->modelGender) : 'Model',
            $this->artisticStyle ?: 'fashion',
            $this->lighting ?: 'professional'
        );
    }

    public function resetForm(): void
    {
        $this->reset([
            'productImage',
            'modelFaceImage',
            'prompt',
            'modelEthnicity',
            'artisticStyle',
            'lighting',
            'cameraShot',
            'bodyPose',
            'backgroundVibe',
            'composition',
            'lensType',
            'filmSimulation',
            'generatedImages',
            'selectedImageIndex',
            'currentImageJobId',
        ]);

        $this->imageCount = 1;
        $this->aiCreativity = 5;
        $this->modelGender = '';
    }

    public function updatedProductImage(): void
    {
        if ($this->productImage) {
            try {
                $this->validateOnly('productImage');
            } catch (\Exception $e) {
                $this->productImage = null;
                throw $e;
            }
        }
    }

    public function updatedModelFaceImage(): void
    {
        if ($this->modelFaceImage) {
            try {
                $this->validateOnly('modelFaceImage');
            } catch (\Exception $e) {
                $this->modelFaceImage = null;
                throw $e;
            }
        }
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
                'ai-images/%s/model-photo_%s_%d.png',
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

            session()->flash('message', __('Model photo saved successfully.'));

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', __('Failed to save the model photo.'));
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
            'model-photo-%s-%d.png',
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
        return view('livewire.ai-image-idea-suite.model-photos');
    }
}
