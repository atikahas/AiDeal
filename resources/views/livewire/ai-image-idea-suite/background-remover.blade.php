<div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
    <!-- Left Column: Upload Section -->
    <div class="flex flex-col gap-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Background Remover') }}</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Remove background from your image with a clean transparent result.') }}
            </p>
        </div>

        @if (session()->has('error') || session()->has('message'))
            <div class="rounded-lg border px-4 py-3 text-sm {{ session()->has('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/50 dark:text-red-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' }}">
                {{ session('error') ?? session('message') }}
            </div>
        @endif

        <form wire:submit.prevent="removeBackground" class="space-y-4">
            <!-- Image Upload -->
            <div class="space-y-2" x-data="{ isDragging: false }">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Upload Image') }} <span class="text-red-500">*</span>
                </label>

                @if($image)
                    @php
                        try {
                            $previewUrl = $image->temporaryUrl();
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
                                wire:click="$set('image', null)"
                                class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-zinc-900 shadow-lg transition hover:bg-zinc-100"
                            >
                                {{ __('Change Image') }}
                            </button>
                        </div>
                    </div>
                @else
                    <label
                        for="upload-image"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="isDragging = false"
                        :class="isDragging ? 'border-zinc-900 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-800' : 'border-zinc-300 dark:border-zinc-600'"
                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed bg-white px-6 py-10 transition-all hover:border-zinc-400 hover:bg-zinc-50 dark:bg-zinc-950 dark:hover:border-zinc-500 dark:hover:bg-zinc-900"
                        wire:loading.class="pointer-events-none opacity-50"
                        wire:target="image"
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

                        <div wire:loading wire:target="image" class="mt-3">
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
                        id="upload-image"
                        accept="image/png,image/jpeg,image/webp"
                        wire:model="image"
                        class="sr-only"
                        required
                    >
                @endif

                @error('image')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <flux:separator />

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200" wire:loading.attr="disabled" wire:target="removeBackground,image">
                    <span>{{ __('Remove Background') }}</span>
                    <svg wire:loading wire:target="removeBackground" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"></path>
                    </svg>
                </button>
                <button type="button" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" wire:click="resetForm" wire:loading.attr="disabled" wire:target="removeBackground,image">{{ __('Reset') }}</button>
            </div>
        </form>
    </div>

    <!-- Right Column: Output Section -->
    <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <header class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Output') }}</h2>
        </header>

        <div class="min-h-[360px] rounded-lg border border-zinc-100 bg-gradient-to-br from-zinc-50 via-white to-zinc-50 p-6 dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
            @if(empty($generatedImages))
                <div class="flex h-full min-h-[280px] flex-col items-center justify-center gap-3 text-center text-zinc-400">
                    <flux:icon.photo variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Upload an image to remove its background.') }}
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
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
                            <!-- Image with checkerboard background for transparency -->
                            <div class="aspect-square w-full overflow-hidden bg-[linear-gradient(45deg,#f0f0f0_25%,transparent_25%,transparent_75%,#f0f0f0_75%,#f0f0f0),linear-gradient(45deg,#f0f0f0_25%,transparent_25%,transparent_75%,#f0f0f0_75%,#f0f0f0)] bg-[length:20px_20px] bg-[position:0_0,10px_10px] dark:bg-[linear-gradient(45deg,#404040_25%,transparent_25%,transparent_75%,#404040_75%,#404040),linear-gradient(45deg,#404040_25%,transparent_25%,transparent_75%,#404040_75%,#404040)]">
                                @if($src)
                                    <img
                                        src="{{ $src }}"
                                        alt="Background removed {{ $index + 1 }}"
                                        class="h-full w-full object-contain transition-opacity group-hover:opacity-90"
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
    @if ($showImageModal && $viewingImageIndex !== null && isset($generatedImages[$viewingImageIndex]))
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data @keydown.escape.window="$wire.closeImageModal()">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/75 transition-opacity" @click="$wire.closeImageModal()"></div>

                <div class="relative z-10 w-full max-w-5xl">
                    <div class="relative overflow-hidden rounded-lg bg-white shadow-xl dark:bg-zinc-900">
                        <!-- Close button -->
                        <button
                            type="button"
                            class="absolute right-4 top-4 z-10 rounded-lg bg-zinc-900/75 p-2 text-white backdrop-blur-sm transition hover:bg-zinc-900 dark:bg-zinc-100/75 dark:text-zinc-900 dark:hover:bg-zinc-100"
                            @click="$wire.closeImageModal()"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Image with checkerboard background -->
                        <div class="flex max-h-[80vh] items-center justify-center overflow-auto bg-[linear-gradient(45deg,#f0f0f0_25%,transparent_25%,transparent_75%,#f0f0f0_75%,#f0f0f0),linear-gradient(45deg,#f0f0f0_25%,transparent_25%,transparent_75%,#f0f0f0_75%,#f0f0f0)] bg-[length:20px_20px] bg-[position:0_0,10px_10px] p-8 dark:bg-[linear-gradient(45deg,#404040_25%,transparent_25%,transparent_75%,#404040_75%,#404040),linear-gradient(45deg,#404040_25%,transparent_25%,transparent_75%,#404040_75%,#404040)]">
                            @php
                                $modalSrc = $generatedImages[$viewingImageIndex]['url'] ?? null;
                                if (! $modalSrc && isset($generatedImages[$viewingImageIndex]['data'])) {
                                    $mime = $generatedImages[$viewingImageIndex]['mime'] ?? 'image/png';
                                    $modalSrc = 'data:' . $mime . ';base64,' . $generatedImages[$viewingImageIndex]['data'];
                                }
                            @endphp
                            <img
                                src="{{ $modalSrc }}"
                                alt="Background removed image"
                                class="max-h-full max-w-full rounded-lg object-contain"
                            >
                        </div>

                        <!-- Action buttons -->
                        <div class="flex items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                                    wire:click="saveImage({{ $viewingImageIndex }})"
                                    wire:loading.attr="disabled"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                    <span>{{ __('Save') }}</span>
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                    wire:click="downloadImage({{ $viewingImageIndex }})"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>{{ __('Download') }}</span>
                                </button>
                            </div>

                            <button
                                type="button"
                                class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                @click="$wire.closeImageModal()"
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
