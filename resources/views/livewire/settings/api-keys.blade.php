<?php

use App\Models\ApiKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component {
    public string $provider = 'gemini';
    public string $label = '';
    public string $secret = '';
    public bool $makeActive = true;
    public ?int $selectedKeyId = null;

    public function mount(): void
    {
        $this->selectedKeyId = Auth::user()?->active_api_key_id
            ?? ApiKey::whereNull('user_id')
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->value('id');
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

        $apiKey = ApiKey::create([
            'user_id' => Auth::id(),
            'provider' => Str::lower(trim($this->provider)),
            'label' => trim($this->label),
            'secret' => trim($this->secret),
            'is_active' => $this->makeActive,
        ]);

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

        if ($notify) {
            session()->flash('api_key_notification', __('Your default API key has been updated.'));
        }
    }

    protected function resetForm(): void
    {
        $this->provider = 'gemini';
        $this->label = '';
        $this->secret = '';
        $this->makeActive = true;
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

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-2">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Select Active API Key') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Choose which key this workspace should use by default. Shared keys are provided by the team, and you can add personal keys for specific providers.') }}
                    </p>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($this->keys as $key)
                        <label
                            class="flex cursor-pointer flex-col gap-3 rounded-2xl border p-4 transition hover:border-zinc-300 hover:bg-zinc-50 dark:hover:border-zinc-600 dark:hover:bg-zinc-800"
                            @class([
                                'border-zinc-900 bg-zinc-50 dark:border-zinc-500 dark:bg-zinc-800' => $selectedKeyId === $key->id,
                                'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => $selectedKeyId !== $key->id,
                            ])
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold uppercase tracking-wide text-zinc-700 dark:text-zinc-200">{{ Str::upper($key->provider) }}</span>
                                        <span
                                            class="rounded-full px-2 py-1 text-xs font-medium"
                                            @class([
                                                'bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200' => $key->user_id === null,
                                                'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100' => $key->user_id !== null,
                                            ])
                                        >
                                            {{ $key->user_id === null ? __('Shared') : __('Personal') }}
                                        </span>
                                    </div>
                                    <p class="text-base font-medium text-zinc-900 dark:text-zinc-50">{{ $key->label }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Added :date', ['date' => $key->created_at->format('M j, Y')]) }}
                                    </p>
                                    <p class="text-sm text-zinc-400 dark:text-zinc-500">
                                        {{ Str::mask($key->secret, '*', 4, max(0, Str::length($key->secret) - 8)) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="radio"
                                        class="size-4 border border-zinc-300 text-zinc-800 focus:ring-zinc-600"
                                        wire:model="selectedKeyId"
                                        value="{{ $key->id }}"
                                        aria-label="{{ __('Select :label', ['label' => $key->label]) }}"
                                    />
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

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
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

                    <div class="flex items-center gap-3">
                        <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('Save Key') }}</span>
                            <span wire:loading>{{ __('Saving...') }}</span>
                        </flux:button>
                        <flux:button
                            type="button"
                            variant="outline"
                            wire:click="$reset('provider', 'label', 'secret', 'makeActive')"
                        >
                            {{ __('Clear') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    </x-settings.layout>
</section>
