<div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
    <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Video Storyboard Generator') }}</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Create detailed video storyboards with scene-by-scene breakdowns for your video projects.') }}
            </p>
        </div>

        @if (session()->has('error') || session()->has('message'))
            <div class="rounded-lg border px-4 py-3 text-sm {{ session()->has('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/50 dark:text-red-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' }}">
                {{ session('error') ?? session('message') }}
            </div>
        @endif

        <form wire:submit.prevent="generateStoryboard" class="space-y-6">
            <!-- Product Information Section -->
            <div class="space-y-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Product Information (Optional)') }}</h3>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="product-name">
                            {{ __('Product Name') }}
                        </label>
                        <input
                            type="text"
                            id="product-name"
                            wire:model.defer="productName"
                            maxlength="200"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            placeholder="{{ __('e.g., Premium Wireless Headphones') }}"
                        />
                        @error('productName')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="product-description">
                            {{ __('Product Description') }}
                        </label>
                        <input
                            type="text"
                            id="product-description"
                            wire:model.defer="productDescription"
                            maxlength="500"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            placeholder="{{ __('e.g., Noise-cancelling, 30hr battery life') }}"
                        />
                        @error('productDescription')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Photo Uploads -->
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Product Photo Upload -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="product-photo">
                            {{ __('Product Photo') }}
                        </label>
                        <div class="space-y-2">
                            @if($productPhoto)
                                <div class="relative overflow-hidden rounded-lg border-2 border-emerald-500 bg-zinc-50 p-2 dark:bg-zinc-900">
                                    <img
                                        src="{{ $productPhoto->temporaryUrl() }}"
                                        alt="Product Preview"
                                        class="h-32 w-full rounded object-cover"
                                    />
                                    <button
                                        type="button"
                                        wire:click="$set('productPhoto', null)"
                                        class="absolute right-2 top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <span class="mt-1 block text-center text-xs text-emerald-600 dark:text-emerald-400">✓ {{ __('Uploaded') }}</span>
                                </div>
                            @else
                                <label for="product-photo" class="flex h-32 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 transition hover:border-zinc-400 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                                    <svg class="h-8 w-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Click to upload product') }}</span>
                                    <input
                                        type="file"
                                        id="product-photo"
                                        wire:model="productPhoto"
                                        accept="image/png,image/jpeg,image/jpg,image/webp"
                                        class="hidden"
                                    />
                                </label>
                            @endif
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Upload product image (optional)') }}
                        </p>
                        @error('productPhoto')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Model Photo Upload -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="model-photo">
                            {{ __('Model / Person Photo') }}
                        </label>
                        <div class="space-y-2">
                            @if($modelPhoto)
                                <div class="relative overflow-hidden rounded-lg border-2 border-emerald-500 bg-zinc-50 p-2 dark:bg-zinc-900">
                                    <img
                                        src="{{ $modelPhoto->temporaryUrl() }}"
                                        alt="Model Preview"
                                        class="h-32 w-full rounded object-cover"
                                    />
                                    <button
                                        type="button"
                                        wire:click="$set('modelPhoto', null)"
                                        class="absolute right-2 top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <span class="mt-1 block text-center text-xs text-emerald-600 dark:text-emerald-400">✓ {{ __('Uploaded') }}</span>
                                </div>
                            @else
                                <label for="model-photo" class="flex h-32 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 transition hover:border-zinc-400 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                                    <svg class="h-8 w-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Click to upload model') }}</span>
                                    <input
                                        type="file"
                                        id="model-photo"
                                        wire:model="modelPhoto"
                                        accept="image/png,image/jpeg,image/jpg,image/webp"
                                        class="hidden"
                                    />
                                </label>
                            @endif
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Upload model/person image (optional)') }}
                        </p>
                        @error('modelPhoto')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Video Concept Section -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="video-idea">
                        {{ __('Video Idea / Concept') }} <span class="text-red-500">*</span>
                    </label>
                    <button
                        type="button"
                        wire:click="generateMagicPrompt"
                        wire:loading.attr="disabled"
                        wire:target="generateMagicPrompt"
                        class="inline-flex items-center gap-1.5 rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        <svg wire:loading.remove wire:target="generateMagicPrompt" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        <span wire:loading.remove wire:target="generateMagicPrompt">{{ __('Magic Prompt') }}</span>
                        <span wire:loading wire:target="generateMagicPrompt">{{ __('Enhancing') }}</span>
                        <svg wire:loading wire:target="generateMagicPrompt" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"></path>
                        </svg>
                    </button>
                </div>
                <textarea
                    id="video-idea"
                    wire:model.defer="videoIdea"
                    rows="4"
                    maxlength="1000"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="{{ __('e.g., Product launch announcement video') }}"
                ></textarea>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    💡 {{ __('Tip: Enter a simple video idea, then click Magic Prompt to expand it into a detailed storyboard concept.') }}
                </p>
                @error('videoIdea')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="scene-count">
                        {{ __('Number of Scenes') }}
                    </label>
                    <select
                        id="scene-count"
                        wire:model="sceneCount"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        @foreach($sceneCounts as $count)
                            <option value="{{ $count }}">{{ $count }} {{ $count === 1 ? 'Scene' : 'Scenes' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="language">
                        {{ __('Output Language') }}
                    </label>
                    <select
                        id="language"
                        wire:model="language"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        @foreach($languages as $lang)
                            <option value="{{ $lang }}">{{ $lang }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <flux:separator />

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                    wire:loading.attr="disabled"
                    wire:target="generateStoryboard"
                >
                    <svg wire:loading.remove wire:target="generateStoryboard" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                    <span wire:loading.remove wire:target="generateStoryboard">{{ __('Generate Storyboard') }}</span>
                    <svg wire:loading wire:target="generateStoryboard" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"></path>
                    </svg>
                    <span wire:loading wire:target="generateStoryboard">{{ __('Generating...') }}</span>
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    wire:click="resetForm"
                >
                    {{ __('Reset') }}
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <header class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
            @if(count($storyboardOutput) > 0)
                <div class="flex space-x-2">
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        x-on:click="
                            const outputText = @js(collect($storyboardOutput)->map(function($scene, $index) {
                                return 'Scene ' . ($index + 1) . ': ' . ($scene['title'] ?? '') . '\n' .
                                       'Description: ' . ($scene['description'] ?? '') . '\n' .
                                       'Duration: ' . ($scene['duration'] ?? '') . '\n';
                            })->join('\n'));
                            navigator.clipboard.writeText(outputText);
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
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                    <button
                        type="button"
                        x-data="{ saved: false }"
                        x-on:click="
                            const content = @js(collect($storyboardOutput)->map(function($scene, $index) {
                                return 'Scene ' . ($index + 1) . ': ' . ($scene['title'] ?? '') . '\n' .
                                       'Description: ' . ($scene['description'] ?? '') . '\n' .
                                       'Duration: ' . ($scene['duration'] ?? '') . '\n';
                            })->join('\n'));
                            const blob = new Blob([content], { type: 'text/plain' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'video-storyboard-' + new Date().toISOString().slice(0, 10) + '.txt';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            URL.revokeObjectURL(url);
                            saved = true;
                            setTimeout(() => saved = false, 2000);
                        "
                        class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900"
                    >
                        <svg x-show="!saved" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        <svg x-show="saved" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span x-text="saved ? 'Saved!' : 'Save as TXT'"></span>
                    </button>
                </div>
            @endif
        </header>

        <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
            @if(count($storyboardOutput) > 0)
                <div class="space-y-8">
                    @foreach($storyboardOutput as $index => $scene)
                        <div class="group relative overflow-hidden rounded-xl border-2 border-zinc-200 bg-white shadow-lg transition hover:shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
                            <!-- Scene Header with Edit Button -->
                            <div class="flex items-center justify-between border-b-2 border-zinc-100 bg-gradient-to-r from-zinc-50 to-white p-4 dark:border-zinc-700 dark:from-zinc-800 dark:to-zinc-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-zinc-900 to-zinc-700 text-lg font-bold text-white shadow-md dark:from-zinc-100 dark:to-zinc-300 dark:text-zinc-900">
                                        {{ $scene['number'] ?? ($index + 1) }}
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-50">
                                            {{ $scene['title'] ?? 'Scene ' . ($index + 1) }}
                                        </h3>
                                        @if(!empty($scene['duration']))
                                            <p class="mt-0.5 flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ $scene['duration'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Edit Button -->
                                <button
                                    type="button"
                                    wire:click="editScene({{ $index }})"
                                    class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700"
                                >
                                    <svg class="inline h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    {{ __('Edit') }}
                                </button>
                            </div>

                            <!-- Content Section -->
                            <div class="space-y-4 p-6">
                                <!-- Tabs -->
                                <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
                                    <button
                                        type="button"
                                        class="border-b-2 border-zinc-900 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:border-zinc-100 dark:text-zinc-100"
                                    >
                                        📝 {{ __('Storyboard') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="border-b-2 border-transparent px-4 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                                    >
                                        🖼️ {{ __('Image') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="border-b-2 border-transparent px-4 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                                    >
                                        🎬 {{ __('Video') }}
                                    </button>
                                </div>

                                <!-- Storyboard Details -->
                                <div class="space-y-4">
                                    @if(!empty($scene['visual']))
                                        <div>
                                            <div class="mb-2 flex items-center gap-2">
                                                <svg class="h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Visual') }}</span>
                                            </div>
                                            <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $scene['visual'] }}</p>
                                        </div>
                                    @endif

                                    <div class="grid gap-4 md:grid-cols-2">
                                        @if(!empty($scene['camera']))
                                            <div>
                                                <div class="mb-2 flex items-center gap-2">
                                                    <svg class="h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Camera') }}</span>
                                                </div>
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $scene['camera'] }}</p>
                                            </div>
                                        @endif

                                        @if(!empty($scene['audio']))
                                            <div>
                                                <div class="mb-2 flex items-center gap-2">
                                                    <svg class="h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                                    </svg>
                                                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Audio') }}</span>
                                                </div>
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $scene['audio'] }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Image Preview & Actions -->
                                <div class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                    <div class="aspect-video overflow-hidden rounded-lg border-2 border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900">
                                        @if(!empty($scene['image_url']))
                                            <img src="{{ $scene['image_url'] }}" alt="Scene {{ $index + 1 }}" class="h-full w-full object-cover" />
                                        @else
                                            <div class="flex h-full items-center justify-center">
                                                <div class="text-center">
                                                    <svg class="mx-auto h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <p class="mt-2 text-sm text-zinc-400">{{ __('No image yet') }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    @if(empty($scene['image_url']))
                                        <button
                                            type="button"
                                            wire:click="generateImageForScene({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="generateImageForScene({{ $index }})"
                                            class="w-full rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                                        >
                                            <span wire:loading.remove wire:target="generateImageForScene({{ $index }})">{{ __('Generate Image') }}</span>
                                            <span wire:loading wire:target="generateImageForScene({{ $index }})">{{ __('Generating...') }}</span>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="regenerateImage({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="regenerateImage({{ $index }})"
                                            class="w-full rounded-lg border-2 border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 disabled:opacity-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700"
                                        >
                                            <svg class="inline h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            {{ __('Regenerate') }}
                                        </button>
                                    @endif
                                </div>

                                <!-- Video Preview & Actions -->
                                <div class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                    <div class="aspect-video overflow-hidden rounded-lg border-2 border-zinc-200 bg-zinc-900 dark:border-zinc-700">
                                        @if(!empty($scene['video_url']))
                                            <video src="{{ $scene['video_url'] }}" controls preload="metadata" class="h-full w-full object-contain">
                                                {{ __('Your browser does not support the video tag.') }}
                                            </video>
                                        @else
                                            <div class="flex h-full items-center justify-center">
                                                <div class="text-center">
                                                    <svg class="mx-auto h-16 w-16 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    <p class="mt-2 text-sm text-zinc-400">{{ __('No video yet') }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    @if(empty($scene['video_url']))
                                        <button
                                            type="button"
                                            wire:click="generateVideoForScene({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="generateVideoForScene({{ $index }})"
                                            @if(empty($scene['image_url'])) disabled @endif
                                            class="w-full rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                                        >
                                            <span wire:loading.remove wire:target="generateVideoForScene({{ $index }})">
                                                @if(empty($scene['image_url']))
                                                    {{ __('Generate Image First') }}
                                                @else
                                                    {{ __('Generate Video') }}
                                                @endif
                                            </span>
                                            <span wire:loading wire:target="generateVideoForScene({{ $index }})">{{ __('Generating...') }}</span>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="regenerateVideo({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="regenerateVideo({{ $index }})"
                                            class="w-full rounded-lg border-2 border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 disabled:opacity-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700"
                                        >
                                            <svg class="inline h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            {{ __('Regenerate') }}
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Transition (shown at bottom if exists) -->
                            @if(!empty($scene['transition']))
                                <div class="border-t-2 border-zinc-100 bg-zinc-50 px-6 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                        </svg>
                                        <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Transition:') }}</span>
                                        <p class="text-xs italic text-zinc-600 dark:text-zinc-400">{{ $scene['transition'] }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                    <flux:icon.clipboard-document-list variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Your video storyboard will appear here.') }}
                    </p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">
                        {{ __('Fill in the details and click Generate Storyboard') }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
