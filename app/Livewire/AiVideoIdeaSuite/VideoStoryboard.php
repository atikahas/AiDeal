<?php

namespace App\Livewire\AiVideoIdeaSuite;

use App\Models\VideoJob;
use App\Services\Ai\ImagenClient;
use App\Services\Ai\VeoClient;
use App\Services\AiActivityLogger;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class VideoStoryboard extends Component
{
    use WithFileUploads;

    public $videoIdea = '';

    public $productName = '';

    public $productDescription = '';

    public $productPhoto;

    public $modelPhoto;

    public $sceneCount = 3;

    public $language = 'English';

    public $storyboardOutput = [];

    public $currentVideoJobId = null;

    public $isMagicPromptProcessing = false;

    public $isGenerating = false;

    public $generatingImageForScene = null;

    public $generatingVideoForScene = null;

    public $editingScene = null;

    public array $sceneCounts = [1, 2, 3, 4];

    public array $languages = ['English', 'Malay', 'Chinese', 'Tamil'];

    protected \App\Services\GeminiService $geminiService;

    protected ImagenClient $imagenClient;

    protected VeoClient $veoClient;

    public function boot(
        \App\Services\GeminiService $geminiService,
        ImagenClient $imagenClient,
        VeoClient $veoClient
    ) {
        $this->geminiService = $geminiService;
        $this->imagenClient = $imagenClient;
        $this->veoClient = $veoClient;
    }

    protected function rules(): array
    {
        return [
            'videoIdea' => 'required|string|min:10|max:1000',
            'productName' => 'nullable|string|max:200',
            'productDescription' => 'nullable|string|max:500',
            'productPhoto' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'modelPhoto' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'sceneCount' => 'required|integer|in:1,2,3,4',
            'language' => 'required|string|in:English,Malay,Chinese,Tamil',
        ];
    }

    public function generateMagicPrompt()
    {
        if (empty($this->videoIdea)) {
            session()->flash('error', __('Please enter a video idea or concept first.'));

            return;
        }

        $this->isMagicPromptProcessing = true;

        try {
            $enhancementPrompt = "Transform this simple video idea into a concise storyboard concept.\n\n";
            $enhancementPrompt .= "Input: {$this->videoIdea}\n\n";
            $enhancementPrompt .= "Create a {$this->sceneCount}-scene video storyboard concept with:\n";
            $enhancementPrompt .= "- Brief scene-by-scene breakdown\n";
            $enhancementPrompt .= "- Key visual elements\n";
            $enhancementPrompt .= "- Camera shots\n";
            $enhancementPrompt .= "- Scene transitions\n\n";
            $enhancementPrompt .= "Output in {$this->language} language.\n\n";
            $enhancementPrompt .= 'Write a comprehensive but concise video concept (2-3 sentences per scene, maximum 800 characters total).';

            $enhancedPrompt = $this->geminiService->generateContent($enhancementPrompt, [
                'temperature' => 0.7,
                'language' => $this->language,
            ]);

            if ($enhancedPrompt) {
                $trimmedPrompt = trim($enhancedPrompt);

                // Ensure it doesn't exceed 1000 characters
                if (strlen($trimmedPrompt) > 1000) {
                    $trimmedPrompt = substr($trimmedPrompt, 0, 997).'...';
                    session()->flash('message', __('Magic prompt generated and trimmed to fit character limit.'));
                } else {
                    session()->flash('message', __('Magic prompt generated successfully!'));
                }

                $this->videoIdea = $trimmedPrompt;
            } else {
                throw new \Exception('No enhanced prompt was generated');
            }
        } catch (\Exception $e) {
            \Log::error('Magic prompt generation failed', [
                'error' => $e->getMessage(),
                'original_idea' => $this->videoIdea,
            ]);

            session()->flash('error', __('Failed to generate magic prompt: :message', [
                'message' => $e->getMessage(),
            ]));
        }

        $this->isMagicPromptProcessing = false;
    }

    public function generateStoryboard()
    {
        $this->validate();
        $this->isGenerating = true;
        $this->storyboardOutput = [];
        $startTime = microtime(true);

        try {
            $storyboardPrompt = $this->buildStoryboardPrompt();

            $response = $this->geminiService->generateContent($storyboardPrompt, [
                'temperature' => 0.8,
                'language' => $this->language,
            ]);

            if ($response) {
                // Parse the response into structured scenes
                $scenes = $this->parseStoryboardResponse($response);

                if (empty($scenes)) {
                    throw new \Exception('Failed to generate storyboard scenes');
                }

                // Add status and generation tracking to each scene
                foreach ($scenes as &$scene) {
                    $scene['image_status'] = 'pending'; // pending, generating, completed, failed
                    $scene['video_status'] = 'pending';
                    $scene['image_url'] = null;
                    $scene['video_url'] = null;
                    $scene['image_path'] = null;
                    $scene['video_path'] = null;
                }

                $this->storyboardOutput = $scenes;

                // Create VideoJob record
                $videoJob = VideoJob::create([
                    'user_id' => auth()->id(),
                    'tool' => 'video-storyboard',
                    'input_json' => [
                        'video_idea' => $this->videoIdea,
                        'product_name' => $this->productName,
                        'product_description' => $this->productDescription,
                        'scene_count' => $this->sceneCount,
                        'language' => $this->language,
                        'scenes' => $scenes,
                    ],
                    'status' => 'completed',
                    'is_saved' => false,
                    'started_at' => now(),
                    'finished_at' => now(),
                ]);

                $this->currentVideoJobId = $videoJob->id;

                // Log to AI Activity
                $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
                AiActivityLogger::log(
                    activityType: 'storyboard_generation',
                    model: 'gemini-2.5-flash',
                    prompt: $this->videoIdea,
                    output: 'Generated '.count($scenes).' scene(s)',
                    tokenCount: 0,
                    status: 'success',
                    latencyMs: $latencyMs,
                    meta: [
                        'scene_count' => count($scenes),
                        'language' => $this->language,
                        'video_job_id' => $videoJob->id,
                    ]
                );

                session()->flash('message', __('Storyboard generated successfully! Now you can generate images for each scene.'));
            } else {
                throw new \Exception('No storyboard was generated');
            }
        } catch (\Exception $e) {
            \Log::error('Storyboard generation failed', [
                'error' => $e->getMessage(),
                'video_idea' => $this->videoIdea,
            ]);

            session()->flash('error', __('Failed to generate storyboard: :message', [
                'message' => $e->getMessage(),
            ]));
        }

        $this->isGenerating = false;
    }

    protected function buildStoryboardPrompt(): string
    {
        $prompt = "Create a detailed video storyboard with exactly {$this->sceneCount} scenes.\n\n";

        $prompt .= "**Video Concept:**\n{$this->videoIdea}\n\n";

        if (! empty($this->productName)) {
            $prompt .= "**Product:** {$this->productName}\n";
        }

        if (! empty($this->productDescription)) {
            $prompt .= "**Product Description:** {$this->productDescription}\n";
        }

        if ($this->productPhoto) {
            $prompt .= "**Note:** A product photo has been provided for reference. Incorporate this product into the scenes.\n";
        }

        if ($this->modelPhoto) {
            $prompt .= "**Note:** A model/person photo has been provided for reference. Feature this person in the scenes.\n";
        }

        $prompt .= "\n**Instructions:**\n";
        $prompt .= "Generate exactly {$this->sceneCount} distinct scenes for this video storyboard.\n";
        $prompt .= "Output in {$this->language} language.\n\n";

        $prompt .= "For each scene, provide:\n";
        $prompt .= "1. Scene Title (brief, catchy)\n";
        $prompt .= "2. Visual Description (what we see: setting, subjects, actions)\n";
        $prompt .= "3. Camera Shot (e.g., close-up, wide shot, tracking shot)\n";
        $prompt .= "4. Duration (suggested seconds for this scene)\n";
        $prompt .= "5. Audio/Dialogue (music, voiceover, or sound effects)\n";
        $prompt .= "6. Transition (how it connects to next scene)\n\n";

        $prompt .= "**Output Format (use exactly this structure):**\n";
        $prompt .= "SCENE 1: [Title]\n";
        $prompt .= "Visual: [Description]\n";
        $prompt .= "Camera: [Shot type]\n";
        $prompt .= "Duration: [X seconds]\n";
        $prompt .= "Audio: [Audio description]\n";
        $prompt .= "Transition: [Transition description]\n\n";

        $prompt .= "Repeat for all {$this->sceneCount} scenes. Be creative and founder-friendly!";

        return $prompt;
    }

    protected function parseStoryboardResponse(string $response): array
    {
        $scenes = [];

        // Split by scene markers
        preg_match_all('/SCENE\s+(\d+):\s*(.+?)(?=SCENE\s+\d+:|$)/s', $response, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $sceneNumber = (int) $match[1];
            $sceneContent = trim($match[2]);

            $scene = [
                'number' => $sceneNumber,
                'title' => '',
                'visual' => '',
                'camera' => '',
                'duration' => '',
                'audio' => '',
                'transition' => '',
            ];

            // Extract title (first line or from the SCENE marker)
            if (preg_match('/^(.+?)(?:\n|Visual:)/i', $sceneContent, $titleMatch)) {
                $scene['title'] = trim($titleMatch[1]);
            }

            // Extract visual description
            if (preg_match('/Visual:\s*(.+?)(?=Camera:|Duration:|Audio:|Transition:|$)/is', $sceneContent, $visualMatch)) {
                $scene['visual'] = trim($visualMatch[1]);
            }

            // Extract camera shot
            if (preg_match('/Camera:\s*(.+?)(?=Duration:|Audio:|Transition:|$)/is', $sceneContent, $cameraMatch)) {
                $scene['camera'] = trim($cameraMatch[1]);
            }

            // Extract duration
            if (preg_match('/Duration:\s*(.+?)(?=Audio:|Transition:|$)/is', $sceneContent, $durationMatch)) {
                $scene['duration'] = trim($durationMatch[1]);
            }

            // Extract audio
            if (preg_match('/Audio:\s*(.+?)(?=Transition:|$)/is', $sceneContent, $audioMatch)) {
                $scene['audio'] = trim($audioMatch[1]);
            }

            // Extract transition
            if (preg_match('/Transition:\s*(.+?)$/is', $sceneContent, $transitionMatch)) {
                $scene['transition'] = trim($transitionMatch[1]);
            }

            $scenes[] = $scene;
        }

        return $scenes;
    }

    public function generateImageForScene($sceneIndex)
    {
        if (! isset($this->storyboardOutput[$sceneIndex])) {
            session()->flash('error', __('Scene not found.'));

            return;
        }

        $this->generatingImageForScene = $sceneIndex;
        $scene = $this->storyboardOutput[$sceneIndex];
        $startTime = microtime(true);

        try {
            // Build prompt for image generation
            $imagePrompt = $this->buildSceneImagePrompt($scene);

            // Generate image using ImagenClient
            $payload = [
                'prompt' => $imagePrompt,
                'image_count' => 1,
                'aspect_ratio' => '16:9',
                'model' => config('services.gemini.imagen_default_model'),
            ];

            $images = $this->productPhoto
                ? $this->imagenClient->editImage($payload, $this->productPhoto)
                : $this->imagenClient->textToImage($payload);

            if (! empty($images[0]['data'])) {
                // Save image to storage
                $userId = auth()->id();
                $timestamp = now()->format('Y-m-d_H-i-s');
                $filename = sprintf('ai-videos/%s/scene_%d_image_%s.png', $userId, $sceneIndex + 1, $timestamp);

                $imageData = base64_decode($images[0]['data']);
                Storage::disk('public')->put($filename, $imageData);

                // Update scene with image data
                $this->storyboardOutput[$sceneIndex]['image_status'] = 'completed';
                $this->storyboardOutput[$sceneIndex]['image_url'] = Storage::disk('public')->url($filename);
                $this->storyboardOutput[$sceneIndex]['image_path'] = $filename;

                // Update VideoJob
                $this->updateVideoJobScenes();

                // Log activity
                $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
                AiActivityLogger::log(
                    activityType: 'scene_image_generation',
                    model: $payload['model'],
                    prompt: $imagePrompt,
                    output: 'Generated image for scene '.($sceneIndex + 1),
                    tokenCount: 0,
                    status: 'success',
                    latencyMs: $latencyMs,
                    meta: [
                        'scene_index' => $sceneIndex,
                        'video_job_id' => $this->currentVideoJobId,
                    ]
                );

                session()->flash('message', __('Image generated successfully for scene :scene!', ['scene' => $sceneIndex + 1]));
            }
        } catch (\Exception $e) {
            $this->storyboardOutput[$sceneIndex]['image_status'] = 'failed';
            \Log::error('Scene image generation failed', [
                'error' => $e->getMessage(),
                'scene_index' => $sceneIndex,
            ]);

            session()->flash('error', __('Failed to generate image: :message', ['message' => $e->getMessage()]));
        }

        $this->generatingImageForScene = null;
    }

    public function generateVideoForScene($sceneIndex)
    {
        if (! isset($this->storyboardOutput[$sceneIndex])) {
            session()->flash('error', __('Scene not found.'));

            return;
        }

        if (empty($this->storyboardOutput[$sceneIndex]['image_url'])) {
            session()->flash('error', __('Please generate an image for this scene first.'));

            return;
        }

        $this->generatingVideoForScene = $sceneIndex;
        $scene = $this->storyboardOutput[$sceneIndex];
        $startTime = microtime(true);

        try {
            // Build prompt for video generation
            $videoPrompt = $this->buildSceneVideoPrompt($scene);

            // Extract duration from scene (default to 4 seconds)
            $duration = 4;
            if (preg_match('/(\d+)\s*(?:second|sec|s)/i', $scene['duration'] ?? '', $matches)) {
                $duration = min(max((int) $matches[1], 4), 8); // Clamp between 4-8 seconds
            }

            $payload = [
                'prompt' => $videoPrompt,
                'duration' => $duration,
                'aspectRatio' => '16:9',
                'model' => 'veo-3.1-generate-preview',
            ];

            // Get the image file for reference
            $imagePath = Storage::disk('public')->path($scene['image_path']);
            $referenceImage = new \Illuminate\Http\UploadedFile(
                $imagePath,
                basename($imagePath),
                'image/png',
                null,
                true
            );

            $videos = $this->veoClient->imageToVideo($payload, $referenceImage);

            if (! empty($videos[0]['data'])) {
                // Save video to storage
                $userId = auth()->id();
                $timestamp = now()->format('Y-m-d_H-i-s');
                $filename = sprintf('ai-videos/%s/scene_%d_video_%s.mp4', $userId, $sceneIndex + 1, $timestamp);

                $videoData = base64_decode($videos[0]['data']);
                Storage::disk('public')->put($filename, $videoData);

                // Update scene with video data
                $this->storyboardOutput[$sceneIndex]['video_status'] = 'completed';
                $this->storyboardOutput[$sceneIndex]['video_url'] = Storage::disk('public')->url($filename);
                $this->storyboardOutput[$sceneIndex]['video_path'] = $filename;

                // Update VideoJob
                $this->updateVideoJobScenes();

                // Log activity
                $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
                AiActivityLogger::log(
                    activityType: 'scene_video_generation',
                    model: 'veo-3.1-generate-preview',
                    prompt: $videoPrompt,
                    output: 'Generated video for scene '.($sceneIndex + 1),
                    tokenCount: 0,
                    status: 'success',
                    latencyMs: $latencyMs,
                    meta: [
                        'scene_index' => $sceneIndex,
                        'duration' => $duration,
                        'video_job_id' => $this->currentVideoJobId,
                    ]
                );

                session()->flash('message', __('Video generated successfully for scene :scene!', ['scene' => $sceneIndex + 1]));
            }
        } catch (\Exception $e) {
            $this->storyboardOutput[$sceneIndex]['video_status'] = 'failed';
            \Log::error('Scene video generation failed', [
                'error' => $e->getMessage(),
                'scene_index' => $sceneIndex,
            ]);

            session()->flash('error', __('Failed to generate video: :message', ['message' => $e->getMessage()]));
        }

        $this->generatingVideoForScene = null;
    }

    protected function buildSceneImagePrompt(array $scene): string
    {
        $prompt = "Create a cinematic video frame for this scene:\n\n";
        $prompt .= "Scene: {$scene['title']}\n";
        $prompt .= "Visual: {$scene['visual']}\n";
        $prompt .= "Camera: {$scene['camera']}\n\n";

        if (! empty($this->productName)) {
            $prompt .= "Product: {$this->productName}\n";
        }

        if (! empty($this->productDescription)) {
            $prompt .= "Description: {$this->productDescription}\n";
        }

        $prompt .= "\nCreate a high-quality, cinematic 16:9 frame that captures this moment. ";
        $prompt .= 'Focus on composition, lighting, and visual storytelling.';

        return $prompt;
    }

    protected function buildSceneVideoPrompt(array $scene): string
    {
        $prompt = "Animate this scene: {$scene['title']}\n\n";
        $prompt .= "Action: {$scene['visual']}\n";
        $prompt .= "Camera: {$scene['camera']}\n";

        if (! empty($scene['audio'])) {
            $prompt .= "Mood: {$scene['audio']}\n";
        }

        $prompt .= "\nCreate smooth, professional video animation with natural movement and transitions.";

        return $prompt;
    }

    protected function updateVideoJobScenes(): void
    {
        if ($this->currentVideoJobId) {
            $videoJob = VideoJob::find($this->currentVideoJobId);
            if ($videoJob) {
                $videoJob->update([
                    'input_json' => array_merge(
                        $videoJob->input_json ?? [],
                        ['scenes' => $this->storyboardOutput]
                    ),
                ]);
            }
        }
    }

    public function editScene($sceneIndex)
    {
        $this->editingScene = $sceneIndex;
    }

    public function saveSceneEdit($sceneIndex)
    {
        $this->editingScene = null;
        $this->updateVideoJobScenes();
        session()->flash('message', __('Scene updated successfully!'));
    }

    public function cancelSceneEdit()
    {
        $this->editingScene = null;
    }

    public function regenerateImage($sceneIndex)
    {
        $this->generateImageForScene($sceneIndex);
    }

    public function regenerateVideo($sceneIndex)
    {
        $this->generateVideoForScene($sceneIndex);
    }

    public function resetForm()
    {
        $this->reset([
            'videoIdea',
            'productName',
            'productDescription',
            'productPhoto',
            'modelPhoto',
            'sceneCount',
            'language',
            'storyboardOutput',
            'currentVideoJobId',
        ]);

        $this->sceneCount = 3;
        $this->language = 'English';
    }

    public function render()
    {
        return view('livewire.ai-video-idea-suite.video-storyboard');
    }
}
