<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <header class="mb-6">
        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Saved Images') }}</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('View and manage your saved AI-generated images.') }}
        </p>
    </header>

    @if (session()->has('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/50 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('message'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="mb-6 space-y-4 rounded-lg border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="filter-tool" class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Tool') }}
                </label>
                <select 
                    id="filter-tool" 
                    wire:model.live="filterTool" 
                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    <option value="">{{ __('All Tools') }}</option>
                    @foreach($availableTools as $tool)
                        <option value="{{ $tool }}">{{ ucwords(str_replace('-', ' ', $tool)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-date-from" class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('From Date') }}
                </label>
                <input 
                    type="date" 
                    id="filter-date-from" 
                    wire:model.live="filterDateFrom" 
                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
            </div>

            <div>
                <label for="filter-date-to" class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('To Date') }}
                </label>
                <input 
                    type="date" 
                    id="filter-date-to" 
                    wire:model.live="filterDateTo" 
                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
            </div>
        </div>
    </div>

    <!-- Images Grid -->
    @if($savedImages->isEmpty())
        <div class="flex min-h-[300px] flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed border-zinc-200 bg-zinc-50 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.photo variant="outline" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('No saved images found.') }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @foreach($savedImages as $imageJob)
                @if($imageJob->generated_images)
                    @foreach($imageJob->generated_images as $index => $image)
                        <div class="group relative overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="aspect-square cursor-pointer overflow-hidden" wire:click="viewImage({{ $imageJob->id }}, {{ $index }})">
                                @if(isset($image['url']))
                                    <img 
                                        src="{{ $image['url'] }}" 
                                        alt="Generated image" 
                                        class="h-full w-full object-cover transition group-hover:scale-105"
                                    >
                                @elseif(isset($image['path']))
                                    <img 
                                        src="{{ Storage::disk('public')->url($image['path']) }}" 
                                        alt="Generated image" 
                                        class="h-full w-full object-cover transition group-hover:scale-105"
                                    >
                                @endif
                            </div>
                            
                            <div class="p-3">
                                <p class="mb-1 truncate text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ Str::limit($imageJob->input_json['prompt'] ?? 'No prompt', 50) }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $imageJob->created_at->format('M d, Y') }}
                                </p>
                                
                                <div class="mt-2 flex gap-2">
                                    <button 
                                        wire:click="downloadImage({{ $imageJob->id }}, {{ $index }})" 
                                        class="flex-1 rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 transition hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
                                    >
                                        {{ __('Download') }}
                                    </button>
                                    <button 
                                        wire:click="deleteImage({{ $imageJob->id }}, {{ $index }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this image?') }}"
                                        class="rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700 transition hover:bg-red-200 dark:bg-red-900/50 dark:text-red-300 dark:hover:bg-red-900/70"
                                    >
                                        {{ __('Delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $savedImages->links() }}
        </div>
    @endif

    <!-- Image Modal -->
    @if($showImageModal && $selectedImage)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" wire:click="closeImageModal"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:align-middle dark:bg-zinc-900">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 dark:bg-zinc-900">
                        <div class="space-y-4">
                            <!-- Image -->
                            <div class="relative">
                                @if(isset($selectedImage['image']['url']))
                                    <img 
                                        src="{{ $selectedImage['image']['url'] }}" 
                                        alt="Generated image" 
                                        class="w-full rounded-lg"
                                    >
                                @elseif(isset($selectedImage['image']['path']))
                                    <img 
                                        src="{{ Storage::disk('public')->url($selectedImage['image']['path']) }}" 
                                        alt="Generated image" 
                                        class="w-full rounded-lg"
                                    >
                                @endif
                            </div>

                            <!-- Details -->
                            <div class="space-y-2">
                                <div>
                                    <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Prompt') }}</h4>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $selectedImage['prompt'] }}</p>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Model') }}</h4>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $selectedImage['model'] }}</p>
                                    </div>
                                    
                                    <div>
                                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Created') }}</h4>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $selectedImage['created_at']->format('M d, Y g:i A') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-zinc-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-zinc-800">
                        <button 
                            type="button" 
                            wire:click="downloadImage({{ $selectedImage['job']->id }}, {{ $selectedImage['index'] }})" 
                            class="inline-flex w-full justify-center rounded-md bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            {{ __('Download') }}
                        </button>
                        <button 
                            type="button" 
                            wire:click="closeImageModal" 
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-base font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 sm:mt-0 sm:w-auto sm:text-sm"
                        >
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>