<?php

use App\Services\GeminiService;
use App\Services\AiActivityLogger;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Volt\Component;

new class extends Component {
    use WithFileUploads;

    public string $activeTab = 'image-generation';
    public bool $showAdvancedSettings = false;
    public ?string $style = null;
    public ?string $lighting = null;
    public ?string $angle = null;
    public ?string $composition = null;
    public ?string $lensType = null;
    public ?string $filmSimulation = null;
    public $imageGenerationPhoto;
    public string $prompt = '';
    public array $generatedImages = [];
    public ?int $selectedImageIndex = null;
    public int $imageCount = 1;
    
    public array $styles = [
        'Photography',
        '3D',
        'Anime',
        'Cinematic',
        'Comic',
        'Digital Art',
        'Fantasy Art',
        'Line Art',
        'Low Poly',
        'Neo Punk',
        'Origami',
        'Pixel Art',
        'Texture'
    ];
    
    public array $compositions = [
        'Rule of Thirds',
        'Center Composition',
        'Leading Lines',
        'Frame Within a Frame',
        'Symmetry and Patterns'
    ];
    
    public array $lightingOptions = [
        'Natural',
        'Studio',
        'Golden Hour',
        'Sunset',
        'Overcast',
        'High Contrast',
        'Low Key',
        'Backlit',
        'Dramatic',
        'Soft'
    ];

    public array $angleOptions = [
        'Front',
        'Side',
        'Top Down',
        'Close-up',
        'Wide Angle',
        'Low Angle',
        'High Angle',
        'Over the Shoulder',
        'Eye Level',
        'Dutch Angle'
    ];
    
    public array $lensTypes = [
        'Prime',
        'Wide Angle',
        'Telephoto',
        'Macro',
        'Fisheye',
        'Tilt-Shift',
        'Zoom',
        'Portrait'
    ];

    public array $filmSimulations = [
        'Kodak Portra 400',
        'Fujifilm Provia',
        'Kodak Ektar 100',
        'Fujifilm Velvia 50',
        'Ilford HP5+ 400',
        'Kodak Tri-X 400',
        'Fujifilm Superia 400',
        'Kodak Gold 200',
        'Cinestill 800T',
        'Fujifilm Acros 100'
    ];

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
    
    public function updatedImageGenerationPhoto()
    {
        $this->validateOnly('imageGenerationPhoto', [
            'imageGenerationPhoto' => 'image|max:5120', // 5MB Max
        ]);
    }

    public function generateOrEditImage()
    {
        session()->flash('info', 'Image generation functionality will be implemented soon');
    }

    public function resetForm()
    {
        $this->reset(['imageGenerationPhoto', 'prompt', 'generatedImage']);
    }
};
?>

