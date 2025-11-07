<div>
    <!-- Tabs for Text/Image Input -->
    <div class="mb-6">
        <div class="sm:hidden">
            <label for="tabs" class="sr-only">Select a tab</label>
            <select id="tabs" class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm">
                <option {{ $activeTab === 'text-to-image' ? 'selected' : '' }}>Text to Image</option>
                <option {{ $activeTab === 'image-to-image' ? 'selected' : '' }}>Image to Image</option>
            </select>
        </div>
        <div class="hidden sm:block">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        type="button"
                        @class([
                            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium',
                            'border-blue-500 text-blue-600' => $activeTab === 'text-to-image',
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $activeTab !== 'text-to-image',
                        ])
                        wire:click="setActiveTab('text-to-image')"
                    >
                        Text to Image
                    </button>
                    <button
                        type="button"
                        @class([
                            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium',
                            'border-blue-500 text-blue-600' => $activeTab === 'image-to-image',
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $activeTab !== 'image-to-image',
                        ])
                        wire:click="setActiveTab('image-to-image')"
                    >
                        Image to Image
                    </button>
                </nav>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="generateImage">
        <div class="space-y-6">
            <!-- Prompt Input -->
            <div>
                <label for="prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt</label>
                <div class="mt-1">
                    <textarea
                        id="prompt"
                        rows="3"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="A beautiful landscape with mountains and a lake..."
                        wire:model.defer="prompt"
                    ></textarea>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Be as descriptive as possible for best results.
                </p>
            </div>

            <!-- Image Upload (Conditional) -->
            @if($activeTab === 'image-to-image')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Source Image</label>
                    <div class="mt-1 flex justify-center rounded-md border-2 border-dashed border-gray-300 px-6 pt-5 pb-6 dark:border-gray-600">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                <label class="relative cursor-pointer rounded-md bg-white font-medium text-blue-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-2 hover:text-blue-500 dark:bg-gray-800">
                                    <span>Upload a file</span>
                                    <input id="file-upload" name="file-upload" type="file" class="sr-only" wire:model="sourceImage">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, WEBP up to 10MB</p>
                        </div>
                    </div>
                    @if($sourceImage)
                        <div class="mt-2 text-sm text-green-600 dark:text-green-400">
                            {{ $sourceImage->getClientOriginalName() }} ({{ round($sourceImage->getSize() / 1024) }} KB)
                        </div>
                    @endif
                    @error('sourceImage')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <!-- Advanced Options (Collapsible) -->
            <div x-data="{ showAdvanced: false }" class="space-y-4">
                <button type="button" @click="showAdvanced = !showAdvanced" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                    <span>Advanced Options</span>
                    <svg :class="{ 'rotate-180': showAdvanced }" class="ml-1 h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="showAdvanced" x-transition class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                    <!-- Negative Prompt -->
                    <div>
                        <label for="negativePrompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Negative Prompt</label>
                        <div class="mt-1">
                            <input
                                type="text"
                                id="negativePrompt"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="blurry, low quality, text, watermark"
                                wire:model.defer="negativePrompt"
                            >
                        </div>
                    </div>

                    <!-- Style Selector -->
                    <div>
                        <label for="style" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Style</label>
                        <select
                            id="style"
                            class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            wire:model.defer="style"
                        >
                            <option value="photographic">Photographic</option>
                            <option value="digital-art">Digital Art</option>
                            <option value="3d-render">3D Render</option>
                            <option value="anime">Anime</option>
                            <option value="comic">Comic</option>
                            <option value="fantasy-art">Fantasy Art</option>
                            <option value="pixel-art">Pixel Art</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Number of Images -->
                        <div>
                            <label for="imageCount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Number of Images</label>
                            <select
                                id="imageCount"
                                class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                wire:model.defer="imageCount"
                            >
                                @for($i = 1; $i <= 4; $i++)
                                    <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'Image' : 'Images' }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Aspect Ratio -->
                        <div>
                            <label for="aspectRatio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Aspect Ratio</label>
                            <select
                                id="aspectRatio"
                                class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                wire:model.defer="aspectRatio"
                            >
                                <option value="1:1">Square (1:1)</option>
                                <option value="4:3">Standard (4:3)</option>
                                <option value="16:9">Widescreen (16:9)</option>
                                <option value="9:16">Portrait (9:16)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generate Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    class="flex w-full justify-center rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                    wire:loading.attr="disabled"
                    wire:target="generateImage"
                >
                    <span wire:loading.remove wire:target="generateImage">
                        {{ $activeTab === 'text-to-image' ? 'Generate Images' : 'Edit Image' }}
                    </span>
                    <span wire:loading wire:target="generateImage" class="flex items-center">
                        <svg class="-ml-1 mr-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
            </div>
        </div>
    </form>

    <!-- Recent Generations -->
    @if($recentJobs->count() > 0)
        <div class="mt-12">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Generations</h3>
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($recentJobs as $job)
                    @if($job->result_image_path)
                        <div class="group relative overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700">
                            <img
                                src="{{ Storage::url($job->result_image_path) }}"
                                alt="Generated image for {{ $job->input_json['prompt'] ?? 'AI generated image' }}"
                                class="h-40 w-full object-cover"
                            >
                            <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 transition-opacity group-hover:opacity-100">
                                <a
                                    href="{{ Storage::url($job->result_image_path) }}"
                                    download
                                    class="rounded-full bg-white p-2 text-gray-700 hover:bg-gray-100"
                                    title="Download"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
