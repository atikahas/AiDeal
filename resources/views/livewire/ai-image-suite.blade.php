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
    <div class="grid gap-6 lg:grid-cols-[360px,1fr] xl:grid-cols-[380px,1fr]">
        @if ($activeTab === 'image-generation')
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Magika Persona') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Your expert AI team for marketing tasks. Select an agent and describe the task to get personalised insights.') }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="staff-input">{{ __('Input for agent') }}</label>
                        <textarea
                            id="staff-input"
                            wire:model.defer="staffInput"
                            rows="4"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            placeholder="{{ __('Describe your product or campaign...') }}"
                        ></textarea>
                        @error('staffInput')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="generateStaffOutput"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                        >
                            <span wire:loading.remove wire:target="generateStaffOutput">{{ __('Generate Insights') }}</span>
                            <span wire:loading wire:target="generateStaffOutput">{{ __('Generating...') }}</span>
                        </button>
                        <button
                            type="button"
                            wire:click="resetStaffForm"
                            class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        >
                            {{ __('Reset') }}
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <header class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
                    </header>

                    <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
                        <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                            <flux:icon.sparkles variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('The AI\'s response will appear here.') }}</p>
                        </div>
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
