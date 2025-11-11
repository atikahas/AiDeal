<div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
    <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Video Generator') }}</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Generate videos from text prompts using AI.') }}
            </p>
        </div>

        @if (session()->has('error') || session()->has('message'))
            <div class="rounded-lg border px-4 py-3 text-sm {{ session()->has('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/50 dark:text-red-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' }}">
                {{ session('error') ?? session('message') }}
            </div>
        @endif

        <form wire:submit.prevent="generateVideo" class="space-y-4">
            <div class="space-y-2" x-data="{ isDragging: false }">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Reference Image (Optional)') }}
                </label>

                @if($referenceImage)
                    @php
                        try {
                            $previewUrl = $referenceImage->temporaryUrl();
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
                                wire:click="$set('referenceImage', null)"
                                class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-zinc-900 shadow-lg transition hover:bg-zinc-100"
                            >
                                {{ __('Change Image') }}
                            </button>
                        </div>
                    </div>
                @else
                    <label
                        for="reference-image"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="isDragging = false"
                        :class="isDragging ? 'border-zinc-900 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-800' : 'border-zinc-300 dark:border-zinc-600'"
                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed bg-white px-6 py-10 transition-all hover:border-zinc-400 hover:bg-zinc-50 dark:bg-zinc-950 dark:hover:border-zinc-500 dark:hover:bg-zinc-900"
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
                    </label>

                    <input
                        type="file"
                        id="reference-image"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="referenceImage"
                        class="sr-only"
                    >
                @endif

                @error('referenceImage')
                    <p class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="prompt">
                        {{ __('Video Prompt') }} <span class="text-red-500">*</span>
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
                    id="prompt"
                    wire:model.defer="prompt"
                    rows="4"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="{{ __('e.g., Premium smartphone with sleek design') }}"
                ></textarea>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    💡 {{ __('Tip: Enter a simple product description, then click Magic Prompt to enhance it automatically.') }}
                </p>
                @error('prompt')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="duration">
                        {{ __('Duration (seconds)') }}
                    </label>
                    <select
                        id="duration"
                        wire:model="duration"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        @foreach($durations as $dur)
                            <option value="{{ $dur }}">{{ $dur }} {{ __('seconds') }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="aspect-ratio">
                        {{ __('Aspect Ratio') }}
                    </label>
                    <select
                        id="aspect-ratio"
                        wire:model="aspectRatio"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        @foreach($aspectRatios as $ratio)
                            <option value="{{ $ratio }}">{{ $ratio }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="style">
                    {{ __('Video Style') }}
                </label>
                <select
                    id="style"
                    wire:model="style"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                >
                    @foreach($styles as $styleOption)
                        <option value="{{ strtolower($styleOption) }}">{{ $styleOption }}</option>
                    @endforeach
                </select>
            </div>

            <flux:separator text="{{ __('Dialogue & Text (Optional)') }}" />

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="on-screen-text">
                    {{ __('On-Screen Text (Captions)') }}
                </label>
                <textarea
                    id="on-screen-text"
                    wire:model.defer="onScreenText"
                    rows="2"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="{{ __('Enter any text you want to appear on the video.') }}"
                ></textarea>
                @error('onScreenText')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="spoken-dialogue">
                    {{ __('Spoken Dialogue (Voiceover)') }}
                </label>
                <textarea
                    id="spoken-dialogue"
                    wire:model.defer="spokenDialogue"
                    rows="3"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="{{ __('Enter the exact dialogue for the AI to speak.') }}"
                ></textarea>
                @error('spokenDialogue')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="voiceover-language">
                        {{ __('Voiceover Language') }}
                    </label>
                    <select
                        id="voiceover-language"
                        wire:model="voiceoverLanguage"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        @foreach($voiceoverLanguages as $lang)
                            <option value="{{ $lang }}">{{ $lang }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="voiceover-mood">
                        {{ __('Voiceover Mood') }}
                    </label>
                    <select
                        id="voiceover-mood"
                        wire:model="voiceoverMood"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        @foreach($voiceoverMoods as $mood)
                            <option value="{{ $mood }}">{{ $mood }}</option>
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
                    wire:target="generateVideo"
                >
                    <span>{{ __('Generate Video') }}</span>
                    <svg wire:loading wire:target="generateVideo" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"></path>
                    </svg>
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
        <header class="mb-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
        </header>

        <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
            @if(!empty($generatedVideos))
                <div class="space-y-4">
                    @foreach($generatedVideos as $index => $video)
                        <div class="group relative overflow-hidden rounded-lg border border-zinc-200 bg-zinc-900 dark:border-zinc-700">
                            <div class="aspect-video w-full">
                                @if(!empty($video['url']))
                                    <video
                                        src="{{ $video['url'] }}"
                                        class="h-full w-full object-contain"
                                        controls
                                        preload="metadata"
                                    >
                                        {{ __('Your browser does not support the video tag.') }}
                                    </video>
                                @endif
                            </div>

                            <div class="flex items-center justify-between border-t border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Video') }} #{{ $index + 1 }}
                                </span>

                                <div class="flex items-center space-x-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-md bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                                        wire:click="saveVideo({{ $index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="saveVideo({{ $index }})"
                                        title="{{ __('Save') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                        </svg>
                                        <span>{{ __('Save') }}</span>
                                    </button>

                                    <a
                                        href="#"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                        wire:click.prevent="downloadVideo({{ $index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="downloadVideo({{ $index }})"
                                        title="{{ __('Download') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        <span>{{ __('Download') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                    <flux:icon.film variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Your generated video will appear here.') }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
