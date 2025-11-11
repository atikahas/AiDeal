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

        <form wire:submit.prevent="generateStoryboard" class="space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="video-idea">
                    {{ __('Video Idea / Concept') }} <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="video-idea"
                    wire:model.defer="videoIdea"
                    rows="4"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="{{ __('Describe your video concept or storyline...') }}"
                ></textarea>
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
                    <span wire:loading.remove wire:target="generateStoryboard">{{ __('Generate Storyboard') }}</span>
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
                <div class="space-y-4">
                    @foreach($storyboardOutput as $index => $scene)
                        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Scene') }} {{ $index + 1 }}: {{ $scene['title'] ?? '' }}</h3>
                                @if(isset($scene['duration']))
                                    <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ $scene['duration'] }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $scene['description'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                    <flux:icon.clipboard-document-list variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Your video storyboard will appear here.') }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
