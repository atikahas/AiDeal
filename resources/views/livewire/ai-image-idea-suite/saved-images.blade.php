<div class="space-y-4">
    @if (session()->has('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/50 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('message'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
            {{ session('message') }}
        </div>
    @endif

    <!-- Header with Filters -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Saved Images') }}</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $savedImages->total() }} {{ __('images') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <select
                wire:model.live="filterTool"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            >
                <option value="">{{ __('All Tools') }}</option>
                @foreach($availableTools as $tool)
                    <option value="{{ $tool }}">{{ ucwords(str_replace('-', ' ', $tool)) }}</option>
                @endforeach
            </select>

            <input
                type="date"
                wire:model.live="filterDateFrom"
                placeholder="{{ __('From') }}"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            >

            <input
                type="date"
                wire:model.live="filterDateTo"
                placeholder="{{ __('To') }}"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            >
        </div>
    </div>

    <!-- Images Grid -->
    @if($savedImages->isEmpty())
        <div class="flex min-h-[400px] flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed border-zinc-200 bg-zinc-50/50 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
            <div class="rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                <svg class="h-12 w-12 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('No saved images') }}
                </p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Start generating and saving images to see them here') }}
                </p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach($savedImages as $imageJob)
                @if($imageJob->generated_images)
                    @foreach($imageJob->generated_images as $index => $image)
                        <div class="group relative aspect-square overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="absolute inset-0 cursor-pointer" wire:click="viewImage({{ $imageJob->id }}, {{ $index }})">
                                @if(isset($image['path']))
                                    <img
                                        src="{{ Storage::disk('public')->url($image['path']) }}"
                                        alt="Generated image"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-110"
                                        loading="lazy"
                                    >
                                @elseif(isset($image['url']))
                                    <img
                                        src="{{ $image['url'] }}"
                                        alt="Generated image"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-110"
                                        loading="lazy"
                                    >
                                @endif
                            </div>

                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 transition-opacity group-hover:opacity-100">
                                <div class="flex gap-2">
                                    <button
                                        wire:click.stop="downloadImage({{ $imageJob->id }}, {{ $index }})"
                                        class="rounded-lg bg-white/90 p-2 text-zinc-700 shadow-lg backdrop-blur-sm transition hover:bg-white"
                                        title="{{ __('Download') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </button>

                                    <button
                                        wire:click.stop="deleteImage({{ $imageJob->id }}, {{ $index }})"
                                        wire:confirm="{{ __('Delete this image?') }}"
                                        class="rounded-lg bg-red-500/90 p-2 text-white shadow-lg backdrop-blur-sm transition hover:bg-red-500"
                                        title="{{ __('Delete') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Tool Badge -->
                            <div class="absolute left-2 top-2 rounded bg-black/60 px-2 py-0.5 text-xs font-medium text-white backdrop-blur-sm">
                                {{ ucfirst(str_replace(['-', '_'], ' ', $imageJob->tool)) }}
                            </div>
                        </div>
                    @endforeach
                @endif
            @endforeach
        </div>

        <!-- Pagination -->
        @if($savedImages->hasPages())
            <div class="mt-6">
                {{ $savedImages->links() }}
            </div>
        @endif
    @endif

    <!-- Image Modal -->
    @if($showImageModal && $selectedImage)
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
                        @if(isset($selectedImage['image']['path']))
                            <img
                                src="{{ Storage::disk('public')->url($selectedImage['image']['path']) }}"
                                alt="Generated image"
                                class="mx-auto max-h-[70vh] w-auto object-contain"
                            >
                        @elseif(isset($selectedImage['image']['url']))
                            <img
                                src="{{ $selectedImage['image']['url'] }}"
                                alt="Generated image"
                                class="mx-auto max-h-[70vh] w-auto object-contain"
                            >
                        @endif
                    </div>

                    <!-- Details -->
                    <div class="space-y-3 p-4 sm:p-6">
                        <!-- Prompt -->
                        @if(!empty($selectedImage['prompt']))
                            <div>
                                <h4 class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Prompt') }}</h4>
                                <p class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedImage['prompt'] }}</p>
                            </div>
                        @endif

                        <!-- Meta Info -->
                        <div class="flex flex-wrap gap-4 text-xs text-zinc-600 dark:text-zinc-400">
                            @if(!empty($selectedImage['model']))
                                <div class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                    <span>{{ $selectedImage['model'] }}</span>
                                </div>
                            @endif

                            <div class="flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ $selectedImage['created_at']->format('M d, Y') }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 pt-2">
                            <button
                                wire:click="downloadImage({{ $selectedImage['job']->id }}, {{ $selectedImage['index'] }})"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
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