<?php

use App\Models\Employee;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mitarbeiter')] class extends Component {
    public ?string $editingId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $position = '';

    public string $department = '';

    public string $work_phone = '';

    public string $mobile_phone = '';

    public string $private_phone = '';

    public string $email = '';

    public string $location = '';

    public string $emergency_contact = '';

    public ?string $manager_id = null;

    public bool $is_key_personnel = false;

    public string $notes = '';

    public ?string $deletingId = null;

    public string $search = '';

    public string $filterDepartment = '';

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->with('manager')
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('position', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($this->filterDepartment !== '', fn ($q) => $q->where('department', $this->filterDepartment))
            ->orderByDesc('is_key_personnel')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function departments(): array
    {
        return Employee::query()
            ->whereNotNull('department')
            ->pluck('department')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    #[Computed]
    public function managerOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Employee::query()
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('employee-form')->show();
    }

    public function openEdit(string $id): void
    {
        $e = Employee::findOrFail($id);

        $this->editingId = $e->id;
        $this->first_name = $e->first_name;
        $this->last_name = $e->last_name;
        $this->position = (string) $e->position;
        $this->department = (string) $e->department;
        $this->work_phone = (string) $e->work_phone;
        $this->mobile_phone = (string) $e->mobile_phone;
        $this->private_phone = (string) $e->private_phone;
        $this->email = (string) $e->email;
        $this->location = (string) $e->location;
        $this->emergency_contact = (string) $e->emergency_contact;
        $this->manager_id = $e->manager_id;
        $this->is_key_personnel = (bool) $e->is_key_personnel;
        $this->notes = (string) $e->notes;

        Flux::modal('employee-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'work_phone' => ['nullable', 'string', 'max:50'],
            'mobile_phone' => ['nullable', 'string', 'max:50'],
            'private_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:1000'],
            'manager_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'is_key_personnel' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->editingId) {
            Employee::findOrFail($this->editingId)->update($validated);
        } else {
            Employee::create($validated);
        }

        Flux::modal('employee-form')->close();
        $this->resetForm();
        unset($this->employees, $this->departments, $this->managerOptions);

        Flux::toast(variant: 'success', text: __('Mitarbeiter gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('employee-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Employee::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->employees, $this->departments, $this->managerOptions);
            Flux::modal('employee-delete')->close();
            Flux::toast(variant: 'success', text: __('Mitarbeiter gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'first_name', 'last_name', 'position', 'department',
            'work_phone', 'mobile_phone', 'private_phone', 'email', 'location',
            'emergency_contact', 'manager_id', 'is_key_personnel', 'notes',
        ]);
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Mitarbeiter') }}</flux:heading>
            <flux:subheading>
                {{ __('Die gesamte Belegschaft mit Kontaktdaten – wichtig für Benachrichtigungsketten und Krisenkommunikation.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Mitarbeiter') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->hasCompany)
        <div class="mb-4 flex flex-wrap gap-3">
            <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="{{ __('Suchen: Name, Rolle, E-Mail …') }}" class="max-w-sm" />
            @if ($this->departments)
                <flux:select wire:model.live="filterDepartment" placeholder="{{ __('Alle Abteilungen') }}" class="max-w-xs">
                    <flux:select.option value="">{{ __('Alle Abteilungen') }}</flux:select.option>
                    @foreach ($this->departments as $dept)
                        <flux:select.option value="{{ $dept }}">{{ $dept }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @forelse ($this->employees as $employee)
            <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                <div class="flex min-w-0 flex-1 items-start gap-3">
                    <flux:avatar :name="$employee->fullName()" size="sm" class="mt-0.5 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ $employee->fullName() }}</span>
                            @if ($employee->is_key_personnel)
                                <flux:badge color="amber" size="sm">{{ __('Schlüsselmitarbeiter') }}</flux:badge>
                            @endif
                            @if ($employee->department)
                                <flux:badge color="zinc" size="sm">{{ $employee->department }}</flux:badge>
                            @endif
                        </div>
                        @if ($employee->position)
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $employee->position }}
                            </flux:text>
                        @endif

                        @if ($employee->mobile_phone || $employee->work_phone || $employee->email)
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                @if ($employee->mobile_phone)
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.device-phone-mobile class="h-3.5 w-3.5 text-zinc-400" />
                                        <span>{{ $employee->mobile_phone }}</span>
                                    </div>
                                @endif
                                @if ($employee->work_phone)
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.phone class="h-3.5 w-3.5 text-zinc-400" />
                                        <span>{{ $employee->work_phone }}</span>
                                    </div>
                                @endif
                                @if ($employee->email)
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.envelope class="h-3.5 w-3.5 text-zinc-400" />
                                        <span class="truncate">{{ $employee->email }}</span>
                                    </div>
                                @endif
                                @if ($employee->location)
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.map-pin class="h-3.5 w-3.5 text-zinc-400" />
                                        <span>{{ $employee->location }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($employee->manager)
                            <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Vorgesetzt:') }} {{ $employee->manager->fullName() }}
                            </flux:text>
                        @endif
                    </div>
                </div>

                <flux:dropdown align="end">
                    <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                    <flux:menu>
                        <flux:menu.item icon="pencil" wire:click="openEdit('{{ $employee->id }}')">
                            {{ __('Bearbeiten') }}
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $employee->id }}')">
                            {{ __('Löschen') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    @if ($this->search !== '' || $this->filterDepartment !== '')
                        {{ __('Keine Mitarbeiter gefunden, die zu den Filtern passen.') }}
                    @else
                        {{ __('Noch keine Mitarbeiter angelegt.') }}
                    @endif
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="employee-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Mitarbeiter bearbeiten') : __('Neuen Mitarbeiter anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Alle Felder außer Vor- und Nachnamen sind optional. Private Nummer und Notfallkontakt sind für echte Ernstfälle Gold wert, wenn E-Mail und Arbeitstelefon nicht funktionieren.') }}
                </flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="first_name" :label="__('Vorname')" required />
                <flux:input wire:model="last_name" :label="__('Nachname')" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="position" :label="__('Position')" placeholder="z. B. Vertriebsleitung" />
                <flux:input wire:model="department" :label="__('Abteilung')" placeholder="z. B. Vertrieb" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="email" :label="__('E-Mail')" type="email" />
                <flux:input wire:model="location" :label="__('Standort')" placeholder="z. B. Hauptsitz" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="work_phone" :label="__('Tel. (Büro)')" />
                <flux:input wire:model="mobile_phone" :label="__('Mobil (dienstlich)')" />
                <flux:input wire:model="private_phone" :label="__('Privat')" />
            </div>

            <flux:textarea
                wire:model="emergency_contact"
                :label="__('Notfallkontakt')"
                rows="2"
                placeholder="z. B. Angehöriger: Max Mustermann (Ehemann), 0171 …"
            />

            <flux:select wire:model="manager_id" :label="__('Vorgesetzt von')" placeholder="{{ __('Niemandem') }}">
                <flux:select.option value="">{{ __('Niemandem') }}</flux:select.option>
                @foreach ($this->managerOptions as $candidate)
                    <flux:select.option value="{{ $candidate->id }}">{{ $candidate->fullName() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:switch wire:model="is_key_personnel" :label="__('Schlüsselmitarbeiter – besonders wichtig für den Betrieb')" />

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="2" />

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

    <flux:modal name="employee-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Mitarbeiter löschen?') }}</flux:heading>
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
