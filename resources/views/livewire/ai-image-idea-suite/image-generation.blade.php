<div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
    <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Image Generator') }}</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Generate images from text or edit existing photos with AI.') }}
            </p>
        </div>

        <div class="space-y-4">
            {{-- <div class="space-y-2">
                for upload image input file
            </div> --}}
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

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                    {{ __('Generate Image') }}
                </button>
                <button type="button" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" wire:click="resetForm">{{ __('Reset') }}</button>
            </div>
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
