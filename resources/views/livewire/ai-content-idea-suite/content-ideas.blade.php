<div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
    <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Content Idea Generator') }}</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Discover trending and engaging content ideas for any topic using fresh search data.') }}
            </p>
        </div>

        @if (session()->has('error') || session()->has('message'))
            <div class="rounded-lg border px-4 py-3 text-sm {{ session()->has('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/50 dark:text-red-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' }}">
                {{ session('error') ?? session('message') }}
            </div>
        @endif

        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="content-topic">{{ __('Your Topic or Niche') }}</label>
                <textarea
                    id="content-topic"
                    wire:model.defer="contentTopic"
                    rows="4"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="{{ __('e.g., digital marketing for small business or healthy breakfast recipes') }}"
                ></textarea>
                @error('contentTopic')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="content-language">{{ __('Output Language') }}</label>
                <select
                    id="content-language"
                    wire:model="contentLanguage"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                >
                    @foreach ($languages as $language)
                        <option value="{{ $language }}">{{ $language }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                wire:click="generateContentIdeas"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                wire:target="generateContentIdeas"
            >
                <span>{{ __('Generate Ideas') }}</span>
                <svg wire:loading wire:target="generateContentIdeas" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"></path>
                </svg>
            </button>
            <button
                type="button"
                wire:click="resetContentIdeas"
                class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
            >
                {{ __('Reset') }}
            </button>
        </div>
    </div>

    <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <header class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
            @if (count($contentIdeasOutput) > 0)
                <div class="flex space-x-2">
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        x-on:click="
                            const outputText = @js(collect($contentIdeasOutput)->map(function($idea) {
                                return '# ' . ($idea['title'] ?? 'Untitled Idea') . '\n' .
                                       (isset($idea['angle']) ? $idea['angle'] . '\n' : '') .
                                       (isset($idea['hook']) ? '\"' . $idea['hook'] . '\"' : '');
                            })->filter()->join('\n\n'));
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
                        <span x-text="copied ? 'Copied!' : 'Copy All'"></span>
                    </button>
                    <button
                        type="button"
                        x-data="{ saved: false }"
                        x-on:click="
                            const content = @js(collect($contentIdeasOutput)->map(function($idea) {
                                return '# ' . ($idea['title'] ?? 'Untitled Idea') . '\n' .
                                       (isset($idea['angle']) ? $idea['angle'] . '\n' : '') .
                                       (isset($idea['hook']) ? '\"' . $idea['hook'] . '\"' : '');
                            })->filter()->join('\n\n'));
                            const blob = new Blob([content], { type: 'text/plain' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'content-ideas-' + new Date().toISOString().slice(0, 10) + '.txt';
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 012 2h14a2 2 0 012-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        <svg x-show="saved" class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span x-text="saved ? 'Saved!' : 'Save All as TXT'"></span>
                    </button>
                </div>
            @endif
        </header>

        <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
            @if (count($contentIdeasOutput) > 0)
                <div class="space-y-4">
                    @foreach ($contentIdeasOutput as $index => $idea)
                        <div class="group relative rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-50">{{ $idea['title'] ?? 'Untitled Idea' }}</h3>
                            @if(isset($idea['angle']))
                                <p class="mt-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $idea['angle'] }}</p>
                            @endif
                            @if(isset($idea['hook']))
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400 italic">"{{ $idea['hook'] }}"</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                    <flux:icon.speaker-wave variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Generate content ideas to see the output here.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
