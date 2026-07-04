<?php

use App\Enums\PreventiveMeasureInterval;
use App\Enums\TrainingType;
use App\Models\Employee;
use App\Models\TrainingRecord;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Schulungen')] class extends Component {
    public ?string $editingId = null;

    public string $employee_id = '';

    public string $topic = '';

    public string $type = '';

    public ?string $completed_at = null;

    public string $interval = '';

    public ?string $next_due_at = null;

    public string $notes = '';

    public string $filterType = '';

    #[Url(as: 'employee')]
    public string $filterEmployee = '';

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->type = TrainingType::Bcm->value;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, TrainingRecord>
     */
    #[Computed]
    public function records(): Collection
    {
        return TrainingRecord::query()
            ->with('employee')
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterEmployee !== '', fn ($q) => $q->where('employee_id', $this->filterEmployee))
            ->orderByDesc('completed_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employees(): Collection
    {
        return Employee::query()->orderBy('last_name')->orderBy('first_name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();

        if ($this->filterEmployee !== '') {
            $this->employee_id = $this->filterEmployee;
        }

        Flux::modal('training-form')->show();
    }

    public function openEdit(string $id): void
    {
        $record = TrainingRecord::findOrFail($id);

        $this->editingId = $record->id;
        $this->employee_id = (string) $record->employee_id;
        $this->topic = (string) $record->topic;
        $this->type = $record->type->value;
        $this->completed_at = $record->completed_at?->toDateString();
        $this->interval = $record->interval?->value ?? '';
        $this->next_due_at = $record->next_due_at?->toDateString();
        $this->notes = (string) $record->notes;

        Flux::modal('training-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'employee_id' => ['required', 'string', Rule::exists('employees', 'id')],
            'topic' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(collect(TrainingType::cases())->pluck('value'))],
            'completed_at' => ['nullable', 'date'],
            'interval' => ['nullable', Rule::in(collect(PreventiveMeasureInterval::cases())->pluck('value'))],
            'next_due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();

        // Bei wiederkehrenden Schulungen ohne explizite Fälligkeit diese aus dem
        // Intervall ableiten (ab Abschlussdatum, sonst ab heute).
        if (! empty($payload['interval']) && empty($payload['next_due_at'])) {
            $base = $payload['completed_at'] ?? CarbonImmutable::now()->toDateString();
            $months = PreventiveMeasureInterval::from($payload['interval'])->months();
            $payload['next_due_at'] = CarbonImmutable::parse($base)->addMonths($months)->toDateString();
        }

        if ($this->editingId) {
            TrainingRecord::findOrFail($this->editingId)->update($payload);
        } else {
            TrainingRecord::create($payload);
        }

        Flux::modal('training-form')->close();
        $this->resetForm();
        unset($this->records);

        Flux::toast(variant: 'success', text: __('Schulungsnachweis gespeichert.'));
    }

    public function markCompleted(string $id): void
    {
        TrainingRecord::findOrFail($id)->markCompleted();
        unset($this->records);

        Flux::toast(variant: 'success', text: __('Als absolviert markiert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('training-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            TrainingRecord::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->records);
            Flux::modal('training-delete')->close();
            Flux::toast(variant: 'success', text: __('Schulungsnachweis gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'employee_id', 'topic', 'completed_at', 'interval', 'next_due_at', 'notes']);
        $this->type = TrainingType::Bcm->value;
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Schulungen') }}</flux:heading>
            <flux:subheading>
                {{ __('Schulungs- und Awareness-Nachweise: wer wurde wann zu welchem Thema geschult – mit Fälligkeitszyklus (NIS2 Art. 21).') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Nachweis') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->hasCompany)
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="filterType" class="max-w-xs">
                <flux:select.option value="">{{ __('Alle Typen') }}</flux:select.option>
                @foreach (\App\Enums\TrainingType::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterEmployee" class="max-w-xs">
                <flux:select.option value="">{{ __('Alle Mitarbeiter') }}</flux:select.option>
                @foreach ($this->employees as $employee)
                    <flux:select.option value="{{ $employee->id }}">{{ $employee->nameLastFirst() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->records as $record)
            <div wire:key="record-{{ $record->id }}" class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $record->topic }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge :color="$record->type->color()" size="sm">{{ $record->type->label() }}</flux:badge>
                            @if ($record->isOverdue())
                                <flux:badge color="red" size="sm">{{ __('Überfällig') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="check-circle" wire:click="markCompleted('{{ $record->id }}')">
                                {{ __('Als absolviert markieren') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $record->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $record->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <flux:icon.user class="h-4 w-4 text-zinc-400" />
                        <span>{{ $record->employee?->fullName() }}</span>
                    </div>

                    @if ($record->completed_at)
                        <div class="flex items-center gap-2">
                            <flux:icon.academic-cap class="h-4 w-4 text-zinc-400" />
                            <span>{{ __('Absolviert am') }}: {{ $record->completed_at->format('d.m.Y') }}</span>
                        </div>
                    @endif

                    @if ($record->interval)
                        <div class="flex items-center gap-2">
                            <flux:icon.arrow-path class="h-4 w-4 text-zinc-400" />
                            <span>{{ $record->interval->label() }}</span>
                        </div>
                    @endif

                    @if ($record->next_due_at)
                        <div class="flex items-center gap-2">
                            <flux:icon.calendar class="h-4 w-4 text-zinc-400" />
                            <span @class(['text-red-600 dark:text-red-400 font-medium' => $record->isOverdue()])>
                                {{ __('Nächste Fälligkeit') }}: {{ $record->next_due_at->format('d.m.Y') }}
                            </span>
                        </div>
                    @endif

                    @if ($record->notes)
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $record->notes }}</flux:text>
                    @endif
                </div>

                <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <flux:button size="sm" variant="filled" icon="check-circle" wire:click="markCompleted('{{ $record->id }}')" class="w-full">
                        {{ __('Als absolviert markieren') }}
                    </flux:button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Schulungsnachweise hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="training-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Nachweis bearbeiten') : __('Neuer Schulungsnachweis') }}
                </flux:heading>
                <flux:subheading>{{ __('Wer wurde wann zu welchem Thema geschult, und wann ist die nächste Schulung fällig?') }}</flux:subheading>
            </div>

            <flux:select wire:model="employee_id" :label="__('Mitarbeiter')" required>
                <flux:select.option value="">{{ __('Bitte wählen') }}</flux:select.option>
                @foreach ($this->employees as $employee)
                    <flux:select.option value="{{ $employee->id }}">{{ $employee->nameLastFirst() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="topic" :label="__('Thema')" type="text" placeholder="z. B. Phishing-Awareness" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="type" :label="__('Typ')" required>
                    @foreach (\App\Enums\TrainingType::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="completed_at" :label="__('Absolviert am')" type="date" />
            </div>

            <flux:select wire:model="interval" :label="__('Wiederholung')" :description="__('Leer = einmalige Schulung.')">
                <flux:select.option value="">{{ __('Einmalig (keine Wiederholung)') }}</flux:select.option>
                @foreach (\App\Enums\PreventiveMeasureInterval::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="next_due_at" :label="__('Nächste Fälligkeit')" type="date" />

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

    <flux:modal name="training-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Nachweis löschen?') }}</flux:heading>
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
