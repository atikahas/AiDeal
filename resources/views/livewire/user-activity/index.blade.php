<div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-2">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">AI Activity Logs</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">View and manage your AI activity history</p>
    </div>

    <!-- Filters -->
    <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <!-- Search Input -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Search</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pl-10 text-sm text-zinc-900 placeholder-zinc-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400 dark:focus:border-blue-500 dark:focus:ring-blue-500/50" 
                        placeholder="Search prompts & outputs..."
                    >
                </div>
            </div>
            
            <!-- Activity Type Select -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Activity Type</label>
                <select 
                    wire:model.live="activityType"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400 dark:focus:border-blue-500 dark:focus:ring-blue-500/50"
                >
                    <option value="">All activity types</option>
                    @foreach($activityTypes as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Model Select -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Model</label>
                <select 
                    wire:model.live="model"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400 dark:focus:border-blue-500 dark:focus:ring-blue-500/50"
                >
                    <option value="">All models</option>
                    @foreach($models as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Status Select -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Status</label>
                <select 
                    wire:model.live="status"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400 dark:focus:border-blue-500 dark:focus:ring-blue-500/50"
                >
                    <option value="">All statuses</option>
                    <option value="success">Success</option>
                    <option value="error">Error</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            
            <!-- Date Range -->
            <div class="space-y-1 md:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Date Range</label>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <div class="relative">
                            <input 
                                type="date" 
                                wire:model.live="startDate"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400 dark:focus:border-blue-500 dark:focus:ring-blue-500/50"
                            >
                        </div>
                    </div>
                    <div>
                        <div class="relative">
                            <input 
                                type="date" 
                                wire:model.live="endDate"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400 dark:focus:border-blue-500 dark:focus:ring-blue-500/50"
                            >
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex flex-col gap-3 md:col-span-2 sm:flex-row sm:items-end sm:justify-between">
                <button 
                    type="button"
                    wire:click="resetFilters"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:ring-offset-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 dark:focus:ring-offset-zinc-900"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Reset Filters
                </button>
                
                <div class="w-full sm:w-40">
                    <label class="sr-only" for="per-page">Items per page</label>
                    <select 
                        id="per-page"
                        wire:model.live="perPage"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-400 dark:focus:border-blue-500 dark:focus:ring-blue-500/50"
                    >
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <div class="min-w-full align-middle
                [&:has(thead_[data-header]):has(tbody_[data-row])]:divide-y [&:has(thead_[data-header]):has(tbody_[data-row])]:divide-zinc-200
                [&:has(thead_[data-header]):has(tbody_[data-row])]:dark:divide-zinc-700"
            >
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr data-header>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 sm:px-6">
                                Activity
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Model
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Tokens
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Status
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Date
                            </th>
                            <th scope="col" class="relative px-4 py-3 sm:px-6">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @forelse($logs as $log)
                            <tr 
                                data-row 
                                class="transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50"
                            >
                                <td class="whitespace-nowrap px-4 py-4 text-sm font-medium text-zinc-900 dark:text-white sm:px-6">
                                    <div class="font-medium text-zinc-900 dark:text-white">
                                        {{ $log->activity_type }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 line-clamp-1">
                                        {{ Str::limit($log->prompt, 50) }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $log->model }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm tabular-nums text-zinc-500 dark:text-zinc-400">
                                    {{ number_format($log->token_count) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4">
                                    @if($log->status === 'success')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                            Success
                                        </span>
                                    @elseif($log->status === 'error')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Error
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span>
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('F j, Y g:i A') }}">
                                        {{ $log->created_at->diffForHumans() }}
                                    </time>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium sm:px-6">
                                    <button 
                                        type="button" 
                                        wire:click="showDetails('{{ $log->id }}')"
                                        class="text-blue-600 transition-colors hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:ring-offset-2 dark:text-blue-400 dark:hover:text-blue-300 dark:focus:ring-offset-zinc-900"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400 sm:px-6">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="font-medium text-zinc-600 dark:text-zinc-300">No activity logs found</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Your AI activity will appear here</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="border-t border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900 sm:px-6">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-data="{ show: @entangle('showDetailModal') }" 
         x-show="show" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         class="relative z-50" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true"
    >
        <div class="fixed inset-0 bg-zinc-500/75 transition-opacity dark:bg-zinc-900/80" 
             x-show="show" 
             x-transition:enter="ease-out duration-300" 
             x-transition:leave="ease-in duration-200"
             @click="show = false"
             aria-hidden="true">
        </div>

        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 sm:items-center sm:p-0">
                <div x-show="show" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative w-full max-w-4xl transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all dark:bg-zinc-800 sm:my-8">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white" id="modal-title">
                            Activity Details
                        </h3>
                        <button type="button" 
                                @click="show = false" 
                                class="rounded-md p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:ring-offset-2 dark:hover:bg-zinc-700 dark:hover:text-zinc-300 dark:focus:ring-offset-zinc-900">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="px-6 py-5">
                        @if($selectedLog)
                            <div class="space-y-6">
                                <!-- Stats Grid -->
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white px-4 py-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 sm:p-6">
                                        <dt class="truncate text-sm font-medium text-zinc-500 dark:text-zinc-400">Activity Type</dt>
                                        <dd class="mt-1 text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">
                                            {{ $selectedLog->activity_type }}
                                        </dd>
                                    </div>
                                    
                                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white px-4 py-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 sm:p-6">
                                        <dt class="truncate text-sm font-medium text-zinc-500 dark:text-zinc-400">Model</dt>
                                        <dd class="mt-1 text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">
                                            {{ $selectedLog->model }}
                                        </dd>
                                    </div>
                                    
                                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white px-4 py-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 sm:p-6">
                                        <dt class="truncate text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</dt>
                                        <dd class="mt-1">
                                            @if($selectedLog->status === 'success')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                                    Success
                                                </span>
                                            @elseif($selectedLog->status === 'error')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-sm font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                                    Error
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-100 px-2.5 py-1 text-sm font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                    <span class="h-2 w-2 rounded-full bg-yellow-500"></span>
                                                    Pending
                                                </span>
                                            @endif
                                        </dd>
                                    </div>
                                    
                                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white px-4 py-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 sm:p-6">
                                        <dt class="truncate text-sm font-medium text-zinc-500 dark:text-zinc-400">Date</dt>
                                        <dd class="mt-1 text-base font-medium text-zinc-900 dark:text-white">
                                            {{ $selectedLog->created_at->format('M d, Y') }}
                                            <span class="text-zinc-500 dark:text-zinc-400">at {{ $selectedLog->created_at->format('g:i A') }}</span>
                                        </dd>
                                    </div>
                                    
                                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white px-4 py-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 sm:p-6">
                                        <dt class="truncate text-sm font-medium text-zinc-500 dark:text-zinc-400">Tokens</dt>
                                        <dd class="mt-1 text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">
                                            {{ number_format($selectedLog->token_count) }}
                                        </dd>
                                    </div>
                                    
                                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white px-4 py-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 sm:p-6">
                                        <dt class="truncate text-sm font-medium text-zinc-500 dark:text-zinc-400">Latency</dt>
                                        <dd class="mt-1 text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">
                                            {{ $selectedLog->latency_ms ? number_format($selectedLog->latency_ms) . ' ms' : 'N/A' }}
                                        </dd>
                                    </div>
                                </div>
                                
                                <!-- Prompt Section -->
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Prompt</h4>
                                        <button type="button" 
                                                @click="copyToClipboard('{{ addslashes($selectedLog->prompt) }}')" 
                                                class="inline-flex items-center rounded-md bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-600 dark:hover:bg-zinc-700">
                                            <svg class="mr-1.5 h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                            </svg>
                                            Copy
                                        </button>
                                    </div>
                                    <div class="mt-2 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                        <pre class="overflow-x-auto p-4 text-sm text-zinc-800 dark:text-zinc-200">{{ $selectedLog->prompt }}</pre>
                                    </div>
                                </div>
                                
                                @if($selectedLog->output)
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Output</h4>
                                            <button type="button" 
                                                    @click="copyToClipboard('{{ addslashes($selectedLog->output) }}')" 
                                                    class="inline-flex items-center rounded-md bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-600 dark:hover:bg-zinc-700">
                                                <svg class="mr-1.5 h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                                </svg>
                                                Copy
                                            </button>
                                        </div>
                                        <div class="mt-2 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                            <pre class="overflow-x-auto p-4 text-sm text-zinc-800 dark:text-zinc-200">{{ $selectedLog->output }}</pre>
                                        </div>
                                    </div>
                                @endif
                                
                                @if($selectedLog->error_message)
                                    <div>
                                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Error Message</h4>
                                        <div class="mt-2 overflow-hidden rounded-lg border border-red-200 bg-red-50 dark:border-red-900/30 dark:bg-red-900/20">
                                            <pre class="overflow-x-auto p-4 text-sm text-red-700 dark:text-red-400">{{ $selectedLog->error_message }}</pre>
                                        </div>
                                    </div>
                                @endif
                                
                                @if($selectedLog->meta)
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Metadata</h4>
                                            <button type="button" 
                                                    @click="copyToClipboard(JSON.stringify({{ json_encode($selectedLog->meta) }}, null, 2))" 
                                                    class="inline-flex items-center rounded-md bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-600 dark:hover:bg-zinc-700">
                                                <svg class="mr-1.5 h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                                </svg>
                                                Copy
                                            </button>
                                        </div>
                                        <div class="mt-2 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                            <pre class="max-h-60 overflow-auto p-4 text-sm text-zinc-800 dark:text-zinc-200">{{ json_encode($selectedLog->meta, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    
                    <!-- Footer -->
                    <div class="flex justify-end border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <button type="button" 
                                @click="show = false" 
                                class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:bg-zinc-800 dark:text-white dark:ring-zinc-600 dark:hover:bg-zinc-700 dark:focus-visible:ring-offset-zinc-900">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Copy to clipboard script -->
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Optional: Show a toast or tooltip that text was copied
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = `
                    <svg class="mr-1.5 h-3.5 w-3.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6-6 6 6m-6-6v13.5" />
                    </svg>
                    Copied!
                `;
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }
    </script>
</div>
