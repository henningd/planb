<?php

use App\Models\Employee;
use App\Models\FallbackProcess;
use App\Models\Role;
use App\Models\System;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Notfallbetrieb')] class extends Component {
    public ?string $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $trigger = '';

    public ?string $responsible_role_id = null;

    public ?string $responsible_employee_id = null;

    public ?int $max_duration_hours = null;

    public string $handover_notes = '';

    public int $priority = 2;

    public string $notes = '';

    public int $sort = 0;

    /** @var array<int, string> */
    public array $system_ids = [];

    public ?string $deletingId = null;

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, FallbackProcess>
     */
    #[Computed]
    public function processes(): Collection
    {
        return FallbackProcess::query()
            ->with(['responsibleRole', 'responsibleEmployee', 'systems'])
            ->orderBy('priority')
            ->orderBy('sort')
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::orderBy('sort')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employees(): Collection
    {
        return Employee::orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * @return Collection<int, System>
     */
    #[Computed]
    public function systems(): Collection
    {
        return System::orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('fallback-form')->show();
    }

    public function openEdit(string $id): void
    {
        $process = FallbackProcess::with('systems')->findOrFail($id);

        $this->editingId = $process->id;
        $this->title = (string) $process->title;
        $this->description = (string) $process->description;
        $this->trigger = (string) $process->trigger;
        $this->responsible_role_id = $process->responsible_role_id;
        $this->responsible_employee_id = $process->responsible_employee_id;
        $this->max_duration_hours = $process->max_duration_hours;
        $this->handover_notes = (string) $process->handover_notes;
        $this->priority = $process->priority;
        $this->notes = (string) $process->notes;
        $this->sort = $process->sort;
        $this->system_ids = $process->systems->pluck('id')->all();

        Flux::modal('fallback-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'trigger' => ['nullable', 'string', 'max:2000'],
            'responsible_role_id' => ['nullable', 'uuid', 'exists:roles,id'],
            'responsible_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'max_duration_hours' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'handover_notes' => ['nullable', 'string', 'max:2000'],
            'priority' => ['integer', 'min:1', 'max:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0'],
            'system_ids' => ['array'],
            'system_ids.*' => ['uuid', 'exists:systems,id'],
        ]);

        $systemIds = $validated['system_ids'] ?? [];
        unset($validated['system_ids']);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();

        if ($this->editingId) {
            $process = FallbackProcess::findOrFail($this->editingId);
            $process->update($payload);
        } else {
            $process = FallbackProcess::create($payload);
        }

        $process->systems()->sync($systemIds);

        Flux::modal('fallback-form')->close();
        $this->resetForm();
        unset($this->processes);

        Flux::toast(variant: 'success', text: __('Notfallbetrieb gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('fallback-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            FallbackProcess::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->processes);
            Flux::modal('fallback-delete')->close();
            Flux::toast(variant: 'success', text: __('Notfallbetrieb gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId',
            'title',
            'description',
            'trigger',
            'responsible_role_id',
            'responsible_employee_id',
            'max_duration_hours',
            'handover_notes',
            'notes',
            'sort',
            'system_ids',
        ]);
        $this->priority = 2;
    }

    /**
     * @return array<int, array{value: int, label: string, color: string}>
     */
    public function priorityOptions(): array
    {
        return [
            ['value' => 1, 'label' => __('Hoch'), 'color' => 'rose'],
            ['value' => 2, 'label' => __('Mittel'), 'color' => 'amber'],
            ['value' => 3, 'label' => __('Niedrig'), 'color' => 'zinc'],
        ];
    }

    public function priorityLabel(int $priority): string
    {
        return match ($priority) {
            1 => __('Hoch'),
            3 => __('Niedrig'),
            default => __('Mittel'),
        };
    }

    public function priorityColor(int $priority): string
    {
        return match ($priority) {
            1 => 'rose',
            3 => 'zinc',
            default => 'amber',
        };
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Notfallbetrieb') }}</flux:heading>
            <flux:subheading>
                {{ __('Ersatzprozesse für den Ausfall kritischer Systeme — wer macht was, wie lange, mit welcher Kapazität, und wie übergibt man später an den Wiederanlauf.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Ersatzprozess') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->processes as $process)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $process->title }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge :color="$this->priorityColor($process->priority)" size="sm">
                                {{ $this->priorityLabel($process->priority) }}
                            </flux:badge>
                            @if ($process->max_duration_hours !== null)
                                <flux:badge color="zinc" size="sm" icon="clock">
                                    {{ __('max.') }} {{ $process->max_duration_hours }} h
                                </flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $process->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $process->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($process->description)
                        <flux:text class="whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $process->description }}</flux:text>
                    @endif
                    @if ($process->trigger)
                        <div class="flex items-start gap-2">
                            <flux:icon.bolt class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Auslöser') }}</flux:text>
                                <flux:text class="text-sm">{{ $process->trigger }}</flux:text>
                            </div>
                        </div>
                    @endif
                    @if ($process->responsibleRole || $process->responsibleEmployee)
                        <div class="flex items-start gap-2">
                            <flux:icon.user-circle class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Verantwortlich') }}</flux:text>
                                <flux:text class="text-sm">
                                    {{ $process->responsibleRole?->name }}
                                    @if ($process->responsibleRole && $process->responsibleEmployee) · @endif
                                    @if ($process->responsibleEmployee)
                                        {{ $process->responsibleEmployee->first_name }} {{ $process->responsibleEmployee->last_name }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif
                    @if ($process->systems->isNotEmpty())
                        <div class="flex items-start gap-2">
                            <flux:icon.server-stack class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div class="min-w-0 flex-1">
                                <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Betroffene Systeme') }}</flux:text>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($process->systems as $system)
                                        <flux:badge color="zinc" size="sm" :href="route('systems.show', ['system' => $system->id])" wire:navigate>
                                            {{ $system->name }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($process->handover_notes)
                        <div class="flex items-start gap-2">
                            <flux:icon.arrow-path class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                            <div>
                                <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Übergabe an Wiederanlauf') }}</flux:text>
                                <flux:text class="whitespace-pre-line text-sm">{{ $process->handover_notes }}</flux:text>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($process->notes)
                    <flux:text class="mt-3 border-t border-zinc-100 pt-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        {{ $process->notes }}
                    </flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Ersatzprozesse hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="fallback-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Ersatzprozess bearbeiten') : __('Neuer Ersatzprozess') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Was wird getan, wenn ein oder mehrere Systeme ausfallen — bis der Wiederanlauf greift.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="title" :label="__('Titel')" type="text" placeholder="z. B. Papierbasierter Auftragsdurchlauf" required />

            <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="4" :placeholder='"Schritt-für-Schritt: was ist zu tun, mit welcher Kapazität (z. B. \"30 % Durchsatz mit Papier statt ERP\")?"' />

            <flux:textarea wire:model="trigger" :label="__('Auslöser')" rows="2" :placeholder='"Wann aktivieren? z. B. \"ERP-System länger als 2 Stunden nicht erreichbar\""' />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="responsible_role_id" :label="__('Verantwortliche Rolle')" placeholder="—">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="responsible_employee_id" :label="__('Verantwortliche Person')" placeholder="—">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($this->employees as $employee)
                        <flux:select.option value="{{ $employee->id }}">{{ $employee->nameLastFirst() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="priority" :label="__('Priorität')">
                    @foreach ($this->priorityOptions() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input
                    wire:model="max_duration_hours"
                    :label="__('Max. Dauer (Stunden)')"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="z. B. 48"
                    description="Wie lange darf dieser Ersatzbetrieb laufen, bevor eskaliert wird?"
                />
            </div>

            <flux:textarea
                wire:model="handover_notes"
                :label="__('Übergabe an Wiederanlauf')"
                rows="3"
                :placeholder='"Was muss nachgeholt werden, sobald das System wieder läuft? z. B. \"Papierbelege ins ERP nachbuchen\""'
            />

            <div>
                <flux:label>{{ __('Betroffene Systeme') }}</flux:label>
                <flux:description class="mb-2">{{ __('Welche Systeme sind durch diesen Ersatzprozess abgedeckt? Mehrfachauswahl möglich; auch ohne System speicherbar (z. B. rein organisatorischer Ablauf).') }}</flux:description>
                @if ($this->systems->isEmpty())
                    <flux:text class="text-sm text-zinc-500">{{ __('Noch keine Systeme erfasst.') }}</flux:text>
                @else
                    <div class="grid max-h-64 grid-cols-1 gap-1 overflow-y-auto rounded-lg border border-zinc-200 p-3 sm:grid-cols-2 dark:border-zinc-700">
                        @foreach ($this->systems as $system)
                            <flux:checkbox
                                wire:model="system_ids"
                                value="{{ $system->id }}"
                                :label="$system->name"
                            />
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />
            </div>

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

    <flux:modal name="fallback-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Ersatzprozess löschen?') }}</flux:heading>
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
