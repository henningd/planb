<?php

use App\Enums\ProcessCriticality;
use App\Models\BusinessProcess;
use App\Models\Employee;
use App\Models\Role;
use App\Models\System;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Geschäftsprozesse')] class extends Component {
    public ?string $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $criticality = '';

    public ?string $mtpd_hours = null;

    public ?string $rto_hours = null;

    public ?string $rpo_hours = null;

    public string $peak_times = '';

    public string $responsible_employee_id = '';

    public string $responsible_role_id = '';

    public string $notes = '';

    public int $sort = 0;

    /** @var array<int, string> */
    public array $selectedSystems = [];

    public string $filterCriticality = '';

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->criticality = ProcessCriticality::Mittel->value;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, BusinessProcess>
     */
    #[Computed]
    public function processes(): Collection
    {
        return BusinessProcess::query()
            ->with(['systems', 'responsible', 'responsibleRole'])
            ->when($this->filterCriticality !== '', fn ($q) => $q->where('criticality', $this->filterCriticality))
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, System>
     */
    #[Computed]
    public function systems(): Collection
    {
        return System::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employees(): Collection
    {
        return Employee::query()->orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();

        Flux::modal('process-form')->show();
    }

    public function openEdit(string $id): void
    {
        $process = BusinessProcess::with('systems')->findOrFail($id);

        $this->editingId = $process->id;
        $this->name = (string) $process->name;
        $this->description = (string) $process->description;
        $this->criticality = $process->criticality->value;
        $this->mtpd_hours = $this->minutesToHoursField($process->mtpd_minutes);
        $this->rto_hours = $this->minutesToHoursField($process->rto_minutes);
        $this->rpo_hours = $this->minutesToHoursField($process->rpo_minutes);
        $this->peak_times = (string) $process->peak_times;
        $this->responsible_employee_id = (string) ($process->responsible_employee_id ?? '');
        $this->responsible_role_id = (string) ($process->responsible_role_id ?? '');
        $this->notes = (string) $process->notes;
        $this->sort = $process->sort;
        $this->selectedSystems = $process->systems->pluck('id')->all();

        Flux::modal('process-form')->show();
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
            'criticality' => ['required', Rule::in(collect(ProcessCriticality::cases())->pluck('value'))],
            'mtpd_hours' => ['nullable', 'numeric', 'min:0'],
            'rto_hours' => ['nullable', 'numeric', 'min:0'],
            'rpo_hours' => ['nullable', 'numeric', 'min:0'],
            'peak_times' => ['nullable', 'string', 'max:255'],
            'responsible_employee_id' => ['nullable', 'string', Rule::exists('employees', 'id')],
            'responsible_role_id' => ['nullable', 'string', Rule::exists('roles', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0'],
            'selectedSystems' => ['array'],
            'selectedSystems.*' => ['string', Rule::exists('systems', 'id')],
        ]);

        $systemIds = $validated['selectedSystems'] ?? [];
        unset($validated['selectedSystems']);

        $mtpdMinutes = $this->hoursFieldToMinutes($validated['mtpd_hours'] ?? null);
        $rtoMinutes = $this->hoursFieldToMinutes($validated['rto_hours'] ?? null);
        $rpoMinutes = $this->hoursFieldToMinutes($validated['rpo_hours'] ?? null);
        unset($validated['mtpd_hours'], $validated['rto_hours'], $validated['rpo_hours']);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();
        $payload['mtpd_minutes'] = $mtpdMinutes;
        $payload['rto_minutes'] = $rtoMinutes;
        $payload['rpo_minutes'] = $rpoMinutes;

        if ($this->editingId) {
            $process = BusinessProcess::findOrFail($this->editingId);
            $process->update($payload);
        } else {
            $process = BusinessProcess::create($payload);
        }

        $process->systems()->sync($systemIds);

        Flux::modal('process-form')->close();
        $this->resetForm();
        unset($this->processes);

        Flux::toast(variant: 'success', text: __('Geschäftsprozess gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('process-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            BusinessProcess::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->processes);
            Flux::modal('process-delete')->close();
            Flux::toast(variant: 'success', text: __('Geschäftsprozess gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'mtpd_hours', 'rto_hours', 'rpo_hours', 'peak_times', 'responsible_employee_id', 'responsible_role_id', 'notes', 'sort', 'selectedSystems']);
        $this->criticality = ProcessCriticality::Mittel->value;
    }

    /**
     * Stored minutes → hours for the number input ("240" → "4", "90" → "1.5").
     */
    protected function minutesToHoursField(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        return rtrim(rtrim(number_format($minutes / 60, 2, '.', ''), '0'), '.');
    }

    /**
     * Entered hours → stored minutes (null when left empty).
     */
    protected function hoursFieldToMinutes(int|string|null $hours): ?int
    {
        if ($hours === null || $hours === '') {
            return null;
        }

        return (int) round(((float) $hours) * 60);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Geschäftsprozesse') }}</flux:heading>
            <flux:subheading>
                {{ __('Geschäftsprozesse und Business-Impact-Analyse (BIA): Kritikalität, Wiederanlaufziele (MTPD/RTO/RPO) und benötigte Systeme (BSI 200-4, NIS2 Art. 21).') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Prozess') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->hasCompany)
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="filterCriticality" class="max-w-xs">
                <flux:select.option value="">{{ __('Alle Kritikalitäten') }}</flux:select.option>
                @foreach (\App\Enums\ProcessCriticality::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->processes as $process)
            <div wire:key="process-{{ $process->id }}" class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $process->name }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge :color="$process->criticality->color()" size="sm">{{ $process->criticality->label() }}</flux:badge>
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
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $process->description }}</flux:text>
                    @endif

                    @if ($process->mtpd_minutes !== null || $process->rto_minutes !== null || $process->rpo_minutes !== null)
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($process->mtpd_minutes !== null)
                                <flux:badge color="zinc" size="sm">{{ __('MTPD') }}: {{ \App\Support\Duration::inHours($process->mtpd_minutes) }}</flux:badge>
                            @endif
                            @if ($process->rto_minutes !== null)
                                <flux:badge color="zinc" size="sm">{{ __('RTO') }}: {{ \App\Support\Duration::inHours($process->rto_minutes) }}</flux:badge>
                            @endif
                            @if ($process->rpo_minutes !== null)
                                <flux:badge color="zinc" size="sm">{{ __('RPO') }}: {{ \App\Support\Duration::inHours($process->rpo_minutes) }}</flux:badge>
                            @endif
                        </div>
                    @endif

                    @if ($process->peak_times)
                        <div class="flex items-center gap-2">
                            <flux:icon.clock class="h-4 w-4 text-zinc-400" />
                            <span>{{ $process->peak_times }}</span>
                        </div>
                    @endif

                    @if ($process->responsible || $process->responsibleRole)
                        <div class="flex items-center gap-2">
                            <flux:icon.user class="h-4 w-4 text-zinc-400" />
                            <span>{{ $process->responsible?->fullName() ?? $process->responsibleRole?->name }}</span>
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <flux:icon.server class="h-4 w-4 text-zinc-400" />
                        <span>{{ trans_choice('{0}Keine Systeme zugeordnet|{1}:count System|[2,*]:count Systeme', $process->systems->count(), ['count' => $process->systems->count()]) }}</span>
                    </div>

                    @if ($process->systems->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ($process->systems as $system)
                                <flux:badge color="sky" size="sm">{{ $system->name }}</flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Geschäftsprozesse hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="process-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Prozess bearbeiten') : __('Neuer Geschäftsprozess') }}
                </flux:heading>
                <flux:subheading>{{ __('Welcher Prozess, wie kritisch, welche Wiederanlaufziele und welche Systeme werden benötigt?') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Prozessname')" type="text" placeholder="z. B. Auftragsabwicklung" required />
            <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="2" placeholder="Worum geht es bei diesem Prozess?" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="criticality" :label="__('Kritikalität')" required>
                    @foreach (\App\Enums\ProcessCriticality::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="peak_times" :label="__('Stoßzeiten')" type="text" placeholder="z. B. Mo–Fr 08–18 Uhr" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="mtpd_hours" :label="__('MTPD (Std.)')" type="number" min="0" step="0.5" :description="__('Max. tolerierbare Ausfallzeit')" />
                <flux:input wire:model="rto_hours" :label="__('RTO (Std.)')" type="number" min="0" step="0.5" :description="__('Wiederanlaufzeit')" />
                <flux:input wire:model="rpo_hours" :label="__('RPO (Std.)')" type="number" min="0" step="0.5" :description="__('Max. Datenverlust')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="responsible_employee_id" :label="__('Verantwortliche/r (Person)')">
                    <flux:select.option value="">{{ __('—') }}</flux:select.option>
                    @foreach ($this->employees as $employee)
                        <flux:select.option value="{{ $employee->id }}">{{ $employee->nameLastFirst() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="responsible_role_id" :label="__('Verantwortliche Rolle')">
                    <flux:select.option value="">{{ __('—') }}</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:label>{{ __('Benötigte Systeme') }}</flux:label>
                <flux:description>{{ __('Welche Systeme müssen für diesen Prozess verfügbar sein?') }}</flux:description>
                @if ($this->systems->isEmpty())
                    <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('Noch keine Systeme angelegt.') }}</flux:text>
                @else
                    <div class="mt-2 grid max-h-48 gap-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 sm:grid-cols-2 dark:border-zinc-700">
                        @foreach ($this->systems as $system)
                            <flux:checkbox wire:model="selectedSystems" value="{{ $system->id }}" :label="$system->name" />
                        @endforeach
                    </div>
                @endif
            </div>

            <flux:textarea wire:model="notes" :label="__('Notizen')" rows="2" />
            <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />

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

    <flux:modal name="process-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Prozess löschen?') }}</flux:heading>
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
