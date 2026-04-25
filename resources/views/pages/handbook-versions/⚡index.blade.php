<?php

use App\Models\Employee;
use App\Models\HandbookVersion;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Versionshistorie')] class extends Component {
    public ?string $editingId = null;

    public string $version = '';

    public string $changed_at = '';

    public ?string $changed_by_employee_id = null;

    public string $change_reason = '';

    public ?string $approved_at = null;

    public ?string $approved_by_employee_id = null;

    public string $approved_by_name = '';

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->changed_at = now()->toDateString();
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, HandbookVersion>
     */
    #[Computed]
    public function versions(): Collection
    {
        return HandbookVersion::with(['changedBy', 'approvedBy'])
            ->orderByDesc('changed_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employeeOptions(): Collection
    {
        return Employee::orderBy('last_name')->orderBy('first_name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('version-form')->show();
    }

    public function openEdit(string $id): void
    {
        $version = HandbookVersion::findOrFail($id);

        $this->editingId = $version->id;
        $this->version = $version->version;
        $this->changed_at = $version->changed_at->toDateString();
        $this->changed_by_employee_id = $version->changed_by_employee_id;
        $this->change_reason = $version->change_reason;
        $this->approved_at = $version->approved_at?->toDateString();
        $this->approved_by_employee_id = $version->approved_by_employee_id;
        $this->approved_by_name = (string) $version->approved_by_name;

        Flux::modal('version-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'version' => ['required', 'string', 'max:50'],
            'changed_at' => ['required', 'date'],
            'changed_by_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'change_reason' => ['required', 'string', 'max:2000'],
            'approved_at' => ['nullable', 'date'],
            'approved_by_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'approved_by_name' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = collect($validated)->map(fn ($value) => $value === '' ? null : $value)->toArray();

        if ($this->editingId) {
            HandbookVersion::findOrFail($this->editingId)->update($payload);
        } else {
            HandbookVersion::create($payload);
        }

        Flux::modal('version-form')->close();
        $this->resetForm();
        unset($this->versions);

        Flux::toast(variant: 'success', text: __('Version gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('version-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            HandbookVersion::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->versions);
            Flux::modal('version-delete')->close();
            Flux::toast(variant: 'success', text: __('Version gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'version', 'changed_by_employee_id', 'change_reason', 'approved_at', 'approved_by_employee_id', 'approved_by_name']);
        $this->changed_at = now()->toDateString();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Versionshistorie') }}</flux:heading>
            <flux:subheading>
                {{ __('Jede Änderung am Notfallhandbuch wird hier dokumentiert. Neue Versionen müssen durch die Geschäftsführung freigegeben werden (Kap. 1).') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neue Version') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->versions as $version)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ __('Version') }} {{ $version->version }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $version->changed_at->format('d.m.Y') }}
                            @if ($version->changedBy)
                                · {{ $version->changedBy->fullName() }}
                            @endif
                        </flux:text>
                        <div class="mt-1">
                            @if ($version->isApproved())
                                <flux:badge color="emerald" size="sm">{{ __('Freigegeben') }} {{ $version->approved_at->format('d.m.Y') }}</flux:badge>
                            @else
                                <flux:badge color="amber" size="sm">{{ __('Freigabe offen') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $version->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $version->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <flux:text class="mt-4 border-t border-zinc-100 pt-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                    {{ $version->change_reason }}
                </flux:text>

                @if ($version->isApproved() && ($version->approvedBy || $version->approved_by_name))
                    <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Freigegeben durch') }}: {{ $version->approvedBy?->fullName() ?? $version->approved_by_name }}
                    </flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Version dokumentiert.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="version-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Version bearbeiten') : __('Neue Version anlegen') }}
                </flux:heading>
                <flux:subheading>{{ __('Versionsnummer, Datum und Änderungsgrund. Freigabe erfolgt separat.') }}</flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="version" :label="__('Version')" type="text" required placeholder="z. B. 1.1" />
                <flux:input wire:model="changed_at" :label="__('Datum')" type="date" required />
            </div>

            <flux:select wire:model="changed_by_employee_id" :label="__('Geändert von')">
                <flux:select.option value="">{{ __('— nicht angegeben —') }}</flux:select.option>
                @foreach ($this->employeeOptions as $emp)
                    <flux:select.option value="{{ $emp->id }}">{{ $emp->fullName() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="change_reason" :label="__('Änderungsgrund')" rows="3" required />

            <div class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div>
                    <flux:heading size="base">{{ __('Freigabe') }}</flux:heading>
                    <flux:subheading>{{ __('Optional – wird durch die Geschäftsführung gesetzt.') }}</flux:subheading>
                </div>
                <flux:input wire:model="approved_at" :label="__('Freigabe-Datum')" type="date" />
                <flux:select wire:model="approved_by_employee_id" :label="__('Freigegeben durch (Mitarbeiter)')">
                    <flux:select.option value="">{{ __('— nicht angegeben —') }}</flux:select.option>
                    @foreach ($this->employeeOptions as $emp)
                        <flux:select.option value="{{ $emp->id }}">{{ $emp->fullName() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="approved_by_name" :label="__('Freigegeben durch (Name, freier Text)')" type="text" placeholder="z. B. Max Mustermann, GF" />
            </div>

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

    <flux:modal name="version-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Version löschen?') }}</flux:heading>
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
