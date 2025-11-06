<?php

use App\Models\ApiKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component {
    public string $provider = 'gemini';
    public string $label = '';
    public string $secret = '';
    public bool $makeActive = true;
    public ?int $selectedKeyId = null;
    public array $testStatuses = [];
    public array $healthCheckResults = [];
    public ?string $healthCheckRanAt = null;
    public ?string $newKeyTestStatus = null;
    public ?string $newKeyTestMessage = null;

    public function mount(): void
    {
        $this->selectedKeyId = Auth::user()?->active_api_key_id
            ?? ApiKey::whereNull('user_id')
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->value('id');

        $this->testStatuses = ApiKey::forUser(Auth::id())
            ->pluck('connection_status', 'id')
            ->map(fn (?string $status) => $status ?: 'unknown')
            ->toArray();
    }

    public function getKeysProperty(): Collection
    {
        return ApiKey::forUser(Auth::id())
            ->orderByRaw('user_id IS NULL DESC')
            ->orderByDesc('is_active')
            ->orderBy('label')
            ->get();
    }

    protected function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:120'],
            'secret' => ['required', 'string', 'min:10'],
            'makeActive' => ['boolean'],
        ];
    }

    public function hydrate(): void
    {
        $this->testStatuses = ApiKey::forUser(Auth::id())
            ->pluck('connection_status', 'id')
            ->map(fn (?string $status) => $status ?: 'unknown')
            ->toArray();
    }

    public function updatedSelectedKeyId($value): void
    {
        if (! $value) {
            return;
        }

        $this->setActiveKey((int) $value);
    }

    public function addKey(): void
    {
        $this->validate();

        if ($this->newKeyTestStatus !== 'connected') {
            $this->addError('secret', __('Please run and pass the connection test before saving this key.'));

            return;
        }

        $apiKey = ApiKey::create([
            'user_id' => Auth::id(),
            'provider' => Str::lower(trim($this->provider)),
            'label' => trim($this->label),
            'secret' => trim($this->secret),
            'is_active' => $this->makeActive,
            'connection_status' => 'unknown',
        ]);

        $this->testStatuses[$apiKey->id] = 'unknown';

        if ($this->makeActive) {
            $this->setActiveKey($apiKey->id, notify: false);
        }

        session()->flash('api_key_notification', __('API key saved successfully.'));

        $this->resetForm();
    }

    public function setActiveKey(int $keyId, bool $notify = true): void
    {
        /** @var \App\Models\ApiKey|null $apiKey */
        $apiKey = ApiKey::forUser(Auth::id())->find($keyId);

        if (! $apiKey) {
            return;
        }

        ApiKey::where('user_id', Auth::id())->update(['is_active' => false]);

        if ($apiKey->user_id === Auth::id()) {
            $apiKey->update(['is_active' => true]);
        }

        $user = Auth::user();
        $user?->forceFill([
            'active_api_key_id' => $apiKey->id,
        ])->save();

        $this->selectedKeyId = $apiKey->id;
        $this->testStatuses[$apiKey->id] = $apiKey->connection_status ?? ($this->testStatuses[$apiKey->id] ?? 'unknown');

        if ($notify) {
            session()->flash('api_key_notification', __('Your default API key has been updated.'));
        }
    }

    public function testConnection(int $keyId): void
    {
        /** @var \App\Models\ApiKey|null $apiKey */
        $apiKey = ApiKey::forUser(Auth::id())->find($keyId);

        if (! $apiKey) {
            return;
        }

        $this->testStatuses[$keyId] = 'testing';

        $success = false;
        $message = '';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey->secret, [
                'contents' => [
                    'parts' => [
                        ['text' => 'Hello']
                    ]
                ]
            ]);

            if ($response->successful()) {
                $success = true;
                $message = __('Connection successful. API key is valid.');
            } else {
                $error = $response->json();
                $message = $error['error']['message'] ?? __('Connection failed. Please verify the key and try again.');
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $message = __('Failed to decrypt API key. Please try again.');
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'API key not valid')) {
                $message = __('Invalid API key. Please check and try again.');
            } elseif (str_contains($message, 'quota')) {
                $message = __('API key is valid but has exceeded quota.');
            }
            report($e);
        }

        $apiKey->forceFill([
            'last_tested_at' => now(),
            'connection_status' => $success ? 'connected' : 'failed',
        ])->save();

        $this->testStatuses[$keyId] = $apiKey->connection_status;

        session()->flash(
            'api_key_notification',
            $success 
                ? $message 
                : ($message ?: __('Connection failed. Please verify the key and try again.'))
        );
    }

    public function runHealthCheck(): void
    {
        $services = [
            [
                'label' => __('Text Generation'),
                'model' => 'gemini-2.5-flash',
                'provider' => 'Gemini',
            ],
            [
                'label' => __('Image Generation'),
                'model' => 'imagen-4.0-generate-001',
                'provider' => 'Gemini Imagen',
            ],
            [
                'label' => __('Video Generation'),
                'model' => 'veo-3.0-generate-001',
                'provider' => 'Gemini Veo',
            ],
        ];

        $activeKey = Auth::user()?->activeApiKey;
        $activeStatus = $activeKey
            ? ($this->testStatuses[$activeKey->id] ?? $activeKey->connection_status ?? 'unknown')
            : 'unknown';
        $isConnected = $activeStatus === 'connected';

        $this->healthCheckResults = collect($services)->map(function ($service) use ($isConnected) {
            if ($isConnected) {
                return [
                    'status' => 'operational',
                    'label' => $service['label'],
                    'model' => $service['model'],
                    'provider' => $service['provider'],
                    'message' => __('Service responded successfully.'),
                ];
            }

            return [
                'status' => 'unavailable',
                'label' => $service['label'],
                'model' => $service['model'],
                'provider' => $service['provider'],
                'message' => __('Active API key not connected. Re-test your credential and try again.'),
            ];
        })->all();

        $this->healthCheckRanAt = Carbon::now()->toDateTimeString();

        session()->flash(
            'api_key_notification',
            $isConnected
                ? __('Health check completed. All services responded.')
                : __('Health check completed. Some services are unavailable.')
        );
    }

    public function testNewKey(): void
    {
        $this->validate([
            'provider' => ['required', 'string', 'max:100'],
            'secret' => ['required', 'string', 'min:10'],
        ]);

        $this->newKeyTestStatus = 'testing';
        $this->newKeyTestMessage = __('Testing connection...');

        $success = false;
        $message = '';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->secret, [
                'contents' => [
                    'parts' => [
                        ['text' => 'Hello']
                    ]
                ]
            ]);

            if ($response->successful()) {
                $success = true;
                $message = __('Connection successful. You can now save this key.');
            } else {
                $error = $response->json();
                $message = $error['error']['message'] ?? __('Connection failed. Please verify the key and try again.');
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'API key not valid')) {
                $message = __('Invalid API key. Please check and try again.');
            } elseif (str_contains($message, 'quota')) {
                $message = __('API key is valid but has exceeded quota.');
            }
            report($e);
        }

        $this->newKeyTestStatus = $success ? 'connected' : 'failed';
        $this->newKeyTestMessage = $message;
    }

    protected function resetForm(): void
    {
        $this->provider = 'gemini';
        $this->label = '';
        $this->secret = '';
        $this->makeActive = true;
        $this->newKeyTestStatus = null;
        $this->newKeyTestMessage = null;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('API Keys')" :subheading="__('Manage shared and personal API credentials used across AI tools.')">
        <div class="space-y-6">
            @if (session()->has('api_key_notification'))
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                    {{ session('api_key_notification') }}
                </div>
            @endif

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-2">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Select Active API Key') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Choose which key this workspace should use by default. Shared keys are provided by the team, and you can add personal keys for specific providers.') }}
                    </p>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($this->keys as $key)
                        @php
                            $status = $testStatuses[$key->id] ?? ($key->connection_status ?? 'unknown');
                            $statusPill = [
                                'connected' => [
                                    'label' => __('Connected'),
                                    'classes' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                                ],
                                'failed' => [
                                    'label' => __('Failed'),
                                    'classes' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
                                ],
                                'testing' => [
                                    'label' => __('Testing'),
                                    'classes' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100',
                                ],
                                'unknown' => [
                                    'label' => __('Not tested'),
                                    'classes' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                                ],
                            ];
                            $currentStatus = $statusPill[$status] ?? $statusPill['unknown'];
                        @endphp

                        <label
                            class="group flex cursor-pointer flex-col rounded-2xl border p-5 transition hover:border-zinc-300 hover:bg-zinc-50 dark:hover:border-zinc-600 dark:hover:bg-zinc-800"
                            @class([
                                'border-zinc-900 bg-zinc-50 dark:border-zinc-500 dark:bg-zinc-800' => $selectedKeyId === $key->id,
                                'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => $selectedKeyId !== $key->id,
                            ])
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide">
                                        <span class="text-zinc-700 dark:text-zinc-200">{{ Str::upper($key->provider) }}</span>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[11px] font-medium tracking-normal"
                                            @class([
                                                'bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200' => $key->user_id === null,
                                                'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100' => $key->user_id !== null,
                                            ])
                                        >
                                            {{ $key->user_id === null ? __('Shared') : __('Personal') }}
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium tracking-normal {{ $currentStatus['classes'] }}">
                                            {{ $currentStatus['label'] }}
                                        </span>
                                    </div>

                                    <div class="space-y-1.5">
                                        <p class="text-base font-semibold text-zinc-900 dark:text-zinc-50">{{ $key->label }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('Added :date', ['date' => $key->created_at->format('M j, Y')]) }}
                                        </p>
                                        <p class="text-sm text-zinc-400 dark:text-zinc-500">
                                            {{ Str::mask($key->secret, '*', 4, max(0, Str::length($key->secret) - 8)) }}
                                        </p>
                                        @if ($key->last_tested_at)
                                            <p class="text-xs text-zinc-400">
                                                {{ __('Last tested :time', ['time' => $key->last_tested_at->diffForHumans()]) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex w-full items-stretch gap-3 sm:w-auto sm:flex-col sm:items-end">
                                    <input
                                        type="radio"
                                        class="mt-1 size-4 shrink-0 border border-zinc-300 text-zinc-800 focus:ring-zinc-600 sm:mt-0"
                                        wire:model="selectedKeyId"
                                        value="{{ $key->id }}"
                                        aria-label="{{ __('Select :label', ['label' => $key->label]) }}"
                                    />
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        class="flex-1 sm:flex-none sm:px-4"
                                        wire:click.stop="testConnection({{ $key->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="testConnection({{ $key->id }})"
                                    >
                                        <span wire:loading.remove wire:target="testConnection({{ $key->id }})">{{ __('Run Test') }}</span>
                                        <span wire:loading wire:target="testConnection({{ $key->id }})">{{ __('Testing...') }}</span>
                                    </flux:button>
                                </div>
                            </div>
                        </label>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-600 dark:text-zinc-400">
                            {{ __('No API keys available yet. Add one below to get started.') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('API Health Check') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Run a comprehensive check on all integrated AI services to ensure they are configured correctly and operational.') }}
                            </p>
                        </div>
                        <flux:button variant="outline" wire:click="runHealthCheck" wire:loading.attr="disabled" wire:target="runHealthCheck">
                            <span wire:loading.remove wire:target="runHealthCheck">{{ __('Run Full System Check') }}</span>
                            <span wire:loading wire:target="runHealthCheck">{{ __('Checking...') }}</span>
                        </flux:button>
                    </div>

                    @if ($healthCheckRanAt)
                        <p class="text-xs text-zinc-400">{{ __('Last run at :time', ['time' => $healthCheckRanAt]) }}</p>
                    @endif
                </div>

                <div class="mt-6 space-y-4">
                    @php
                        $healthStyles = [
                            'operational' => [
                                'classes' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200',
                                'label' => __('Operational'),
                            ],
                            'unavailable' => [
                                'classes' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-200',
                                'label' => __('Not Available'),
                            ],
                        ];
                    @endphp

                    @if ($healthCheckResults)
                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach ($healthCheckResults as $result)
                                @php
                                    $style = $healthStyles[$result['status']] ?? $healthStyles['unavailable'];
                                @endphp
                                <div class="rounded-2xl border p-4 dark:border-zinc-700">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ $result['label'] }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $result['provider'] }} · {{ $result['model'] }}</p>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $style['classes'] }}">{{ $style['label'] }}</span>
                                    </div>
                                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $result['message'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-600 dark:text-zinc-400">
                            {{ __('Run a system check to see the status of each service.') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-2">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Add a New API Key') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Store additional provider keys securely. You can optionally set the new key as active right away.') }}
                    </p>
                </div>

                <form wire:submit.prevent="addKey" class="mt-6 space-y-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="provider">{{ __('Provider') }}</label>
                            <input
                                id="provider"
                                type="text"
                                wire:model.defer="provider"
                                class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="{{ __('e.g., Gemini, OpenAI, Anthropic') }}"
                                required
                            />
                            @error('provider')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="label">{{ __('Label') }}</label>
                            <input
                                id="label"
                                type="text"
                                wire:model.defer="label"
                                class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="{{ __('Friendly name for this key') }}"
                                required
                            />
                            @error('label')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="secret">{{ __('API Key') }}</label>
                        <textarea
                            id="secret"
                            wire:model.defer="secret"
                            rows="3"
                            class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            placeholder="{{ __('Paste the full API key or token here') }}"
                            required
                        ></textarea>
                        @error('secret')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/60">
                        <label for="makeActive" class="flex items-center gap-3">
                            <input
                                id="makeActive"
                                type="checkbox"
                                wire:model.defer="makeActive"
                                class="size-4 rounded border-zinc-300 text-zinc-800 focus:ring-zinc-600"
                            />
                            <span class="text-zinc-700 dark:text-zinc-300">{{ __('Set as active key after saving') }}</span>
                        </label>

                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ __('Encrypted at rest') }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <flux:button
                            type="button"
                            variant="outline"
                            wire:click="testNewKey"
                            wire:loading.attr="disabled"
                            wire:target="testNewKey"
                        >
                            <span wire:loading.remove wire:target="testNewKey">{{ __('Run Test') }}</span>
                            <span wire:loading wire:target="testNewKey">{{ __('Testing...') }}</span>
                        </flux:button>
                        <flux:button
                            variant="primary"
                            type="submit"
                            wire:loading.attr="disabled"
                            :disabled="$newKeyTestStatus !== 'connected'"
                        >
                            <span wire:loading.remove>{{ __('Save Key') }}</span>
                            <span wire:loading>{{ __('Saving...') }}</span>
                        </flux:button>
                        <flux:button
                            type="button"
                            variant="outline"
                            wire:click="$reset('provider', 'label', 'secret', 'makeActive', 'newKeyTestStatus', 'newKeyTestMessage')"
                        >
                            {{ __('Clear') }}
                        </flux:button>
                    </div>

                    @if ($newKeyTestMessage)
                        <div
                            @class([
                                'rounded-xl border px-4 py-3 text-sm',
                                'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200' => $newKeyTestStatus === 'connected',
                                'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-200' => $newKeyTestStatus === 'failed',
                                'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' => $newKeyTestStatus === 'testing',
                            ])
                        >
                            {{ $newKeyTestMessage }}
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </x-settings.layout>
</section>
