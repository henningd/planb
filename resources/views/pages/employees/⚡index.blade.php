<?php

use App\Enums\CrisisRole;
use App\Models\Employee;
use App\Models\Location;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

    public ?string $location_id = null;

    public string $emergency_contact = '';

    /** @var array<int, string> */
    public array $manager_ids = [];

    public bool $is_key_personnel = false;

    public string $crisis_role = '';

    public bool $is_crisis_deputy = false;

    public string $notes = '';

    public ?string $deletingId = null;

    public string $search = '';

    public string $filterDepartment = '';

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->with(['managers', 'location'])
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

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Location>
     */
    #[Computed]
    public function locationOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Location::query()
            ->orderBy('sort')
            ->orderBy('name')
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
        $this->location_id = $e->location_id;
        $this->emergency_contact = (string) $e->emergency_contact;
        $this->manager_ids = $e->managers->pluck('id')->all();
        $this->is_key_personnel = (bool) $e->is_key_personnel;
        $this->crisis_role = $e->crisis_role?->value ?? '';
        $this->is_crisis_deputy = (bool) $e->is_crisis_deputy;
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
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'emergency_contact' => ['nullable', 'string', 'max:1000'],
            'manager_ids' => ['array'],
            'manager_ids.*' => ['uuid', 'exists:employees,id'],
            'is_key_personnel' => ['boolean'],
            'crisis_role' => ['nullable', 'string', Rule::in(collect(CrisisRole::cases())->pluck('value'))],
            'is_crisis_deputy' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($validated['crisis_role'])) {
            $conflict = Employee::query()
                ->where('crisis_role', $validated['crisis_role'])
                ->where('is_crisis_deputy', $validated['is_crisis_deputy'] ?? false)
                ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->exists();

            if ($conflict) {
                $this->addError('crisis_role', __('Diese Krisenrolle ist bereits vergeben. Lösen Sie zuerst die andere Zuordnung oder markieren Sie diese Person als Vertretung.'));

                return;
            }
        } else {
            $validated['crisis_role'] = null;
            $validated['is_crisis_deputy'] = false;
        }

        $managerIds = collect($validated['manager_ids'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->filter(fn ($id) => $id !== $this->editingId) // niemand ist sein eigener Vorgesetzter
            ->unique()
            ->values()
            ->all();
        unset($validated['manager_ids']);

        if ($this->editingId) {
            $employee = Employee::findOrFail($this->editingId);
            $employee->update($validated);
        } else {
            $employee = Employee::create($validated);
        }
        $employee->managers()->sync($managerIds);

        Flux::modal('employee-form')->close();
        $this->resetForm();
        unset($this->employees, $this->departments, $this->managerOptions, $this->locationOptions);

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
            unset($this->employees, $this->departments, $this->managerOptions, $this->locationOptions);
            Flux::modal('employee-delete')->close();
            Flux::toast(variant: 'success', text: __('Mitarbeiter gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'first_name', 'last_name', 'position', 'department',
            'work_phone', 'mobile_phone', 'private_phone', 'email', 'location_id',
            'emergency_contact', 'manager_ids', 'is_key_personnel',
            'crisis_role', 'is_crisis_deputy', 'notes',
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function crisisRoleOptions(): array
    {
        return CrisisRole::options();
    }
}; ?>

<section class="w-full">
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

    @if ($this->employees->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                @if ($this->search !== '' || $this->filterDepartment !== '')
                    {{ __('Keine Mitarbeiter gefunden, die zu den Filtern passen.') }}
                @else
                    {{ __('Noch keine Mitarbeiter angelegt.') }}
                @endif
            </flux:text>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->employees as $employee)
                <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <flux:avatar :name="$employee->fullName()" size="sm" class="mt-0.5 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <flux:heading size="base">{{ $employee->fullName() }}</flux:heading>
                                @if ($employee->position)
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $employee->position }}</flux:text>
                                @endif
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    @if ($employee->is_key_personnel)
                                        <flux:badge color="amber" size="sm">{{ __('Schlüsselmitarbeiter') }}</flux:badge>
                                    @endif
                                    @if ($employee->crisis_role)
                                        <flux:badge color="red" size="sm">
                                            {{ $employee->crisis_role->label() }}@if ($employee->is_crisis_deputy) ({{ __('Vertretung') }})@endif
                                        </flux:badge>
                                    @endif
                                    @if ($employee->department)
                                        <flux:badge color="zinc" size="sm">{{ $employee->department }}</flux:badge>
                                    @endif
                                </div>
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

                    @if ($employee->mobile_phone || $employee->work_phone || $employee->email || $employee->location_id)
                        <div class="mt-4 space-y-2 text-sm">
                            @if ($employee->mobile_phone)
                                <div class="flex items-center gap-2">
                                    <flux:icon.device-phone-mobile class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <a href="tel:{{ $employee->mobile_phone }}" class="hover:underline">{{ $employee->mobile_phone }}</a>
                                </div>
                            @endif
                            @if ($employee->work_phone)
                                <div class="flex items-center gap-2">
                                    <flux:icon.phone class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <a href="tel:{{ $employee->work_phone }}" class="hover:underline">{{ $employee->work_phone }}</a>
                                </div>
                            @endif
                            @if ($employee->email)
                                <div class="flex items-center gap-2">
                                    <flux:icon.envelope class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <a href="mailto:{{ $employee->email }}" class="truncate hover:underline">{{ $employee->email }}</a>
                                </div>
                            @endif
                            @if ($employee->location)
                                <div class="flex items-center gap-2">
                                    <flux:icon.map-pin class="h-4 w-4 shrink-0 text-zinc-400" />
                                    <span>{{ $employee->location->name }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($employee->managers->isNotEmpty())
                        <flux:text class="mt-3 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            {{ __('Vorgesetzt von:') }} {{ $employee->managers->map(fn ($m) => $m->fullName())->implode(', ') }}
                        </flux:text>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

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
                <flux:select wire:model="location_id" :label="__('Standort')">
                    <flux:select.option value="">{{ __('— kein Standort —') }}</flux:select.option>
                    @foreach ($this->locationOptions as $loc)
                        <flux:select.option value="{{ $loc->id }}">{{ $loc->name }}</flux:select.option>
                    @endforeach
                </flux:select>
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

            <flux:field>
                <flux:label>{{ __('Vorgesetzt von') }}</flux:label>
                <flux:description>
                    {{ __('Mehrere Vorgesetzte möglich (z. B. fachlich + disziplinarisch). Wenn niemand ausgewählt: keine Vorgesetzten.') }}
                </flux:description>
                @if ($this->managerOptions->isEmpty())
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('Es sind noch keine anderen Mitarbeiter erfasst.') }}
                    </flux:text>
                @else
                    <div class="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($this->managerOptions as $candidate)
                            <flux:checkbox
                                wire:model="manager_ids"
                                value="{{ $candidate->id }}"
                                :label="$candidate->fullName().($candidate->position ? ' · '.$candidate->position : '')"
                            />
                        @endforeach
                    </div>
                @endif
            </flux:field>

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
