<?php

use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Volt\Component;

new class extends Component {
    use WithFileUploads;

    public string $activeTab = 'staff-magika';

    public array $staffAgents = [
        ['key' => 'wan', 'name' => 'Wan', 'role' => 'Ideal Customer Persona'],
        ['key' => 'tina', 'name' => 'Tina', 'role' => 'Fear & Desire'],
        ['key' => 'jamil', 'name' => 'Jamil', 'role' => 'Marketing Angle'],
        ['key' => 'najwa', 'name' => 'Najwa', 'role' => 'Copywriter'],
        ['key' => 'saifuz', 'name' => 'Saifuz', 'role' => 'Copy Variations'],
        ['key' => 'mieya', 'name' => 'Mieya', 'role' => 'Formula Copywriting (AIDA)'],
        ['key' => 'afiq', 'name' => 'Afiq', 'role' => 'Sales Page Creator'],
        ['key' => 'julia', 'name' => 'Julia', 'role' => 'Headline Brainstormer'],
        ['key' => 'mazrul', 'name' => 'Mazrul', 'role' => 'Script Writer'],
        ['key' => 'musa', 'name' => 'Musa', 'role' => 'LinkedIn Branding'],
        ['key' => 'joe', 'name' => 'Joe', 'role' => 'Image Prompter'],
        ['key' => 'zaki', 'name' => 'Zaki', 'role' => 'Poster Prompter'],
    ];

    public string $selectedStaff = 'wan';
    public string $staffInput = '';
    public ?string $staffOutput = null;

    public string $contentTopic = '';
    public string $contentLanguage = 'English';
    public array $contentIdeasOutput = [];

    public string $marketingProduct = '';
    public string $marketingAudience = '';
    public string $marketingKeywords = '';
    public string $marketingTone = 'Professional';
    public string $marketingLanguage = 'English';
    public ?array $marketingOutput = null;

    public ?\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $productPhoto = null;
    public string $productDescription = '';
    public string $storyVibe = 'Random';
    public string $storyLighting = 'Random';
    public string $storyContentType = 'Random';
    public string $storyLanguage = 'English';
    public array $storyOutput = [];

    public array $languages = ['English', 'Malay', 'Spanish', 'German', 'French'];
    public array $tones = ['Professional', 'Friendly', 'Bold', 'Playful', 'Conversational'];
    public array $storyVibes = ['Random', 'Inspirational', 'Bold', 'Playful', 'Premium'];
    public array $storyLightings = ['Random', 'Bright', 'Moody', 'Natural', 'Studio'];
    public array $storyContentTypes = ['Random', 'Product Ad', 'Founder Story', 'Tutorial', 'Lifestyle'];

    protected string $layout = 'layouts.app';

    public function withLayoutData(): array
    {
        return [
            'title' => __('AI Content Idea Suite'),
        ];
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function selectStaff(string $staffKey): void
    {
        $this->selectedStaff = $staffKey;
        $this->staffOutput = null;
    }

    public function generateStaffOutput(): void
    {
        $this->validate([
            'staffInput' => ['required', 'string', 'min:10'],
        ]);

        $agent = collect($this->staffAgents)->firstWhere('key', $this->selectedStaff);

        $personaHeading = $agent
            ? sprintf('%s — %s', $agent['name'], $agent['role'])
            : __('Selected Agent');

        $this->staffOutput = <<<MARKDOWN
### {$personaHeading}

**Focus Input**
{$this->staffInput}

**Key Insights**
1. {$this->generateStaffInsight('Pain point')}
2. {$this->generateStaffInsight('Opportunity')}
3. {$this->generateStaffInsight('Call to action')}
MARKDOWN;
    }

    protected function generateStaffInsight(string $type): string
    {
        $topic = Str::headline($this->staffInput ?: __('your product or service'));
        return match ($type) {
            'Pain point' => "Customers struggle with {$topic} because it feels overwhelming without clear guidance.",
            'Opportunity' => "{$topic} can stand out by highlighting a quick transformation and tangible proof.",
            default => "Invite the audience to take the next step with a confident and time-bound offer.",
        };
    }

    public function resetStaffForm(): void
    {
        $this->staffInput = '';
        $this->staffOutput = null;
    }

    public function generateContentIdeas(): void
    {
        $this->validate([
            'contentTopic' => ['required', 'string', 'min:4'],
        ]);

        $keyword = Str::headline($this->contentTopic);

        $this->contentIdeasOutput = collect(range(1, 5))
            ->map(fn ($index) => [
                'title' => "{$keyword}: Idea {$index}",
                'angle' => __('Fresh talking point #:index around :topic', [
                    'index' => $index,
                    'topic' => strtolower($keyword),
                ]),
                'hook' => __('Hook your audience with a quick win or trend-backed insight.'),
            ])
            ->all();
    }

    public function resetContentIdeas(): void
    {
        $this->contentTopic = '';
        $this->contentLanguage = 'English';
        $this->contentIdeasOutput = [];
    }

    public function generateMarketingCopy(): void
    {
        $this->validate([
            'marketingProduct' => ['required', 'string', 'min:6'],
            'marketingTone' => ['required', 'string'],
        ]);

        $audience = $this->marketingAudience ?: __('busy decision makers');
        $keywords = array_filter(array_map('trim', explode(',', $this->marketingKeywords)));

        $this->marketingOutput = [
            'headline' => Str::headline($this->marketingProduct),
            'body' => __(
                'Hey :audience, meet :product — designed to solve your biggest challenge in under 10 minutes.',
                [
                    'audience' => strtolower($audience),
                    'product' => strtolower($this->marketingProduct),
                ],
            ),
            'cta' => __('Start your free trial today and see instant results.'),
            'keywords' => $keywords,
        ];
    }

    public function resetMarketingCopy(): void
    {
        $this->marketingProduct = '';
        $this->marketingAudience = '';
        $this->marketingKeywords = '';
        $this->marketingTone = 'Professional';
        $this->marketingLanguage = 'English';
        $this->marketingOutput = null;
    }

    public function generateStoryline(): void
    {
        $this->validate([
            'productPhoto' => ['nullable', 'image', 'max:5120'],
            'productDescription' => ['required', 'string', 'min:10'],
        ]);

        $base = Str::headline($this->productDescription);

        $this->storyOutput = [
            [
                'label' => __('Scene 1'),
                'description' => __('Opening hero shot showcasing :base with :lighting lighting and :vibe energy.', [
                    'base' => strtolower($base),
                    'lighting' => strtolower($this->storyLighting),
                    'vibe' => strtolower($this->storyVibe),
                ]),
            ],
            [
                'label' => __('Scene 2'),
                'description' => __('Demonstrate the core benefit in action with quick cut visuals and bold captions.'),
            ],
            [
                'label' => __('Scene 3'),
                'description' => __('End with a confident CTA overlay inviting viewers to experience :base today.', [
                    'base' => strtolower($base),
                ]),
            ],
        ];
    }

    public function resetStoryline(): void
    {
        $this->productPhoto = null;
        $this->productDescription = '';
        $this->storyVibe = 'Random';
        $this->storyLighting = 'Random';
        $this->storyContentType = 'Random';
        $this->storyLanguage = 'English';
        $this->storyOutput = [];
    }
}; ?>

<div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Content Idea Suite') }}</h1>
        <p class="max-w-3xl text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Discover ideas, craft marketing assets, and storyboard product ads with a collaborative team of AI specialists.') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap gap-2">
            @foreach ([
                'staff-magika' => __('Staff Magika'),
                'content-ideas' => __('Content Ideas'),
                'marketing-copy' => __('Marketing Copy'),
                'product-storyline' => __('Product Ad Storyline'),
            ] as $tabKey => $label)
                <button
                    type="button"
                    wire:click="setActiveTab('{{ $tabKey }}')"
                    @class([
                        'rounded-lg px-4 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500',
                        'bg-purple-600 text-white shadow-sm' => $activeTab === $tabKey,
                        'bg-transparent text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100' => $activeTab !== $tabKey,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[360px,1fr] xl:grid-cols-[380px,1fr]">
        @if ($activeTab === 'staff-magika')
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Magika Persona') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Your expert AI team for marketing tasks. Select an agent and describe the task to get personalised insights.') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-4 gap-3">
                        @foreach ($staffAgents as $agent)
                            <button
                                type="button"
                                wire:click="selectStaff('{{ $agent['key'] }}')"
                                @class([
                                    'flex flex-col rounded-lg border px-4 py-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500',
                                    'border-purple-200 bg-purple-50 text-purple-900 shadow-sm dark:border-purple-500/60 dark:bg-purple-500/10 dark:text-purple-100' => $selectedStaff === $agent['key'],
                                    'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' => $selectedStaff !== $agent['key'],
                                ])
                            >
                                <span class="text-sm font-semibold">{{ $agent['name'] }}</span>
                                <span class="mt-1 text-xs text-inherit/70">{{ $agent['role'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="staff-input">{{ __('Input for agent') }}</label>
                        <textarea
                            id="staff-input"
                            wire:model.defer="staffInput"
                            rows="4"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:ring-purple-400"
                            placeholder="{{ __('Describe your product or campaign...') }}"
                        ></textarea>
                        @error('staffInput')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="content-language">{{ __('Output Language') }}</label>
                            <select
                                id="content-language"
                                wire:model="contentLanguage"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                @foreach ($languages as $language)
                                    <option value="{{ $language }}">{{ $language }}</option>
                                @endforeach
                            </select>
                        </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="generateStaffOutput"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 disabled:opacity-70"
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
                        @if ($staffOutput)
                            <article class="prose max-w-none text-zinc-800 dark:prose-invert dark:text-zinc-100">
                                {!! Str::markdown($staffOutput) !!}
                            </article>
                        @else
                            <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                                <flux:icon.sparkles variant="outline" class="size-10 text-purple-300 dark:text-purple-400" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('The AI\'s response will appear here.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($activeTab === 'content-ideas')
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Content Idea Generator') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Discover trending and engaging content ideas for any topic using fresh search data.') }}
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="content-topic">{{ __('Your Topic or Niche') }}</label>
                            <textarea
                                id="content-topic"
                                wire:model.defer="contentTopic"
                                rows="4"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
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
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
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
                            class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 disabled:opacity-70"
                        >
                            <span wire:loading.remove wire:target="generateContentIdeas">{{ __('Generate Ideas') }}</span>
                            <span wire:loading wire:target="generateContentIdeas">{{ __('Generating...') }}</span>
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
                    </header>

                    <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
                        @if ($contentIdeasOutput)
                            <div class="grid gap-4">
                                @foreach ($contentIdeasOutput as $idea)
                                    <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-50">{{ $idea['title'] }}</h3>
                                        <p class="mt-2 text-sm text-purple-600 dark:text-purple-300">{{ $idea['angle'] }}</p>
                                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $idea['hook'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                                <flux:icon.arrow-trending-up variant="outline" class="size-10 text-purple-300 dark:text-purple-400" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Your generated content ideas will appear here.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($activeTab === 'marketing-copy')
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('AI Marketing Copywriter') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Generate persuasive copy for ads, posts, and websites with customizable tone and language.') }}
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-product">{{ __('Product/Service Details') }}</label>
                            <textarea
                                id="marketing-product"
                                wire:model.defer="marketingProduct"
                                rows="4"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="{{ __('e.g., a high-end coffee maker that brews in 30 seconds...') }}"
                            ></textarea>
                            @error('marketingProduct')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-audience">{{ __('Target Audience (Optional)') }}</label>
                                <input
                                    id="marketing-audience"
                                    type="text"
                                    wire:model.defer="marketingAudience"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                    placeholder="{{ __('e.g., busy professionals, coffee lovers...') }}"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-keywords">{{ __('Keywords to Include (Optional)') }}</label>
                                <input
                                    id="marketing-keywords"
                                    type="text"
                                    wire:model.defer="marketingKeywords"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                    placeholder="{{ __('e.g., quick, premium, morning coffee') }}"
                                />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-tone">{{ __('Tone of Voice') }}</label>
                                <select
                                    id="marketing-tone"
                                    wire:model="marketingTone"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($tones as $tone)
                                        <option value="{{ $tone }}">{{ $tone }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="marketing-language">{{ __('Output Language') }}</label>
                                <select
                                    id="marketing-language"
                                    wire:model="marketingLanguage"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($languages as $language)
                                        <option value="{{ $language }}">{{ $language }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="generateMarketingCopy"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 disabled:opacity-70"
                        >
                            <span wire:loading.remove wire:target="generateMarketingCopy">{{ __('Generate Copy') }}</span>
                            <span wire:loading wire:target="generateMarketingCopy">{{ __('Generating...') }}</span>
                        </button>
                        <button
                            type="button"
                            wire:click="resetMarketingCopy"
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
                        @if ($marketingOutput)
                            <article class="prose max-w-none text-zinc-800 dark:prose-invert dark:text-zinc-100">
                                <h3>{{ $marketingOutput['headline'] }}</h3>
                                <p>{{ $marketingOutput['body'] }}</p>
                                <p><strong>{{ __('Call to Action:') }}</strong> {{ $marketingOutput['cta'] }}</p>
                                @if (!empty($marketingOutput['keywords']))
                                    <p class="mt-4 text-xs uppercase tracking-wide text-purple-500">{{ __('Suggested Keywords:') }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($marketingOutput['keywords'] as $keyword)
                                            <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700 dark:bg-purple-500/10 dark:text-purple-200">{{ $keyword }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @else
                            <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                                <flux:icon.speaker-wave variant="outline" class="size-10 text-purple-300 dark:text-purple-400" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Your generated marketing copy will appear here.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Product Ad Storyline') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Generate a short, punchy video ad concept. Upload an image and describe your product to build a 1-scene storyline.') }}
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="product-photo">{{ __('Upload Product Photo') }}</label>
                            <label
                                for="product-photo"
                                class="flex min-h-[140px] cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 text-sm text-zinc-500 transition hover:border-purple-400 hover:text-purple-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400 dark:hover:border-purple-400 dark:hover:text-purple-300"
                            >
                                @if ($productPhoto)
                                    <img src="{{ $productPhoto->temporaryUrl() }}" alt="{{ __('Uploaded preview') }}" class="h-32 w-32 rounded-lg object-cover shadow-sm" />
                                    <span class="mt-3 text-xs text-zinc-400">{{ __('Click to change image') }}</span>
                                @else
                                    <flux:icon.photo variant="outline" class="mb-3 size-10 text-purple-300" />
                                    <span class="text-sm font-medium">{{ __('Upload image') }}</span>
                                    <span class="mt-1 text-xs text-zinc-400">{{ __('PNG or JPG up to 5MB') }}</span>
                                @endif
                            </label>
                            <input id="product-photo" type="file" class="hidden" wire:model="productPhoto" accept="image/*" />
                            @error('productPhoto')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="product-description">{{ __('Product Description') }}</label>
                            <textarea
                                id="product-description"
                                wire:model.defer="productDescription"
                                rows="4"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="{{ __('e.g., Organic coffee beans from Brazil, single-origin, rich aroma...') }}"
                            ></textarea>
                            @error('productDescription')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="story-vibe">{{ __('Vibe / Mood') }}</label>
                                <select
                                    id="story-vibe"
                                    wire:model="storyVibe"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($storyVibes as $vibe)
                                        <option value="{{ $vibe }}">{{ $vibe }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="story-lighting">{{ __('Lighting') }}</label>
                                <select
                                    id="story-lighting"
                                    wire:model="storyLighting"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($storyLightings as $lighting)
                                        <option value="{{ $lighting }}">{{ $lighting }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="story-content-type">{{ __('Content Type') }}</label>
                                <select
                                    id="story-content-type"
                                    wire:model="storyContentType"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($storyContentTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="story-language">{{ __('Output Language') }}</label>
                                <select
                                    id="story-language"
                                    wire:model="storyLanguage"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    @foreach ($languages as $language)
                                        <option value="{{ $language }}">{{ $language }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="generateStoryline"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 disabled:opacity-70"
                        >
                            <span wire:loading.remove wire:target="generateStoryline">{{ __('Generate Storyline') }}</span>
                            <span wire:loading wire:target="generateStoryline">{{ __('Generating...') }}</span>
                        </button>
                        <button
                            type="button"
                            wire:click="resetStoryline"
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
                        @if ($storyOutput)
                            <div class="grid gap-4">
                                @foreach ($storyOutput as $scene)
                                    <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-50">{{ $scene['label'] }}</h3>
                                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $scene['description'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                                <flux:icon.video-camera variant="outline" class="size-10 text-purple-300 dark:text-purple-400" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Your generated storyboard will appear here.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
