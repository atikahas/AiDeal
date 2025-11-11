<div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
    <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Model Photoshoot') }}</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Generate professional model photos wearing your product.') }}
            </p>
        </div>
        @if (session()->has('error') || session()->has('message'))
            <div class="rounded-lg border px-4 py-3 text-sm {{ session()->has('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/50 dark:text-red-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' }}">
                {{ session('error') ?? session('message') }}
            </div>
        @endif
        <form wire:submit.prevent="generateModelPhotos" class="space-y-4">
            <!-- Assets & Model Selection -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Assets & Model Selection') }}</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Product Photo Upload -->
                    <div class="space-y-2" x-data="{ isDragging: false }">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Product Photo') }} <span class="text-red-500">*</span>
                        </label>

                        @if($productImage)
                            @php
                                try {
                                    $previewUrl = $productImage->temporaryUrl();
                                } catch (\Exception $e) {
                                    $previewUrl = null;
                                }
                            @endphp

                            <div class="relative overflow-hidden rounded-xl border-2 border-zinc-200 bg-gradient-to-br from-zinc-50 to-white dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-950">
                                @if($previewUrl)
                                    <div class="aspect-video w-full overflow-hidden">
                                        <img src="{{ $previewUrl }}" alt="Preview" class="h-full w-full object-contain">
                                    </div>
                                @endif

                                <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity hover:opacity-100">
                                    <button
                                        type="button"
                                        wire:click="$set('productImage', null)"
                                        class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-zinc-900 shadow-lg transition hover:bg-zinc-100"
                                    >
                                        {{ __('Change Image') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <label
                                for="upload-product-image"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="isDragging = false"
                                :class="isDragging ? 'border-zinc-900 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-800' : 'border-zinc-300 dark:border-zinc-600'"
                                class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed bg-white px-6 py-10 transition-all hover:border-zinc-400 hover:bg-zinc-50 dark:bg-zinc-950 dark:hover:border-zinc-500 dark:hover:bg-zinc-900"
                                wire:loading.class="pointer-events-none opacity-50"
                                wire:target="productImage"
                            >
                                <div class="mb-3 rounded-full bg-zinc-100 p-3 dark:bg-zinc-800">
                                    <svg class="h-8 w-8 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>

                                <div class="text-center">
                                    <p class="mb-1 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                        <span class="text-zinc-900 underline dark:text-zinc-100">{{ __('Click to upload') }}</span>
                                        {{ __('or drag and drop') }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('PNG, JPG or WebP (max. 2MB)') }}
                                    </p>
                                </div>

                                <div wire:loading wire:target="productImage" class="mt-3">
                                    <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>{{ __('Uploading...') }}</span>
                                    </div>
                                </div>
                            </label>

                            <input
                                type="file"
                                id="upload-product-image"
                                accept="image/png,image/jpeg,image/webp"
                                wire:model="productImage"
                                class="sr-only"
                                required
                            >
                        @endif

                        @error('productImage')
                            <p class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Model Face Upload (Optional) -->
                    <div class="space-y-2" x-data="{ isDragging: false }">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __("Model's Face") }} <span class="text-xs text-zinc-500">({{ __('Optional') }})</span>
                        </label>

                        @if($modelFaceImage)
                            @php
                                try {
                                    $previewUrl = $modelFaceImage->temporaryUrl();
                                } catch (\Exception $e) {
                                    $previewUrl = null;
                                }
                            @endphp

                            <div class="relative overflow-hidden rounded-xl border-2 border-zinc-200 bg-gradient-to-br from-zinc-50 to-white dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-950">
                                @if($previewUrl)
                                    <div class="aspect-video w-full overflow-hidden">
                                        <img src="{{ $previewUrl }}" alt="Preview" class="h-full w-full object-contain">
                                    </div>
                                @endif

                                <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity hover:opacity-100">
                                    <button
                                        type="button"
                                        wire:click="$set('modelFaceImage', null)"
                                        class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-zinc-900 shadow-lg transition hover:bg-zinc-100"
                                    >
                                        {{ __('Change Image') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <label
                                for="upload-model-face"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="isDragging = false"
                                :class="isDragging ? 'border-zinc-900 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-800' : 'border-zinc-300 dark:border-zinc-600'"
                                class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed bg-white px-6 py-10 transition-all hover:border-zinc-400 hover:bg-zinc-50 dark:bg-zinc-950 dark:hover:border-zinc-500 dark:hover:bg-zinc-900"
                                wire:loading.class="pointer-events-none opacity-50"
                                wire:target="modelFaceImage"
                            >
                                <div class="mb-3 rounded-full bg-zinc-100 p-3 dark:bg-zinc-800">
                                    <svg class="h-8 w-8 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>

                                <div class="text-center">
                                    <p class="mb-1 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                        <span class="text-zinc-900 underline dark:text-zinc-100">{{ __('Click to upload') }}</span>
                                        {{ __('or drag and drop') }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('PNG, JPG or WebP (max. 2MB)') }}
                                    </p>
                                </div>

                                <div wire:loading wire:target="modelFaceImage" class="mt-3">
                                    <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>{{ __('Uploading...') }}</span>
                                    </div>
                                </div>
                            </label>

                            <input
                                type="file"
                                id="upload-model-face"
                                accept="image/png,image/jpeg,image/webp"
                                wire:model="modelFaceImage"
                                class="sr-only"
                            >
                        @endif

                        @error('modelFaceImage')
                            <p class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Custom Prompt -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="prompt">
                    {{ __('Custom Prompt') }} <span class="text-xs text-zinc-500">({{ __('Optional') }})</span>
                </label>
                <textarea id="prompt" wire:model.live="prompt" rows="4" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" placeholder="{{ __('Leave empty to use advanced settings below, or provide your own custom prompt...') }}"></textarea>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('If left empty, a detailed prompt will be generated based on the settings below.') }}
                </p>
                @error('prompt')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Creative Direction -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Creative Direction') }}</h3>

                <!-- Model Gender -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __("Model's Gender") }}
                        @if($modelFaceImage)
                            <span class="text-xs font-normal text-zinc-500">({{ __('Disabled - using uploaded face') }})</span>
                        @endif
                    </label>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            wire:click="$set('modelGender', 'female')"
                            @disabled($modelFaceImage)
                            class="flex-1 rounded-lg border px-4 py-2 text-sm font-medium transition {{ $modelGender === 'female' ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900' : 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-900' }} {{ $modelFaceImage ? 'opacity-50 cursor-not-allowed' : '' }}"
                        >
                            {{ __('Female') }}
                        </button>
                        <button
                            type="button"
                            wire:click="$set('modelGender', 'male')"
                            @disabled($modelFaceImage)
                            class="flex-1 rounded-lg border px-4 py-2 text-sm font-medium transition {{ $modelGender === 'male' ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900' : 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-900' }} {{ $modelFaceImage ? 'opacity-50 cursor-not-allowed' : '' }}"
                        >
                            {{ __('Male') }}
                        </button>
                    </div>
                </div>

                <!-- Other Creative Direction Fields -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="modelEthnicity">
                            {{ __('Ethnicity') }}
                            @if($modelFaceImage)
                                <span class="text-xs font-normal text-zinc-500">({{ __('Disabled - using uploaded face') }})</span>
                            @endif
                        </label>
                        <select id="modelEthnicity" wire:model="modelEthnicity" @disabled($modelFaceImage) class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 {{ $modelFaceImage ? 'opacity-50 cursor-not-allowed' : '' }}">
                            <option value="">-- {{ __('Select Ethnicity') }} --</option>
                            @foreach($ethnicities as $ethnicity)
                                <option value="{{ $ethnicity }}">{{ $ethnicity }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="artisticStyle">{{ __('Artistic Style') }}</label>
                        <select id="artisticStyle" wire:model="artisticStyle" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- {{ __('Select Style') }} --</option>
                            @foreach($styles as $styleOption)
                                <option value="{{ $styleOption }}">{{ $styleOption }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="lighting">{{ __('Lighting') }}</label>
                        <select id="lighting" wire:model="lighting" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- {{ __('Select Lighting') }} --</option>
                            @foreach($lightingOptions as $lightingOption)
                                <option value="{{ $lightingOption }}">{{ $lightingOption }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="cameraShot">{{ __('Camera Shot') }}</label>
                        <select id="cameraShot" wire:model="cameraShot" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- {{ __('Select Camera Shot') }} --</option>
                            @foreach($cameraShots as $shot)
                                <option value="{{ $shot }}">{{ $shot }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="bodyPose">{{ __('Body Pose') }}</label>
                        <select id="bodyPose" wire:model="bodyPose" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- {{ __('Select Body Pose') }} --</option>
                            @foreach($bodyPoses as $pose)
                                <option value="{{ $pose }}">{{ $pose }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="backgroundVibe">{{ __('Background/Vibe') }}</label>
                        <select id="backgroundVibe" wire:model="backgroundVibe" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- {{ __('Select Background') }} --</option>
                            @foreach($backgroundVibes as $vibe)
                                <option value="{{ $vibe }}">{{ $vibe }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="composition">{{ __('Composition') }}</label>
                        <select id="composition" wire:model="composition" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- {{ __('Select Composition') }} --</option>
                            @foreach($compositions as $comp)
                                <option value="{{ $comp }}">{{ $comp }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="lensType">{{ __('Lens Type') }}</label>
                        <select id="lensType" wire:model="lensType" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- {{ __('Select Lens Type') }} --</option>
                            @foreach($lensTypes as $lens)
                                <option value="{{ $lens }}">{{ $lens }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="filmSimulation">{{ __('Film Simulation') }}</label>
                        <select id="filmSimulation" wire:model="filmSimulation" class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            <option value="">-- {{ __('Select Film Simulation') }} --</option>
                            @foreach($filmSimulations as $film)
                                <option value="{{ $film }}">{{ $film }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Number of Images and AI Creativity -->
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
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="ai-creativity">
                            {{ __('AI Creativity') }}: <span x-text="$wire.aiCreativity"></span>
                        </label>
                    </div>
                    <input type="range" id="ai-creativity" wire:model.live="aiCreativity" min="1" max="10" value="5" class="w-full h-2 bg-zinc-200 rounded-lg border-2 border-zinc-200 dark:border-zinc-700 appearance-none cursor-pointer dark:bg-zinc-700 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-zinc-900 [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white [&::-webkit-slider-thumb]:dark:border-zinc-900 [&::-webkit-slider-thumb]:dark:bg-white">
                    <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                        <span>Less Creative</span>
                        <span>More Creative</span>
                    </div>
                </div>
            </div>

            <flux:separator />

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200" wire:loading.attr="disabled" wire:target="generateModelPhotos,productImage,modelFaceImage">
                    <span>{{ __('Generate Model Photos') }}</span>
                    <svg wire:loading wire:target="generateModelPhotos" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"></path>
                    </svg>
                </button>
                <button type="button" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" wire:click="resetForm" wire:loading.attr="disabled" wire:target="generateModelPhotos,productImage,modelFaceImage">{{ __('Reset') }}</button>
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
                        {{ __('Upload a product image and configure settings to generate model photos.') }}
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
                                        alt="Generated model photo {{ $index + 1 }}"
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
                                            wire:click="viewImage({{ $index }})"
                                            title="View"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span class="sr-only">{{ __('View') }}</span>
                                        </button>

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

    <!-- Image View Modal -->
    @if($showImageModal && $viewingImageIndex !== null && isset($generatedImages[$viewingImageIndex]))
        @php
            $viewingImage = $generatedImages[$viewingImageIndex];
            $viewingSrc = $viewingImage['url'] ?? null;
            if (!$viewingSrc && isset($viewingImage['data'])) {
                $mime = $viewingImage['mime'] ?? 'image/png';
                $viewingSrc = 'data:' . $mime . ';base64,' . $viewingImage['data'];
            } elseif (!$viewingSrc && is_string($viewingImage)) {
                $viewingSrc = $viewingImage;
            }
        @endphp

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="closeImageModal">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/90 backdrop-blur-sm" wire:click="closeImageModal"></div>

            <!-- Modal Content -->
            <div class="relative z-10 w-full max-w-6xl">
                <!-- Close Button -->
                <button
                    wire:click="closeImageModal"
                    class="absolute -right-2 -top-2 z-20 rounded-full bg-white p-2 text-zinc-700 shadow-lg transition hover:bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 sm:-right-4 sm:-top-4"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="overflow-hidden rounded-lg bg-white shadow-2xl dark:bg-zinc-900">
                    <!-- Image -->
                    <div class="relative bg-zinc-100 dark:bg-zinc-800">
                        @if($viewingSrc)
                            <img
                                src="{{ $viewingSrc }}"
                                alt="Generated model photo {{ $viewingImageIndex + 1 }}"
                                class="mx-auto max-h-[80vh] w-auto object-contain"
                            >
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between gap-2 p-4 sm:p-6">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Image') }} #{{ $viewingImageIndex + 1 }}
                        </span>

                        <div class="flex gap-2">
                            <button
                                wire:click="saveImage({{ $viewingImageIndex }})"
                                wire:loading.attr="disabled"
                                wire:target="saveImage({{ $viewingImageIndex }})"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                </svg>
                                {{ __('Save') }}
                            </button>

                            <button
                                wire:click="downloadImage({{ $viewingImageIndex }})"
                                wire:loading.attr="disabled"
                                wire:target="downloadImage({{ $viewingImageIndex }})"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                {{ __('Download') }}
                            </button>

                            <button
                                wire:click="closeImageModal"
                                class="rounded-lg border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                {{ __('Close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