<main class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    @if (session()->has('error'))
        <div class="rounded-md bg-red-50 p-4 dark:bg-red-900/30">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</h3>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('success'))
        <div class="rounded-md bg-green-50 p-4 dark:bg-green-900/30">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</h3>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Image Suite') }}</h1>
        <p class="max-w-4xl text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Generate, enhance, and transform images with AI. Create stunning visuals, product photos, and more with just a few clicks.') }}
        </p>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap gap-2">
            @foreach([
                'image-generation' => __('Image Generation'),
                'product-photos' => __('Product Photos'),
                'model-photos' => __('Model Photos'),
                'image-enhancer' => __('Image Enhancer'),
                'background-remover' => __('Background Remover'),
            ] as $tabKey => $label)
                <button
                    type="button"
                    wire:click="setActiveTab('{{ $tabKey }}')"
                    @class([
                        'rounded-lg px-4 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500',
                        'bg-zinc-900 text-white shadow-sm dark:bg-zinc-100 dark:text-zinc-900' => $activeTab === $tabKey,
                        'bg-transparent text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100' => $activeTab !== $tabKey,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Tab Content -->
    <div class="grid gap-6 lg:grid-cols-[360px,1fr] xl:grid-cols-[380px,1fr]">
        @if ($activeTab === 'image-generation')
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Image Generator') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Generate images from text or edit existing photos with AI.') }}
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="product-photo">{{ __('Reference / Source Images (up to 5)') }}</label>
                            <label for="image-generation-photo" class="flex min-h-[140px] cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 text-sm text-zinc-500 transition hover:border-zinc-400 hover:text-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-zinc-200">
                                <flux:icon.photo variant="outline" class="mb-3 size-10 text-zinc-300" />
                                <span class="text-sm font-medium">{{ __('Upload image') }}</span>
                                <span class="mt-1 text-xs text-zinc-400">{{ __('PNG or JPG up to 5MB') }}</span>
                            </label>
                            <input id="image-generation-photo" type="file" class="hidden" wire:model.live="imageGenerationPhoto" accept="image/*" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="staff-input">
                                {{ __('Prompt') }}
                            </label>
                            <textarea id="prompt" wire:model.live="prompt" rows="4" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" placeholder="{{ __('Describe the image you want to generate...') }}"></textarea>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="image-count">
                                    {{ __('Number of Images') }}
                                </label>
                                <select id="image-count" wire:model.live="imageCount" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'Image' : 'Images' }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="aspect-ratio">
                                    {{ __('Aspect Ratio') }}
                                </label>
                                <select id="aspect-ratio" wire:model.live="aspectRatio" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                    <option value="1:1" selected>1:1 Square</option>
                                    <option value="4:3">4:3 Standard</option>
                                    <option value="16:9">16:9 Widescreen</option>
                                    <option value="9:16">9:16 Portrait</option>
                                    <option value="3:2">3:2 Classic 35mm</option>
                                </select>
                            </div>
                        </div>
                        <div class="">
                            <button type="button" wire:click="$toggle('showAdvancedSettings')" class="inline-flex items-center text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                                {{ __('Advanced Settings') }}
                                <svg class="ml-1.5 h-4 w-4 transition-transform duration-200 {{ $showAdvancedSettings ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                        <div x-show="$wire.showAdvancedSettings" x-collapse class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="style">{{ __('Style') }}</label>
                                    <select id="style" wire:model="style" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                        <option value="">-- Select --</option>
                                        @foreach($styles as $styleOption)
                                            <option value="{{ $styleOption }}">{{ $styleOption }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="lighting">{{ __('Lighting') }}</label>
                                    <select id="lighting" wire:model="lighting" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                        <option value="">-- Select --</option>
                                        @foreach($lightingOptions as $lightingOption)
                                            <option value="{{ $lightingOption }}">{{ $lightingOption }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="angle">{{ __('Angle') }}</label>
                                    <select id="angle" wire:model="angle" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                        <option value="">-- Select --</option>
                                        @foreach($angleOptions as $angleOption)
                                            <option value="{{ $angleOption }}">{{ $angleOption }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="composition">{{ __('Composition') }}</label>
                                    <select id="composition" wire:model="composition" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                        <option value="">-- Select --</option>
                                        @foreach($compositions as $compositionOption)
                                            <option value="{{ $compositionOption }}">{{ $compositionOption }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="lensType">{{ __('Lens Type') }}</label>
                                    <select id="lensType" wire:model="lensType" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                        <option value="">-- Select --</option>
                                        @foreach($lensTypes as $lensTypeOption)
                                            <option value="{{ $lensTypeOption }}">{{ $lensTypeOption }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="filmSimulation">{{ __('Film Simulation') }}</label>
                                    <select id="filmSimulation" wire:model="filmSimulation" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                        <option value="">-- Select --</option>
                                        @foreach($filmSimulations as $filmSimulationOption)
                                            <option value="{{ $filmSimulationOption }}">{{ $filmSimulationOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    

                    <div class="flex items-center gap-3">
                        <button type="button" class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200" wire:click="generateImages" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('Generate Images') }}</span>
                            <span wire:loading>
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                        <button type="button" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" wire:click="resetForm">{{ __('Reset') }}</button>
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <header class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
                    </header>

                    <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
                        @if(empty($generatedImages))
                            <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                                <flux:icon.photo variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Enter a prompt to generate images.') }}
                                </p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach($generatedImages as $index => $image)
                                    <div class="relative cursor-pointer overflow-hidden rounded-lg border-2 {{ $selectedImageIndex === $index ? 'border-blue-500 ring-2 ring-blue-500' : 'border-transparent' }}" wire:click="$set('selectedImageIndex', {{ $index }})">
                                        <img src="{{ $image }}" alt="Generated image {{ $index + 1 }}" class="h-full w-full object-cover">
                                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2 text-white">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium">Variant #{{ $index + 1 }}</span>
                                                @if($selectedImageIndex === $index)
                                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            @if($selectedImageIndex !== null)
                                <div class="mt-4 flex justify-end space-x-2">
                                    <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" wire:click="downloadImage">
                                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        {{ __('Download Selected') }}
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Other Tabs Placeholder -->
        @if ($activeTab !== 'image-generation')
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">
                    {{ __('Coming Soon') }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('This feature is under development and will be available soon.') }}
                </p>
            </div>
        @endif
    </div>
</main>
