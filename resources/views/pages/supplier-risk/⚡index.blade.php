<?php

use App\Enums\PreventiveMeasureInterval;
use App\Enums\SecurityAssessmentStatus;
use App\Enums\SupplierCriticality;
use App\Models\ServiceProvider;
use App\Models\SupplierRiskAssessment;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Lieferketten-Risiko')] class extends Component {
    public ?string $editingId = null;

    public string $service_provider_id = '';

    public string $criticality = '';

    public string $security_status = '';

    public string $interval = '';

    public ?string $last_assessed_at = null;

    public ?string $next_assessment_at = null;

    public string $alternative_provider = '';

    public string $notes = '';

    public string $filterCriticality = '';

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->criticality = SupplierCriticality::Mittel->value;
        $this->security_status = SecurityAssessmentStatus::NotAssessed->value;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, SupplierRiskAssessment>
     */
    #[Computed]
    public function assessments(): Collection
    {
        return SupplierRiskAssessment::query()
            ->with('serviceProvider')
            ->when($this->filterCriticality !== '', fn ($q) => $q->where('criticality', $this->filterCriticality))
            ->orderBy('next_assessment_at')
            ->get();
    }

    /**
     * @return Collection<int, ServiceProvider>
     */
    #[Computed]
    public function providers(): Collection
    {
        return ServiceProvider::query()->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();

        Flux::modal('assessment-form')->show();
    }

    public function openEdit(string $id): void
    {
        $a = SupplierRiskAssessment::findOrFail($id);

        $this->editingId = $a->id;
        $this->service_provider_id = (string) $a->service_provider_id;
        $this->criticality = $a->criticality->value;
        $this->security_status = $a->security_status->value;
        $this->interval = $a->interval?->value ?? '';
        $this->last_assessed_at = $a->last_assessed_at?->toDateString();
        $this->next_assessment_at = $a->next_assessment_at?->toDateString();
        $this->alternative_provider = (string) $a->alternative_provider;
        $this->notes = (string) $a->notes;

        Flux::modal('assessment-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'service_provider_id' => ['required', 'string', Rule::exists('service_providers', 'id')],
            'criticality' => ['required', Rule::in(collect(SupplierCriticality::cases())->pluck('value'))],
            'security_status' => ['required', Rule::in(collect(SecurityAssessmentStatus::cases())->pluck('value'))],
            'interval' => ['nullable', Rule::in(collect(PreventiveMeasureInterval::cases())->pluck('value'))],
            'last_assessed_at' => ['nullable', 'date'],
            'next_assessment_at' => ['nullable', 'date'],
            'alternative_provider' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();

        // Bei gesetztem Intervall ohne explizite Fälligkeit diese aus dem
        // Intervall ableiten (ab letzter Bewertung, sonst ab heute).
        if (! empty($payload['interval']) && empty($payload['next_assessment_at'])) {
            $base = $payload['last_assessed_at'] ?? \Carbon\CarbonImmutable::now()->toDateString();
            $months = PreventiveMeasureInterval::from($payload['interval'])->months();
            $payload['next_assessment_at'] = \Carbon\CarbonImmutable::parse($base)->addMonths($months)->toDateString();
        }

        if ($this->editingId) {
            SupplierRiskAssessment::findOrFail($this->editingId)->update($payload);
        } else {
            SupplierRiskAssessment::create($payload);
        }

        Flux::modal('assessment-form')->close();
        $this->resetForm();
        unset($this->assessments);

        Flux::toast(variant: 'success', text: __('Risikobewertung gespeichert.'));
    }

    public function markAssessed(string $id): void
    {
        SupplierRiskAssessment::findOrFail($id)->markAssessed();
        unset($this->assessments);

        Flux::toast(variant: 'success', text: __('Als bewertet markiert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('assessment-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            SupplierRiskAssessment::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->assessments);
            Flux::modal('assessment-delete')->close();
            Flux::toast(variant: 'success', text: __('Risikobewertung gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'service_provider_id', 'interval', 'last_assessed_at', 'next_assessment_at', 'alternative_provider', 'notes']);
        $this->criticality = SupplierCriticality::Mittel->value;
        $this->security_status = SecurityAssessmentStatus::NotAssessed->value;
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Lieferketten-Risiko') }}</flux:heading>
            <flux:subheading>
                {{ __('Risikobewertung je Dienstleister – Kritikalität, Sicherheitsbewertung und Wiederbewertung (NIS2 Art. 21 Supply-Chain).') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neue Bewertung') }}
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
                @foreach (\App\Enums\SupplierCriticality::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->assessments as $assessment)
            <div wire:key="assessment-{{ $assessment->id }}" class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $assessment->serviceProvider?->name }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge :color="$assessment->criticality->color()" size="sm">{{ $assessment->criticality->label() }}</flux:badge>
                            <flux:badge :color="$assessment->security_status->color()" size="sm">{{ $assessment->security_status->label() }}</flux:badge>
                            @if ($assessment->isOverdue())
                                <flux:badge color="red" size="sm">{{ __('Überfällig') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="check-circle" wire:click="markAssessed('{{ $assessment->id }}')">
                                {{ __('Als bewertet markieren') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $assessment->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $assessment->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($assessment->interval)
                        <div class="flex items-center gap-2">
                            <flux:icon.arrow-path class="h-4 w-4 text-zinc-400" />
                            <span>{{ $assessment->interval->label() }}</span>
                        </div>
                    @endif

                    @if ($assessment->next_assessment_at)
                        <div class="flex items-center gap-2">
                            <flux:icon.calendar class="h-4 w-4 text-zinc-400" />
                            <span @class(['text-red-600 dark:text-red-400 font-medium' => $assessment->isOverdue()])>
                                {{ __('Nächste Bewertung') }}: {{ $assessment->next_assessment_at->format('d.m.Y') }}
                            </span>
                        </div>
                    @endif

                    @if ($assessment->alternative_provider)
                        <div class="flex items-center gap-2">
                            <flux:icon.arrows-right-left class="h-4 w-4 text-zinc-400" />
                            <span>{{ __('Ausweich') }}: {{ $assessment->alternative_provider }}</span>
                        </div>
                    @endif

                    @if ($assessment->notes)
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $assessment->notes }}</flux:text>
                    @endif
                </div>

                <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <flux:button size="sm" variant="filled" icon="check-circle" wire:click="markAssessed('{{ $assessment->id }}')" class="w-full">
                        {{ __('Als bewertet markieren') }}
                    </flux:button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Risikobewertungen hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="assessment-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Bewertung bearbeiten') : __('Neue Risikobewertung') }}
                </flux:heading>
                <flux:subheading>{{ __('Wie kritisch ist der Dienstleister, wie ist die Sicherheitsbewertung, wann wird wiederbewertet?') }}</flux:subheading>
            </div>

            <flux:select wire:model="service_provider_id" :label="__('Dienstleister')" required>
                <flux:select.option value="">{{ __('Bitte wählen') }}</flux:select.option>
                @foreach ($this->providers as $provider)
                    <flux:select.option value="{{ $provider->id }}">{{ $provider->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="criticality" :label="__('Kritikalität')" required>
                    @foreach (\App\Enums\SupplierCriticality::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="security_status" :label="__('Sicherheitsbewertung')" required>
                    @foreach (\App\Enums\SecurityAssessmentStatus::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:select wire:model="interval" :label="__('Wiederbewertung')" :description="__('Leer = einmalige Bewertung.')">
                <flux:select.option value="">{{ __('Einmalig (keine Wiederholung)') }}</flux:select.option>
                @foreach (\App\Enums\PreventiveMeasureInterval::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="last_assessed_at" :label="__('Zuletzt bewertet')" type="date" />
                <flux:input wire:model="next_assessment_at" :label="__('Nächste Bewertung')" type="date" />
            </div>

            <flux:input wire:model="alternative_provider" :label="__('Ausweich-Dienstleister')" type="text" placeholder="z. B. Backup-Lieferant" />
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

    <flux:modal name="assessment-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Bewertung löschen?') }}</flux:heading>
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
