<?php

use App\Enums\SystemCategory;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemPriority;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Systeme')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $category = '';

    public ?int $system_priority_id = null;

    /** @var array<int> */
    public array $service_provider_ids = [];

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->category = SystemCategory::Basisbetrieb->value;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, SystemPriority>
     */
    #[Computed]
    public function priorities(): Collection
    {
        return SystemPriority::orderBy('sort')->get();
    }

    /**
     * @return Collection<int, ServiceProvider>
     */
    #[Computed]
    public function providers(): Collection
    {
        return ServiceProvider::orderBy('name')->get();
    }

    /**
     * Systems grouped by their category. Keyed by the enum value.
     *
     * @return array<string, Collection<int, System>>
     */
    #[Computed]
    public function systemsByCategory(): array
    {
        $systems = System::with(['priority', 'serviceProviders'])->orderBy('name')->get();
        $grouped = [];

        foreach (SystemCategory::cases() as $category) {
            $grouped[$category->value] = $systems->where('category', $category);
        }

        return $grouped;
    }

    public function openCreate(?string $category = null): void
    {
        $this->resetForm();
        if ($category) {
            $this->category = $category;
        }
        Flux::modal('system-form')->show();
    }

    public function openEdit(int $id): void
    {
        $system = System::with('serviceProviders')->findOrFail($id);

        $this->editingId = $system->id;
        $this->name = $system->name;
        $this->description = (string) $system->description;
        $this->category = $system->category->value;
        $this->system_priority_id = $system->system_priority_id;
        $this->service_provider_ids = $system->serviceProviders->pluck('id')->all();

        Flux::modal('system-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'in:'.collect(SystemCategory::cases())->pluck('value')->implode(',')],
            'system_priority_id' => ['nullable', 'integer', 'exists:system_priorities,id'],
            'service_provider_ids' => ['array'],
            'service_provider_ids.*' => ['integer', 'exists:service_providers,id'],
        ]);

        $providerIds = $validated['service_provider_ids'] ?? [];
        unset($validated['service_provider_ids']);

        $system = $this->editingId
            ? tap(System::findOrFail($this->editingId))->update($validated)
            : System::create($validated);

        $system->serviceProviders()->sync($providerIds);

        Flux::modal('system-form')->close();
        $this->resetForm();
        unset($this->systemsByCategory);

        Flux::toast(variant: 'success', text: __('System gespeichert.'));
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('system-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            System::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->systemsByCategory);
            Flux::modal('system-delete')->close();
            Flux::toast(variant: 'success', text: __('System gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'system_priority_id', 'service_provider_ids']);
        $this->category = SystemCategory::Basisbetrieb->value;
    }
}; ?>

<section class="mx-auto w-full max-w-5xl">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Systeme & Betriebskontinuität') }}</flux:heading>
            <flux:subheading>
                {{ __('Welche Systeme braucht Ihr Betrieb – und in welcher Reihenfolge müssen sie im Ernstfall zurück ans Netz?') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neues System') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an, bevor Sie Systeme hinzufügen.') }}
        </div>
    @endunless

    <div class="space-y-6">
        @foreach (\App\Enums\SystemCategory::cases() as $category)
            @php($systems = $this->systemsByCategory[$category->value])
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                    <div>
                        <div class="flex items-center gap-3">
                            <flux:heading size="base">{{ $category->label() }}</flux:heading>
                            <flux:badge color="zinc" size="sm">{{ $systems->count() }}</flux:badge>
                        </div>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $category->description() }}
                        </flux:text>
                    </div>
                    <flux:button size="sm" variant="ghost" icon="plus" wire:click="openCreate('{{ $category->value }}')" :disabled="! $this->hasCompany">
                        {{ __('Hinzufügen') }}
                    </flux:button>
                </div>

                @forelse ($systems as $system)
                    <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $system->name }}</span>
                                @if ($system->priority)
                                    <flux:badge
                                        :color="match ($system->priority->sort) { 1 => 'rose', 2 => 'amber', default => 'zinc' }"
                                        size="sm"
                                    >
                                        {{ $system->priority->name }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('Ohne Priorität') }}</flux:badge>
                                @endif
                            </div>
                            @if ($system->description)
                                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $system->description }}
                                </flux:text>
                            @endif
                            @if ($system->serviceProviders->isNotEmpty())
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <flux:icon.wrench-screwdriver class="h-3.5 w-3.5 text-zinc-400" />
                                    @foreach ($system->serviceProviders as $p)
                                        <flux:badge color="zinc" size="sm">{{ $p->name }}@if ($p->hotline) · {{ $p->hotline }}@endif</flux:badge>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openEdit({{ $system->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $system->id }})" />
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch kein System in dieser Kategorie.') }}
                        </flux:text>
                    </div>
                @endforelse
            </div>
        @endforeach
    </div>

    <flux:modal name="system-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('System bearbeiten') : __('Neues System anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Was ist das System, wofür wird es gebraucht und wie wichtig ist es?') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required placeholder="z. B. Warenwirtschaft, Telefonanlage" />

            <flux:textarea
                wire:model="description"
                :label="__('Beschreibung')"
                rows="3"
                placeholder="Wofür wird dieses System genutzt?"
            />

            <flux:select wire:model="category" :label="__('Kategorie')" required>
                @foreach (\App\Enums\SystemCategory::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="system_priority_id" :label="__('Priorität')" placeholder="Keine">
                <flux:select.option value="">{{ __('Ohne Priorität') }}</flux:select.option>
                @foreach ($this->priorities as $priority)
                    <flux:select.option value="{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->providers->isNotEmpty())
                <flux:field>
                    <flux:label>{{ __('Dienstleister') }}</flux:label>
                    <flux:description>{{ __('Wer ist für dieses System zuständig, wenn es ausfällt?') }}</flux:description>
                    <div class="space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($this->providers as $provider)
                            <flux:checkbox
                                wire:model="service_provider_ids"
                                value="{{ $provider->id }}"
                                :label="$provider->name.($provider->hotline ? ' · '.$provider->hotline : '')"
                            />
                        @endforeach
                    </div>
                </flux:field>
            @endif

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingId ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="system-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('System löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
