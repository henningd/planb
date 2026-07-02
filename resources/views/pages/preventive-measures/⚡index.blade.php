<?php

use App\Enums\PreventiveMeasureCategory;
use App\Enums\PreventiveMeasureEffectiveness;
use App\Enums\PreventiveMeasureInterval;
use App\Enums\PreventiveMeasureStatus;
use App\Models\Employee;
use App\Models\PreventiveMeasure;
use App\Models\Risk;
use App\Models\Role;
use App\Models\System;
use App\Support\Prevention\PreventiveMeasureCatalog;
use App\Support\TaskNotifier;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Präventivmaßnahmen')] class extends Component {
    public ?string $editingId = null;

    public string $system_id = '';

    public string $title = '';

    public string $description = '';

    public string $category = '';

    public string $status = '';

    public string $interval = '';

    public ?string $target_date = null;

    public ?string $last_executed_at = null;

    public ?string $next_due_at = null;

    public string $effectiveness = '';

    public string $responsible_employee_id = '';

    public string $responsible_role_id = '';

    public bool $notifyResponsible = true;

    public string $risk_id = '';

    public string $result_notes = '';

    public int $sort = 0;

    #[Url(as: 'system')]
    public string $filterSystem = '';

    public string $filterCategory = '';

    public string $filterStatus = '';

    public bool $onlyDue = false;

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->category = PreventiveMeasureCategory::Backup->value;
        $this->status = PreventiveMeasureStatus::Planned->value;
        $this->effectiveness = PreventiveMeasureEffectiveness::NotAssessed->value;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, PreventiveMeasure>
     */
    #[Computed]
    public function measures(): Collection
    {
        return PreventiveMeasure::query()
            ->with(['system', 'responsible', 'responsibleRole', 'risk'])
            ->when($this->filterSystem !== '', fn ($q) => $q->where('system_id', $this->filterSystem))
            ->when($this->filterCategory !== '', fn ($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('sort')
            ->orderBy('title')
            ->get()
            ->when($this->onlyDue, fn ($c) => $c->filter->isOverdue()->values());
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

    /**
     * @return Collection<int, Risk>
     */
    #[Computed]
    public function risks(): Collection
    {
        return Risk::query()->orderBy('title')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();

        if ($this->filterSystem !== '') {
            $this->system_id = $this->filterSystem;
        }

        Flux::modal('measure-form')->show();
    }

    public function openEdit(string $id): void
    {
        $m = PreventiveMeasure::findOrFail($id);

        $this->editingId = $m->id;
        $this->system_id = (string) $m->system_id;
        $this->title = (string) $m->title;
        $this->description = (string) $m->description;
        $this->category = $m->category->value;
        $this->status = $m->status->value;
        $this->interval = $m->interval?->value ?? '';
        $this->target_date = $m->target_date?->toDateString();
        $this->last_executed_at = $m->last_executed_at?->toDateString();
        $this->next_due_at = $m->next_due_at?->toDateString();
        $this->effectiveness = $m->effectiveness?->value ?? PreventiveMeasureEffectiveness::NotAssessed->value;
        $this->responsible_employee_id = (string) ($m->responsible_employee_id ?? '');
        $this->responsible_role_id = (string) ($m->responsible_role_id ?? '');
        $this->risk_id = (string) ($m->risk_id ?? '');
        $this->result_notes = (string) $m->result_notes;
        $this->sort = $m->sort;

        Flux::modal('measure-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'system_id' => ['required', 'string', Rule::exists('systems', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(collect(PreventiveMeasureCategory::cases())->pluck('value'))],
            'status' => ['required', Rule::in(collect(PreventiveMeasureStatus::cases())->pluck('value'))],
            'interval' => ['nullable', Rule::in(collect(PreventiveMeasureInterval::cases())->pluck('value'))],
            'target_date' => ['nullable', 'date'],
            'last_executed_at' => ['nullable', 'date'],
            'next_due_at' => ['nullable', 'date'],
            'effectiveness' => ['nullable', Rule::in(collect(PreventiveMeasureEffectiveness::cases())->pluck('value'))],
            'responsible_employee_id' => ['nullable', 'string', Rule::exists('employees', 'id')],
            'responsible_role_id' => ['nullable', 'string', Rule::exists('roles', 'id')],
            'risk_id' => ['nullable', 'string', Rule::exists('risks', 'id')],
            'result_notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0'],
        ]);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();

        // Bei wiederkehrenden Maßnahmen ohne explizite Fälligkeit diese aus dem
        // Intervall ableiten (ab letzter Durchführung, sonst ab heute).
        if (! empty($payload['interval']) && empty($payload['next_due_at'])) {
            $base = $payload['last_executed_at'] ?? CarbonImmutable::now()->toDateString();
            $months = PreventiveMeasureInterval::from($payload['interval'])->months();
            $payload['next_due_at'] = CarbonImmutable::parse($base)->addMonths($months)->toDateString();
        }

        if ($this->editingId) {
            $measure = PreventiveMeasure::findOrFail($this->editingId);
            $measure->update($payload);
        } else {
            $measure = PreventiveMeasure::create($payload);
        }

        $notify = $this->notifyResponsible;

        Flux::modal('measure-form')->close();
        $this->resetForm();
        unset($this->measures);

        Flux::toast(variant: 'success', text: __('Präventivmaßnahme gespeichert.'));

        if ($notify) {
            $result = TaskNotifier::notifyMeasure($measure);

            if (! $result->isEmpty()) {
                Flux::toast(variant: 'success', text: __('Benachrichtigung an :names gesendet.', ['names' => $result->names()]));
            } elseif ($measure->responsible_employee_id !== null) {
                Flux::toast(variant: 'warning', text: __('Keine E-Mail verschickt: verantwortliche Person hat keine E-Mail-Adresse hinterlegt.'));
            }
        }
    }

    public function markExecuted(string $id): void
    {
        PreventiveMeasure::findOrFail($id)->markExecuted();
        unset($this->measures);

        Flux::toast(variant: 'success', text: __('Als durchgeführt markiert.'));
    }

    public function importCatalog(): void
    {
        if (! $this->hasCompany || $this->filterSystem === '') {
            Flux::toast(variant: 'warning', text: __('Bitte zuerst ein System auswählen.'));

            return;
        }

        $system = System::findOrFail($this->filterSystem);
        $existing = $system->preventiveMeasures()->pluck('title')->map(fn ($t) => mb_strtolower($t))->all();

        $created = 0;
        foreach (PreventiveMeasureCatalog::forSystemType($system->system_type) as $suggestion) {
            if (in_array(mb_strtolower($suggestion['title']), $existing, true)) {
                continue;
            }

            $system->preventiveMeasures()->create([
                'title' => $suggestion['title'],
                'description' => $suggestion['description'],
                'category' => $suggestion['category'],
                'status' => PreventiveMeasureStatus::Planned,
                'interval' => $suggestion['interval'],
            ]);
            $created++;
        }

        unset($this->measures);
        Flux::toast(variant: 'success', text: trans_choice('{0}Keine neuen Vorschläge.|{1}:count Maßnahme übernommen.|[2,*]:count Maßnahmen übernommen.', $created, ['count' => $created]));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('measure-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            PreventiveMeasure::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->measures);
            Flux::modal('measure-delete')->close();
            Flux::toast(variant: 'success', text: __('Präventivmaßnahme gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'system_id', 'title', 'description', 'interval', 'target_date', 'last_executed_at', 'next_due_at', 'responsible_employee_id', 'responsible_role_id', 'notifyResponsible', 'risk_id', 'result_notes', 'sort']);
        $this->category = PreventiveMeasureCategory::Backup->value;
        $this->status = PreventiveMeasureStatus::Planned->value;
        $this->effectiveness = PreventiveMeasureEffectiveness::NotAssessed->value;
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Präventivmaßnahmen') }}</flux:heading>
            <flux:subheading>
                {{ __('Vorbeugende Maßnahmen, die Ausfälle verhindern – mit Zuständigen, Fälligkeiten und Wirksamkeit (NIS2 Art. 21, BSI 200-4).') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neue Maßnahme') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->hasCompany)
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="filterSystem" class="max-w-xs">
                <flux:select.option value="">{{ __('Alle Systeme') }}</flux:select.option>
                @foreach ($this->systems as $system)
                    <flux:select.option value="{{ $system->id }}">{{ $system->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterCategory" class="max-w-xs">
                <flux:select.option value="">{{ __('Alle Kategorien') }}</flux:select.option>
                @foreach (\App\Enums\PreventiveMeasureCategory::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterStatus" class="max-w-xs">
                <flux:select.option value="">{{ __('Alle Status') }}</flux:select.option>
                @foreach (\App\Enums\PreventiveMeasureStatus::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:checkbox wire:model.live="onlyDue" :label="__('Nur überfällige')" />

            @if ($this->filterSystem !== '')
                <flux:button size="sm" variant="filled" icon="sparkles" wire:click="importCatalog">
                    {{ __('Vorschläge übernehmen') }}
                </flux:button>
            @endif
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->measures as $measure)
            <div wire:key="measure-{{ $measure->id }}" class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $measure->title }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge color="zinc" size="sm" :icon="$measure->category->icon()">{{ $measure->category->label() }}</flux:badge>
                            <flux:badge :color="$measure->status->color()" size="sm">{{ $measure->status->label() }}</flux:badge>
                            @if ($measure->isOverdue())
                                <flux:badge color="red" size="sm">{{ __('Überfällig') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            @if ($measure->isRecurring())
                                <flux:menu.item icon="check-circle" wire:click="markExecuted('{{ $measure->id }}')">
                                    {{ __('Als durchgeführt markieren') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                            @endif
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $measure->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $measure->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($measure->description)
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $measure->description }}</flux:text>
                    @endif

                    <div class="flex items-center gap-2">
                        <flux:icon.server class="h-4 w-4 text-zinc-400" />
                        <span>{{ $measure->system?->name }}</span>
                    </div>

                    @if ($measure->responsible || $measure->responsibleRole)
                        <div class="flex items-center gap-2">
                            <flux:icon.user class="h-4 w-4 text-zinc-400" />
                            <span>{{ $measure->responsible?->fullName() ?? $measure->responsibleRole?->name }}</span>
                        </div>
                    @endif

                    @if ($measure->interval)
                        <div class="flex items-center gap-2">
                            <flux:icon.arrow-path class="h-4 w-4 text-zinc-400" />
                            <span>{{ $measure->interval->label() }}</span>
                        </div>
                    @endif

                    @if ($measure->next_due_at)
                        <div class="flex items-center gap-2">
                            <flux:icon.calendar class="h-4 w-4 text-zinc-400" />
                            <span @class(['text-red-600 dark:text-red-400 font-medium' => $measure->isOverdue()])>
                                {{ __('Nächste Fälligkeit') }}: {{ $measure->next_due_at->format('d.m.Y') }}
                            </span>
                        </div>
                    @elseif ($measure->target_date)
                        <div class="flex items-center gap-2">
                            <flux:icon.flag class="h-4 w-4 text-zinc-400" />
                            <span @class(['text-red-600 dark:text-red-400 font-medium' => $measure->isOverdue()])>
                                {{ __('Zieltermin') }}: {{ $measure->target_date->format('d.m.Y') }}
                            </span>
                        </div>
                    @endif

                    @if ($measure->effectiveness)
                        <div class="flex items-center gap-2">
                            <flux:icon.shield-check class="h-4 w-4 text-zinc-400" />
                            <flux:badge :color="$measure->effectiveness->color()" size="sm">{{ $measure->effectiveness->label() }}</flux:badge>
                        </div>
                    @endif

                    @if ($measure->risk)
                        <div class="flex items-center gap-2">
                            <flux:icon.exclamation-triangle class="h-4 w-4 text-zinc-400" />
                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('Risiko') }}: {{ $measure->risk->title }}</span>
                        </div>
                    @endif
                </div>

                @if ($measure->isRecurring())
                    <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <flux:button size="sm" variant="filled" icon="check-circle" wire:click="markExecuted('{{ $measure->id }}')" class="w-full">
                            {{ __('Als durchgeführt markieren') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Präventivmaßnahmen hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="measure-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Maßnahme bearbeiten') : __('Neue Präventivmaßnahme') }}
                </flux:heading>
                <flux:subheading>{{ __('Was wird vorbeugend getan, für welches System, wer ist verantwortlich, wie oft?') }}</flux:subheading>
            </div>

            <flux:select wire:model="system_id" :label="__('System')" required>
                <flux:select.option value="">{{ __('Bitte wählen') }}</flux:select.option>
                @foreach ($this->systems as $system)
                    <flux:select.option value="{{ $system->id }}">{{ $system->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="title" :label="__('Maßnahme')" type="text" placeholder="z. B. Backup-Rückspieltest" required />
            <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="2" placeholder="Was genau ist zu tun?" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="category" :label="__('Kategorie')" required>
                    @foreach (\App\Enums\PreventiveMeasureCategory::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="status" :label="__('Status')" required>
                    @foreach (\App\Enums\PreventiveMeasureStatus::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:select wire:model="interval" :label="__('Wiederholung')" :description="__('Leer = einmalige Maßnahme.')">
                <flux:select.option value="">{{ __('Einmalig (keine Wiederholung)') }}</flux:select.option>
                @foreach (\App\Enums\PreventiveMeasureInterval::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="target_date" :label="__('Zieltermin')" type="date" />
                <flux:input wire:model="last_executed_at" :label="__('Zuletzt durchgeführt')" type="date" />
                <flux:input wire:model="next_due_at" :label="__('Nächste Fälligkeit')" type="date" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="responsible_employee_id" :label="__('Verantwortliche/r (Person)')">
                    <flux:select.option value="">{{ __('—') }}</flux:select.option>
                    @foreach ($this->employees as $employee)
                        <flux:select.option value="{{ $employee->id }}">{{ $employee->fullName() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="responsible_role_id" :label="__('Verantwortliche Rolle')">
                    <flux:select.option value="">{{ __('—') }}</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:checkbox
                wire:model="notifyResponsible"
                :label="__('Verantwortliche Person per E-Mail benachrichtigen')"
                :description="__('Sendet beim Speichern eine E-Mail an die verantwortliche Person – mit Kalender-Einladung (.ics), sofern eine Fälligkeit hinterlegt ist.')"
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="effectiveness" :label="__('Wirksamkeit')">
                    @foreach (\App\Enums\PreventiveMeasureEffectiveness::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($this->risks->isNotEmpty())
                    <flux:select wire:model="risk_id" :label="__('Verknüpftes Risiko')">
                        <flux:select.option value="">{{ __('—') }}</flux:select.option>
                        @foreach ($this->risks as $risk)
                            <flux:select.option value="{{ $risk->id }}">{{ $risk->title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
            </div>

            <flux:textarea wire:model="result_notes" :label="__('Notizen / letztes Ergebnis')" rows="2" />
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

    <flux:modal name="measure-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Maßnahme löschen?') }}</flux:heading>
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
