<div class="grid grid-cols-1 gap-3 xl:grid-cols-2" x-data="{ generationMode: @entangle('generationMode') }">
    <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Image Generator') }}</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Generate images from text prompts using AI.') }}
            </p>
        </div>

        @if (session()->has('error') || session()->has('message'))
            <div class="rounded-lg border px-4 py-3 text-sm {{ session()->has('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/50 dark:text-red-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' }}">
                {{ session('error') ?? session('message') }}
            </div>
        @endif

        <form wire:submit.prevent="generateImage" class="space-y-4">
            <div class="flex flex-wrap gap-2 rounded-xl border border-dashed border-zinc-200 p-2 dark:border-zinc-700">
                @foreach([
                    'text-to-image' => __('Text'),
                    'text-image' => __('Text + Image'),
                ] as $mode => $label)
                    <button
                        type="button"
                        wire:click="setGenerationMode('{{ $mode }}')"
                        wire:loading.attr="disabled"
                        wire:target="generateImage"
                        @class([
                            'flex-1 rounded-lg px-3 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500',
                            'bg-zinc-900 text-white shadow-sm dark:bg-zinc-100 dark:text-zinc-900' => $generationMode === $mode,
                            'bg-white text-zinc-600 hover:bg-zinc-100 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' => $generationMode !== $mode,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="space-y-2" x-show="generationMode !== 'text-to-image'" x-transition>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="upload-image">
                        {{ __('Upload Image') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="file"
                        id="upload-image"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="image"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                        x-bind:required="generationMode !== 'text-to-image'"
                    >
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Maximum file size: 2MB. Supported formats: JPEG, PNG, WebP') }}
                    </p>
                    @error('image')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    @if($image)
                        <div class="mt-3 space-y-2">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Image uploaded successfully') }}</p>
                            @php
                                try {
                                    $previewUrl = $image->temporaryUrl();
                                } catch (\Exception $e) {
                                    $previewUrl = null;
                                }
                            @endphp
                            @if($previewUrl)
                                <div class="relative h-32 w-32 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ $previewUrl }}" alt="Preview" class="h-full w-full object-cover">
                                </div>
                            @endif
                        </div>
                    @endif
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="prompt">
                    {{ __('Prompt') }} <span class="text-red-500">*</span>
                </label>
                <textarea 
                    id="prompt" 
                    wire:model.live="prompt" 
                    rows="4" 
                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" 
                    placeholder="{{ __('Describe the image you want to generate...') }}"
                ></textarea>
                @error('prompt')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="image-count">
                        {{ __('Number of Images') }}
                    </label>
                    <select id="image-count" wire:model.live="imageCount" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        @for($i = 1; $i <= 4; $i++)
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
            <div class="space-y-2">
                <button type="button" wire:click="$toggle('showAdvancedSettings')" class="inline-flex items-center text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                    {{ __('Advanced Settings (Optional)') }}
                    <svg class="ml-1.5 h-4 w-4 transition-transform duration-200 {{ $showAdvancedSettings ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
            <div x-show="$wire.showAdvancedSettings" x-collapse class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="lens-type">{{ __('Lens Type') }}</label>
                        <select id="lens-type" wire:model="lensType" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- Select --</option>
                            @foreach($lensTypes as $lensTypeOption)
                                <option value="{{ $lensTypeOption }}">{{ $lensTypeOption }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="film-simulation">{{ __('Film Simulation') }}</label>
                        <select id="film-simulation" wire:model="filmSimulation" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- Select --</option>
                            @foreach($filmSimulations as $filmSimulationOption)
                                <option value="{{ $filmSimulationOption }}">{{ $filmSimulationOption }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <flux:separator />

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                    wire:loading.attr="disabled"
                    wire:target="generateImage,image"
                >
                    <span>{{ __('Generate Image') }}</span>
                    <svg wire:loading wire:target="generateImage" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"></path>
                    </svg>
                </button>
                <button type="button" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" wire:click="resetForm" wire:loading.attr="disabled" wire:target="generateImage,image">{{ __('Reset') }}</button>
            </div>
        </form>
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
                        @php
                            $src = $image['url'] ?? null;
                            if (! $src && isset($image['data'])) {
                                $mime = $image['mime'] ?? 'image/png';
                                $src = 'data:' . $mime . ';base64,' . $image['data'];
                            } elseif (! $src && is_string($image)) {
                                $src = $image;
                            }
                        @endphp
                        <div class="group relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="aspect-square w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                @if($src)
                                    <img 
                                        src="{{ $src }}" 
                                        alt="Generated image {{ $index + 1 }}" 
                                        class="h-full w-full object-cover transition-opacity group-hover:opacity-90"
                                        wire:click="selectImage({{ $index }})"
                                    >
                                @endif
                            </div>
                            
                            <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/60 to-transparent opacity-0 transition-opacity group-hover:opacity-100">
                                <div class="flex w-full items-center justify-between p-3">
                                    <span class="text-sm font-medium text-white">#{{ $index + 1 }}</span>
                                    
                                    <div class="flex items-center space-x-2">
                                        <button 
                                            type="button" 
                                            class="inline-flex items-center justify-center rounded-md bg-white/10 p-1.5 text-white backdrop-blur-sm hover:bg-white/20"
                                            wire:click="saveImage({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="saveImage({{ $index }})"
                                            title="Save"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                            </svg>
                                            <span class="sr-only">{{ __('Save') }}</span>
                                        </button>
                                        
                                        <a 
                                            href="#" 
                                            class="inline-flex items-center justify-center rounded-md bg-white/10 p-1.5 text-white backdrop-blur-sm hover:bg-white/20"
                                            wire:click.prevent="downloadImage({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadImage({{ $index }})"
                                            title="Download"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span class="sr-only">{{ __('Download') }}</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
