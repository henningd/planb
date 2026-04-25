<?php

use App\Enums\EmergencyResourceType;
use App\Models\EmergencyResource;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Sofortmittel')] class extends Component {
    public ?string $editingId = null;

    public string $type = '';

    public string $name = '';

    public string $description = '';

    public string $location = '';

    public string $access_holders = '';

    public ?string $last_check_at = null;

    public ?string $next_check_at = null;

    public string $notes = '';

    public int $sort = 0;

    public string $filterType = '';

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->type = EmergencyResourceType::EmergencyCash->value;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, EmergencyResource>
     */
    #[Computed]
    public function resources(): Collection
    {
        return EmergencyResource::query()
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->orderBy('type')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('resource-form')->show();
    }

    public function openEdit(string $id): void
    {
        $r = EmergencyResource::findOrFail($id);

        $this->editingId = $r->id;
        $this->type = $r->type->value;
        $this->name = (string) $r->name;
        $this->description = (string) $r->description;
        $this->location = (string) $r->location;
        $this->access_holders = (string) $r->access_holders;
        $this->last_check_at = $r->last_check_at?->toDateString();
        $this->next_check_at = $r->next_check_at?->toDateString();
        $this->notes = (string) $r->notes;
        $this->sort = $r->sort;

        Flux::modal('resource-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'type' => ['required', 'string', Rule::in(collect(EmergencyResourceType::cases())->pluck('value'))],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
            'access_holders' => ['nullable', 'string', 'max:1000'],
            'last_check_at' => ['nullable', 'date'],
            'next_check_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0'],
        ]);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();

        if ($this->editingId) {
            EmergencyResource::findOrFail($this->editingId)->update($payload);
        } else {
            EmergencyResource::create($payload);
        }

        Flux::modal('resource-form')->close();
        $this->resetForm();
        unset($this->resources);

        Flux::toast(variant: 'success', text: __('Sofortmittel gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('resource-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            EmergencyResource::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->resources);
            Flux::modal('resource-delete')->close();
            Flux::toast(variant: 'success', text: __('Sofortmittel gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'location', 'access_holders', 'last_check_at', 'next_check_at', 'notes', 'sort']);
        $this->type = EmergencyResourceType::EmergencyCash->value;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function typeOptions(): array
    {
        return EmergencyResourceType::options();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Sofortmittel') }}</flux:heading>
            <flux:subheading>
                {{ __('Verfügbare Sofortmittel und Ressourcen für den Ernstfall – Notfallkasse, Ersatz-Hardware, Offline-Backup, Generator/USV (Kap. 8.2).') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neues Sofortmittel') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->hasCompany)
        <div class="mb-4 flex flex-wrap gap-3">
            <flux:select wire:model.live="filterType" placeholder="{{ __('Alle Typen') }}" class="max-w-xs">
                <flux:select.option value="">{{ __('Alle Typen') }}</flux:select.option>
                @foreach ($this->typeOptions() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->resources as $resource)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $resource->name ?: $resource->type->label() }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge color="zinc" size="sm">{{ $resource->type->label() }}</flux:badge>
                            @if ($resource->isOverdue())
                                <flux:badge color="red" size="sm">{{ __('Prüfung überfällig') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $resource->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $resource->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($resource->description)
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $resource->description }}</flux:text>
                    @endif
                    @if ($resource->location)
                        <div class="flex items-center gap-2">
                            <flux:icon.map-pin class="h-4 w-4 text-zinc-400" />
                            <span>{{ $resource->location }}</span>
                        </div>
                    @endif
                    @if ($resource->access_holders)
                        <div class="flex items-start gap-2">
                            <flux:icon.users class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <span>{{ $resource->access_holders }}</span>
                        </div>
                    @endif
                    @if ($resource->next_check_at)
                        <div class="flex items-center gap-2">
                            <flux:icon.calendar class="h-4 w-4 text-zinc-400" />
                            <span @class(['text-red-600 dark:text-red-400 font-medium' => $resource->isOverdue()])>
                                {{ __('Nächste Prüfung') }}: {{ $resource->next_check_at->format('d.m.Y') }}
                            </span>
                        </div>
                    @endif
                </div>

                @if ($resource->notes)
                    <flux:text class="mt-3 border-t border-zinc-100 pt-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        {{ $resource->notes }}
                    </flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Sofortmittel hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="resource-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Sofortmittel bearbeiten') : __('Neues Sofortmittel') }}
                </flux:heading>
                <flux:subheading>{{ __('Was steht bereit, wo, wer hat Zugriff, wann wurde es zuletzt geprüft.') }}</flux:subheading>
            </div>

            <flux:select wire:model="type" :label="__('Typ')" required>
                @foreach ($this->typeOptions() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="name" :label="__('Bezeichnung')" type="text" placeholder="z. B. Notebook Reserve, Hauptkasse" />
            <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="2" placeholder="Inhalt, Kapazität, Spezifika" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="location" :label="__('Aufbewahrungsort')" type="text" />
                <flux:input wire:model="access_holders" :label="__('Zugriffsberechtigte')" type="text" placeholder="z. B. GF + IT-Lead" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="last_check_at" :label="__('Letzte Prüfung')" type="date" />
                <flux:input wire:model="next_check_at" :label="__('Nächste Prüfung')" type="date" />
            </div>

            <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="2" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingId ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="resource-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Sofortmittel löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" type="button" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
