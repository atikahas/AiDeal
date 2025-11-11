
<div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Video Suite') }}</h1>
        <p class="max-w-4xl text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Generate videos and create storyboards with AI. Transform your ideas into dynamic video content with just a few clicks.') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap gap-2">
            @foreach([
                'video-generation' => __('Video Generation'),
                'video-storyboard' => __('Video Storyboard'),
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

    <div class="grid gap-6">
        @if ($activeTab === 'video-generation')
            <livewire:ai-video-idea-suite.video-generation />
        @elseif($activeTab === 'video-storyboard')
            <livewire:ai-video-idea-suite.video-storyboard />
        @endif
    </div>
</div>
