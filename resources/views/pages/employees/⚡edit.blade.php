<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Support\AssignmentSync;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mitarbeiter bearbeiten')] class extends Component {
    public ?Employee $employee = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $position = '';

    public ?string $department_id = null;

    public string $work_phone = '';

    public string $mobile_phone = '';

    public string $private_phone = '';

    public string $email = '';

    public ?string $location_id = null;

    public string $emergency_contact = '';

    /** @var array<int, string> */
    public array $manager_ids = [];

    /**
     * Rollen-Zuordnungen: Schlüssel = role_id, Wert = 'main' | 'deputy'.
     * Fehlende Schlüssel = nicht zugeordnet.
     *
     * @var array<string, string>
     */
    public array $roleAssignments = [];

    public bool $is_key_personnel = false;

    public string $notes = '';

    public function mount(?Employee $employee = null): void
    {
        abort_unless(Auth::user()?->currentCompany(), 403);

        if ($employee && $employee->exists) {
            $employee->load(['managers', 'roles']);

            $this->employee = $employee;
            $this->first_name = $employee->first_name;
            $this->last_name = $employee->last_name;
            $this->position = (string) $employee->position;
            $this->department_id = $employee->department_id;
            $this->work_phone = (string) $employee->work_phone;
            $this->mobile_phone = (string) $employee->mobile_phone;
            $this->private_phone = (string) $employee->private_phone;
            $this->email = (string) $employee->email;
            $this->location_id = $employee->location_id;
            $this->emergency_contact = (string) $employee->emergency_contact;
            $this->manager_ids = $employee->managers->pluck('id')->all();
            $this->roleAssignments = $employee->roles->mapWithKeys(
                fn (Role $role) => [$role->id => ((bool) ($role->pivot->is_deputy ?? false)) ? 'deputy' : 'main']
            )->all();
            $this->is_key_personnel = (bool) $employee->is_key_personnel;
            $this->notes = (string) $employee->notes;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Department>
     */
    #[Computed]
    public function departmentOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Department::query()->orderBy('sort')->orderBy('name')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    #[Computed]
    public function availableRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::query()->orderBy('sort')->orderBy('name')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    #[Computed]
    public function managerOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Employee::query()
            ->when($this->employee?->id, fn ($q, $id) => $q->where('id', '!=', $id))
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

    public function save()
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'work_phone' => ['nullable', 'string', 'max:50'],
            'mobile_phone' => ['nullable', 'string', 'max:50'],
            'private_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'emergency_contact' => ['nullable', 'string', 'max:1000'],
            'manager_ids' => ['array'],
            'manager_ids.*' => ['uuid', 'exists:employees,id'],
            'roleAssignments' => ['array'],
            'roleAssignments.*' => ['nullable', 'string', Rule::in(['', 'main', 'deputy'])],
            'is_key_personnel' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $editingId = $this->employee?->id;

        $managerIds = collect($validated['manager_ids'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->filter(fn ($id) => $id !== $editingId)
            ->unique()
            ->values()
            ->all();
        unset($validated['manager_ids']);

        $assignments = collect($validated['roleAssignments'] ?? [])
            ->filter(fn ($mode) => $mode === 'main' || $mode === 'deputy')
            ->all();
        $validRoleIds = Role::query()->whereIn('id', array_keys($assignments))->pluck('id')->all();
        $desiredRoles = [];
        foreach ($validRoleIds as $rid) {
            $desiredRoles[$rid] = ['is_deputy' => ($assignments[$rid] ?? 'main') === 'deputy'];
        }
        unset($validated['roleAssignments']);

        if ($this->employee?->exists) {
            $this->employee->update($validated);
            $employee = $this->employee;
        } else {
            $employee = Employee::create($validated);
        }
        $employee->managers()->sync($managerIds);
        AssignmentSync::sync($employee, $employee->roles(), $desiredRoles);

        Flux::toast(variant: 'success', text: __('Mitarbeiter gespeichert.'));

        return redirect()->route('employees.index');
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('employees.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Mitarbeiter') }}
        </flux:link>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <form wire:submit="save" class="space-y-5 p-6">
            <div>
                <flux:heading size="xl">
                    {{ $employee?->exists ? __('Mitarbeiter bearbeiten') : __('Neuen Mitarbeiter anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Alle Felder außer Vor- und Nachnamen sind optional. Private Nummer und Notfallkontakt sind für echte Ernstfälle Gold wert, wenn E-Mail und Arbeitstelefon nicht funktionieren.') }}
                </flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="first_name" :label="__('Vorname')" required />
                <flux:input wire:model="last_name" :label="__('Nachname')" required />
            </div>

            <div @class(['grid gap-4', 'sm:grid-cols-2' => config('features.departments')])>
                <flux:input wire:model="position" :label="__('Position')" placeholder="z. B. Vertriebsleitung" />
                @if (config('features.departments'))
                    <flux:select wire:model="department_id" :label="__('Abteilung')" :placeholder="__('Keine Abteilung')">
                        <flux:select.option value="">{{ __('— Keine Abteilung —') }}</flux:select.option>
                        @foreach ($this->departmentOptions as $dept)
                            <flux:select.option value="{{ $dept->id }}">{{ $dept->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @if ($this->departmentOptions->isEmpty())
                        <flux:text class="-mt-2 text-xs text-zinc-500">
                            {{ __('Noch keine Abteilung angelegt — pflegen Sie diese unter „Abteilungen" in der Sidebar.') }}
                        </flux:text>
                    @endif
                @endif
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
                    {{-- Plain-HTML-Checkboxen statt <flux:checkbox> für die Array-Bindung:
                         Flux' Web-Component <ui-checkbox> hatte bei wire:model="manager_ids"
                         über mehrere Checkboxen falsche Checked-States gesetzt. --}}
                    <div class="max-h-72 space-y-1 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($this->managerOptions as $candidate)
                            <label
                                wire:key="manager-option-{{ $candidate->id }}"
                                class="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"
                            >
                                <input
                                    type="checkbox"
                                    wire:model="manager_ids"
                                    value="{{ $candidate->id }}"
                                    class="size-4 shrink-0 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-700"
                                />
                                <span class="text-zinc-700 dark:text-zinc-200">
                                    {{ $candidate->nameLastFirst() }}@if ($candidate->position) <span class="text-zinc-500 dark:text-zinc-400">· {{ $candidate->position }}</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Rollen') }}</flux:label>
                <flux:description>
                    {{ __('Mehrere Rollen möglich. Markieren Sie pro Rolle, ob die Person Hauptverantwortliche/r oder Stellvertretung ist.') }}
                </flux:description>
                @if ($this->availableRoles->isEmpty())
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('Noch keine Rollen angelegt — pflegen Sie diese unter „Rollen" in der Sidebar.') }}
                    </flux:text>
                @else
                    <div class="max-h-80 space-y-1 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($this->availableRoles as $role)
                            <div
                                wire:key="role-option-{{ $role->id }}"
                                class="flex items-center justify-between gap-3 rounded px-1 py-0.5 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"
                            >
                                <span class="flex items-center gap-2 text-zinc-700 dark:text-zinc-200">
                                    {{ $role->name }}
                                    @if ($role->isSystem())
                                        <flux:badge color="blue" size="sm">{{ __('Pflichtrolle') }}</flux:badge>
                                    @endif
                                </span>
                                <select
                                    wire:model.live="roleAssignments.{{ $role->id }}"
                                    class="rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                >
                                    <option value="">{{ __('— nicht zugeordnet —') }}</option>
                                    <option value="main">{{ __('Hauptperson') }}</option>
                                    <option value="deputy">{{ __('Stellvertretung') }}</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:field>

            <flux:switch wire:model="is_key_personnel" :label="__('Schlüsselmitarbeiter – besonders wichtig für den Betrieb')" />

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="2" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button type="button" variant="filled" :href="route('employees.index')" wire:navigate>
                    {{ __('Abbrechen') }}
                </flux:button>
                <flux:button variant="primary" type="submit">
                    {{ $employee?->exists ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </div>
</section>
