<div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
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
    <div class="grid gap-6 lg:grid-cols-[1fr,1fr]">
        @if ($activeTab === 'image-generation')
            <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Image Generation') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Generate unique images using AI. Describe what you want to see in detail for best results.') }}
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="prompt">
                            {{ __('Image Prompt') }}
                        </label>
                        <textarea
                            id="prompt"
                            wire:model="prompt"
                            rows="4"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400"
                            placeholder="A beautiful sunset over mountains with a lake in the foreground"
                        ></textarea>
                        @error('prompt')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="imageStyle">
                                {{ __('Style') }}
                            </label>
                            <select
                                id="imageStyle"
                                wire:model="imageStyle"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400"
                            >
                                <option value="photorealistic">Photorealistic</option>
                                <option value="digital-art">Digital Art</option>
                                <option value="3d-render">3D Render</option>
                                <option value="watercolor">Watercolor</option>
                                <option value="anime">Anime</option>
                            </select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="imageAspectRatio">
                                    {{ __('Aspect Ratio') }}
                                </label>
                                <select 
                                    id="imageAspectRatio"
                                    wire:model="imageAspectRatio"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400">
                                    <option value="1:1">Square (1:1)</option>
                                    <option value="16:9">Wide (16:9)</option>
                                    <option value="9:16">Portrait (9:16)</option>
                                    <option value="4:3">Standard (4:3)</option>
                                    <option value="3:4">Portrait (3:4)</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="image">
                                {{ __('Upload Reference Image (Optional)') }}
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label for="image" class="flex flex-col items-center justify-center w-full h-32 border-2 border-zinc-300 border-dashed rounded-lg cursor-pointer bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800/50 dark:hover:border-zinc-500 dark:hover:bg-zinc-800/30">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-8 h-8 mb-2 text-zinc-500 dark:text-zinc-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                        </svg>
                                        <p class="mb-2 text-sm text-zinc-500 dark:text-zinc-400"><span class="font-semibold">{{ __('Click to upload') }}</span> {{ __('or drag and drop') }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('PNG, JPG, or WEBP (MAX. 10MB)') }}</p>
                                    </div>
                                    <input 
                                        id="image" 
                                        type="file" 
                                        class="hidden"
                                        wire:model="image"
                                        accept="image/*"
                                    >
                                </label>
                            </div>
                            @error('image')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                wire:click="generateImage"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-offset-zinc-900"
                            >
                                <svg wire:loading wire:target="generateImage" class="mr-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="generateImage">
                                    {{ __('Generate Image') }}
                                </span>
                                <span wire:loading wire:target="generateImage">
                                    {{ __('Generating...') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <header class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Preview') }}</h2>
                        @if ($generatedImage)
                            <div class="flex space-x-2">
                                <button 
                                    type="button"
                                    x-data="{ copied: false }"
                                    x-on:click="
                                        navigator.clipboard.writeText('{{ $generatedImage }}');
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg x-show="!copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <svg x-show="copied" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="copied ? 'Copied!' : 'Copy URL'"></span>
                                </button>
                                
                                <button
                                    type="button"
                                    wire:click="downloadImage"
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                >
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    {{ __('Download') }}
                                </button>
                            </div>
                        @endif
                    </header>

                    <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
                        @if ($generatedImage)
                            <div class="flex flex-col items-center justify-center h-full">
                                <img 
                                    src="{{ $generatedImage }}?{{ time() }}" 
                                    alt="Generated Image"
                                    class="max-w-full max-h-[300px] rounded-lg shadow-md">
                                
                                <div class="mt-6 flex justify-center space-x-3">
                                    <button
                                        type="button"
                                        wire:click="regenerateImage"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md shadow-sm text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                                    >
                                        <svg wire:loading wire:target="regenerateImage" class="mr-2 h-4 w-4 animate-spin text-zinc-600 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="regenerateImage">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            {{ __('Regenerate') }}
                                        </span>
                                        <span wire:loading wire:target="regenerateImage">
                                            {{ __('Regenerating...') }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ __('Your generated image will appear here') }}
                                </p>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ __('Enter a prompt and click "Generate Image" to get started') }}
                                </p>
                            </div>
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
</div>
