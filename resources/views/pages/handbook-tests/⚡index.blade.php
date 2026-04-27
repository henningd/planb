<?php

use App\Enums\HandbookTestInterval;
use App\Enums\HandbookTestType;
use App\Models\Employee;
use App\Models\HandbookTest;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Testplan')] class extends Component {
    public ?string $editingId = null;

    public string $type = '';

    public string $name = '';

    public string $description = '';

    public string $interval = '';

    public ?string $last_executed_at = null;

    public ?string $next_due_at = null;

    public ?string $responsible_employee_id = null;

    public ?string $responsible_role_id = null;

    public string $result_notes = '';

    public int $sort = 0;

    public ?string $deletingId = null;

    public ?string $executingId = null;

    public function mount(): void
    {
        $this->type = HandbookTestType::ContactCheck->value;
        $this->interval = HandbookTestInterval::Yearly->value;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, HandbookTest>
     */
    #[Computed]
    public function tests(): Collection
    {
        return HandbookTest::with(['responsible', 'responsibleRole.employees'])
            ->orderByRaw('next_due_at IS NULL, next_due_at')
            ->orderBy('sort')
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

    /**
     * @return Collection<int, \App\Models\Role>
     */
    #[Computed]
    public function roleOptions(): Collection
    {
        return \App\Models\Role::orderBy('sort')->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('test-form')->show();
    }

    public function openEdit(string $id): void
    {
        $t = HandbookTest::findOrFail($id);

        $this->editingId = $t->id;
        $this->type = $t->type->value;
        $this->name = (string) $t->name;
        $this->description = (string) $t->description;
        $this->interval = $t->interval->value;
        $this->last_executed_at = $t->last_executed_at?->toDateString();
        $this->next_due_at = $t->next_due_at?->toDateString();
        $this->responsible_employee_id = $t->responsible_employee_id;
        $this->responsible_role_id = $t->responsible_role_id;
        $this->result_notes = (string) $t->result_notes;
        $this->sort = $t->sort;

        Flux::modal('test-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'type' => ['required', 'string', Rule::in(collect(HandbookTestType::cases())->pluck('value'))],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'interval' => ['required', 'string', Rule::in(collect(HandbookTestInterval::cases())->pluck('value'))],
            'last_executed_at' => ['nullable', 'date'],
            'next_due_at' => ['nullable', 'date'],
            'responsible_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'responsible_role_id' => ['nullable', 'uuid', 'exists:roles,id'],
            'result_notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0'],
        ]);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();

        if ($this->editingId) {
            HandbookTest::findOrFail($this->editingId)->update($payload);
        } else {
            HandbookTest::create($payload);
        }

        Flux::modal('test-form')->close();
        $this->resetForm();
        unset($this->tests);

        Flux::toast(variant: 'success', text: __('Test gespeichert.'));
    }

    public function confirmExecute(string $id): void
    {
        $this->executingId = $id;
        Flux::modal('test-execute')->show();
    }

    public function markExecuted(): void
    {
        if ($this->executingId) {
            HandbookTest::findOrFail($this->executingId)->markExecuted();
            $this->executingId = null;
            unset($this->tests);
            Flux::modal('test-execute')->close();
            Flux::toast(variant: 'success', text: __('Test als durchgeführt markiert.'));
        }
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('test-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            HandbookTest::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->tests);
            Flux::modal('test-delete')->close();
            Flux::toast(variant: 'success', text: __('Test gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'last_executed_at', 'next_due_at', 'responsible_employee_id', 'responsible_role_id', 'result_notes', 'sort']);
        $this->type = HandbookTestType::ContactCheck->value;
        $this->interval = HandbookTestInterval::Yearly->value;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function typeOptions(): array
    {
        return HandbookTestType::options();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function intervalOptions(): array
    {
        return HandbookTestInterval::options();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Testplan') }}</flux:heading>
            <flux:subheading>
                {{ __('Pflege- und Testplan für das Notfallhandbuch (Kap. 16). Ein Notfallhandbuch ist nur so gut wie sein letzter Test.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Test') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->tests as $test)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $test->name ?: $test->type->label() }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge color="zinc" size="sm">{{ $test->type->label() }}</flux:badge>
                            <flux:badge color="sky" size="sm">{{ $test->interval->label() }}</flux:badge>
                            @if ($test->isOverdue())
                                <flux:badge color="red" size="sm">{{ __('Überfällig') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="check-circle" wire:click="confirmExecute('{{ $test->id }}')">
                                {{ __('Als durchgeführt markieren') }}
                            </flux:menu.item>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $test->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $test->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($test->description)
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $test->description }}</flux:text>
                    @endif
                    @if ($test->last_executed_at)
                        <div class="flex items-center gap-2">
                            <flux:icon.check class="h-4 w-4 text-zinc-400" />
                            <span>{{ __('Zuletzt') }}: {{ $test->last_executed_at->format('d.m.Y') }}</span>
                        </div>
                    @endif
                    @if ($test->next_due_at)
                        <div class="flex items-center gap-2">
                            <flux:icon.calendar class="h-4 w-4 text-zinc-400" />
                            <span @class(['text-red-600 dark:text-red-400 font-medium' => $test->isOverdue()])>
                                {{ __('Nächste Fälligkeit') }}: {{ $test->next_due_at->format('d.m.Y') }}
                            </span>
                        </div>
                    @endif
                    @if ($test->responsible)
                        <div class="flex items-center gap-2">
                            <flux:icon.user class="h-4 w-4 text-zinc-400" />
                            <span>{{ $test->responsible->fullName() }}</span>
                        </div>
                    @endif
                    @if ($test->responsibleRole)
                        <div class="flex items-center gap-2">
                            <flux:icon.identification class="h-4 w-4 text-zinc-400" />
                            <span>{{ $test->responsibleRole->name }}</span>
                            @if ($test->responsibleRole->employees->count() > 0)
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">({{ $test->responsibleRole->employees->count() }} {{ __('Mitglieder') }})</span>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($test->result_notes)
                    <flux:text class="mt-3 border-t border-zinc-100 pt-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        {{ $test->result_notes }}
                    </flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Tests geplant.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="test-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Test bearbeiten') : __('Neuen Test anlegen') }}
                </flux:heading>
                <flux:subheading>{{ __('Test-Typ, Intervall und nächste Fälligkeit.') }}</flux:subheading>
            </div>

            <flux:select wire:model="type" :label="__('Test-Typ')" required>
                @foreach ($this->typeOptions() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="name" :label="__('Bezeichnung')" type="text" placeholder="optional, z. B. Halbjahres-Review der Kontakte" />
            <flux:textarea wire:model="description" :label="__('Inhalt / Beschreibung')" rows="2" />

            <flux:select wire:model="interval" :label="__('Intervall')" required>
                @foreach ($this->intervalOptions() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="last_executed_at" :label="__('Letzte Durchführung')" type="date" />
                <flux:input wire:model="next_due_at" :label="__('Nächste Fälligkeit')" type="date" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="responsible_employee_id" :label="__('Verantwortlich (Person)')">
                    <flux:select.option value="">{{ __('— keine Person —') }}</flux:select.option>
                    @foreach ($this->employeeOptions as $emp)
                        <flux:select.option value="{{ $emp->id }}">{{ $emp->fullName() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="responsible_role_id" :label="__('Verantwortlich (Rolle / Gruppe)')">
                    <flux:select.option value="">{{ __('— keine Rolle —') }}</flux:select.option>
                    @foreach ($this->roleOptions as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:description>
                {{ __('Person, Rolle/Gruppe oder beides. Eine Rolle kann mehrere Mitglieder umfassen — die werden auf dieser Seite und im PDF mit ausgewiesen.') }}
            </flux:description>

            <flux:textarea wire:model="result_notes" :label="__('Ergebnis-Notizen')" rows="2" />

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

    <flux:modal name="test-execute" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Test als durchgeführt markieren?') }}</flux:heading>
                <flux:subheading>{{ __('Die letzte Durchführung wird auf heute gesetzt und die nächste Fälligkeit aus dem Intervall berechnet.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" wire:click="markExecuted">{{ __('Bestätigen') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="test-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Test löschen?') }}</flux:heading>
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
