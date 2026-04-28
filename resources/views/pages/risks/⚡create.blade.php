<?php

use App\Enums\RiskCategory;
use App\Enums\RiskMitigationStatus;
use App\Enums\RiskStatus;
use App\Enums\RiskTreatmentStrategy;
use App\Models\Employee;
use App\Models\Risk;
use App\Models\System;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Neues Risiko')] class extends Component {
    public string $title = '';

    public string $description = '';

    public string $category = 'operational';

    public int $probability = 3;

    public int $impact = 3;

    public ?int $residual_probability = null;

    public ?int $residual_impact = null;

    public string $status = 'identified';

    public ?string $treatment_strategy = null;

    public ?int $owner_user_id = null;

    public ?string $review_due_at = null;

    /**
     * @var array<int, string>
     */
    public array $system_ids = [];

    /**
     * @var array<int, array{title: string, description: string, status: string, target_date: ?string, responsible_employee_id: ?string}>
     */
    public array $mitigations = [];

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    #[Computed]
    public function systems()
    {
        return System::orderBy('name')->get();
    }

    #[Computed]
    public function employees()
    {
        return Employee::orderBy('last_name')->orderBy('first_name')->get();
    }

    #[Computed]
    public function teamUsers()
    {
        $team = Auth::user()->currentTeam;

        return $team ? $team->members()->orderBy('name')->get() : collect();
    }

    public function addMitigation(): void
    {
        $this->mitigations[] = [
            'title' => '',
            'description' => '',
            'status' => RiskMitigationStatus::Planned->value,
            'target_date' => null,
            'responsible_employee_id' => null,
        ];
    }

    public function removeMitigation(int $index): void
    {
        unset($this->mitigations[$index]);
        $this->mitigations = array_values($this->mitigations);
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            return;
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'in:'.collect(RiskCategory::cases())->pluck('value')->implode(',')],
            'probability' => ['required', 'integer', 'min:1', 'max:5'],
            'impact' => ['required', 'integer', 'min:1', 'max:5'],
            'residual_probability' => ['nullable', 'integer', 'min:1', 'max:5'],
            'residual_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'status' => ['required', 'in:'.collect(RiskStatus::cases())->pluck('value')->implode(',')],
            'treatment_strategy' => ['nullable', 'in:'.collect(RiskTreatmentStrategy::cases())->pluck('value')->implode(',')],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'review_due_at' => ['nullable', 'date'],
            'system_ids' => ['array'],
            'system_ids.*' => ['uuid', 'exists:systems,id'],
            'mitigations' => ['array'],
            'mitigations.*.title' => ['required_with:mitigations.*.description', 'nullable', 'string', 'max:255'],
            'mitigations.*.description' => ['nullable', 'string', 'max:2000'],
            'mitigations.*.status' => ['required', 'in:'.collect(RiskMitigationStatus::cases())->pluck('value')->implode(',')],
            'mitigations.*.target_date' => ['nullable', 'date'],
            'mitigations.*.responsible_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
        ]);

        $risk = DB::transaction(function () use ($validated) {
            $risk = Risk::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?: null,
                'category' => $validated['category'],
                'probability' => $validated['probability'],
                'impact' => $validated['impact'],
                'residual_probability' => $validated['residual_probability'],
                'residual_impact' => $validated['residual_impact'],
                'status' => $validated['status'],
                'treatment_strategy' => $validated['treatment_strategy'],
                'owner_user_id' => $validated['owner_user_id'],
                'review_due_at' => $validated['review_due_at'],
            ]);

            $risk->systems()->sync($validated['system_ids'] ?? []);

            foreach ($validated['mitigations'] ?? [] as $mitigation) {
                if (empty($mitigation['title'])) {
                    continue;
                }
                $risk->mitigations()->create([
                    'title' => $mitigation['title'],
                    'description' => $mitigation['description'] ?: null,
                    'status' => $mitigation['status'],
                    'target_date' => $mitigation['target_date'],
                    'responsible_employee_id' => $mitigation['responsible_employee_id'],
                ]);
            }

            return $risk;
        });

        $this->redirectRoute('risks.show', ['risk' => $risk->id], navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-4xl">
    <div class="mb-6">
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('risks.index')" wire:navigate>
            {{ __('Zurück') }}
        </flux:button>
        <flux:heading size="xl" class="mt-2">{{ __('Neues Risiko erfassen') }}</flux:heading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Basis') }}</flux:heading>
            <div class="mt-4 space-y-4">
                <flux:input wire:model="title" :label="__('Titel')" required />
                <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="3" />

                <flux:select wire:model="category" :label="__('Kategorie')" required>
                    @foreach (RiskCategory::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="owner_user_id" :label="__('Eigentümer')">
                    <flux:select.option value="">{{ __('— optional —') }}</flux:select.option>
                    @foreach ($this->teamUsers as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="review_due_at" :label="__('Nächster Review fällig')" type="date" />
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Bewertung') }}</flux:heading>
            <flux:subheading class="mb-4">
                {{ __('Score = Wahrscheinlichkeit × Schaden. Restrisiko erst ausfüllen, wenn Maßnahmen wirken.') }}
            </flux:subheading>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <flux:label>{{ __('Eintrittswahrscheinlichkeit (1–5)') }}</flux:label>
                    <input type="range" min="1" max="5" wire:model.live="probability" class="w-full" />
                    <flux:text class="text-sm text-zinc-500">{{ __('Wert:') }} {{ $probability }}</flux:text>
                </div>
                <div>
                    <flux:label>{{ __('Schadenshöhe (1–5)') }}</flux:label>
                    <input type="range" min="1" max="5" wire:model.live="impact" class="w-full" />
                    <flux:text class="text-sm text-zinc-500">{{ __('Wert:') }} {{ $impact }}</flux:text>
                </div>
            </div>

            <div class="mt-4 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                <flux:text class="text-sm">
                    {{ __('Aktueller Score:') }} <span class="font-semibold">{{ $probability * $impact }}</span>
                </flux:text>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <flux:select wire:model="residual_probability" :label="__('Restwahrscheinlichkeit (nach Maßnahmen)')">
                    <flux:select.option value="">{{ __('— offen —') }}</flux:select.option>
                    @foreach (range(1, 5) as $val)
                        <flux:select.option value="{{ $val }}">{{ $val }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="residual_impact" :label="__('Restschaden (nach Maßnahmen)')">
                    <flux:select.option value="">{{ __('— offen —') }}</flux:select.option>
                    @foreach (range(1, 5) as $val)
                        <flux:select.option value="{{ $val }}">{{ $val }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <flux:select wire:model="status" :label="__('Status')" required>
                    @foreach (RiskStatus::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="treatment_strategy" :label="__('Strategie')">
                    <flux:select.option value="">{{ __('— offen —') }}</flux:select.option>
                    @foreach (RiskTreatmentStrategy::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Verknüpfte Systeme') }}</flux:heading>
            <flux:subheading class="mb-4">
                {{ __('Welche Geschäfts-Systeme sind betroffen, wenn dieses Risiko eintritt?') }}
            </flux:subheading>
            @if ($this->systems->isEmpty())
                <flux:text class="text-sm text-zinc-500">
                    {{ __('Es sind noch keine Systeme erfasst.') }}
                </flux:text>
            @else
                <div class="grid gap-2 md:grid-cols-2">
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

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <flux:heading size="md">{{ __('Maßnahmen') }}</flux:heading>
                    <flux:subheading>{{ __('Konkrete Schritte zur Reduzierung. Können auch später ergänzt werden.') }}</flux:subheading>
                </div>
                <flux:button type="button" size="sm" icon="plus" wire:click="addMitigation">
                    {{ __('Hinzufügen') }}
                </flux:button>
            </div>

            @forelse ($mitigations as $index => $mitigation)
                <div class="mb-4 space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-xs uppercase text-zinc-500">{{ __('Maßnahme') }} #{{ $index + 1 }}</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeMitigation({{ $index }})" />
                    </div>
                    <flux:input wire:model="mitigations.{{ $index }}.title" :label="__('Titel')" />
                    <flux:textarea wire:model="mitigations.{{ $index }}.description" :label="__('Beschreibung')" rows="2" />
                    <div class="grid gap-3 md:grid-cols-3">
                        <flux:select wire:model="mitigations.{{ $index }}.status" :label="__('Status')">
                            @foreach (RiskMitigationStatus::cases() as $case)
                                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="mitigations.{{ $index }}.target_date" :label="__('Zieldatum')" type="date" />
                        <flux:select wire:model="mitigations.{{ $index }}.responsible_employee_id" :label="__('Verantwortlich')">
                            <flux:select.option value="">{{ __('— optional —') }}</flux:select.option>
                            @foreach ($this->employees as $employee)
                                <flux:select.option value="{{ $employee->id }}">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            @empty
                <flux:text class="text-sm text-zinc-500">
                    {{ __('Noch keine Maßnahme — können Sie auch später ergänzen.') }}
                </flux:text>
            @endforelse
        </div>

        <div class="flex items-center justify-end gap-2">
            <flux:button type="button" variant="filled" :href="route('risks.index')" wire:navigate>
                {{ __('Abbrechen') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Risiko speichern') }}
            </flux:button>
        </div>
    </form>
</section>
