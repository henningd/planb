<?php

use App\Enums\RiskMitigationStatus;
use App\Enums\RiskStatus;
use App\Models\Risk;
use App\Models\RiskMitigation;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Risiko-Detail')] class extends Component {
    public Risk $risk;

    public string $new_mitigation_title = '';

    public string $new_mitigation_description = '';

    public string $new_mitigation_status = 'planned';

    public ?string $new_mitigation_target_date = null;

    public function mount(Risk $risk): void
    {
        abort_if($risk->company_id !== Auth::user()->currentCompany()?->id, 403);

        $this->risk = $risk->load(['systems', 'owner', 'mitigations.responsibleEmployee']);
    }

    public function changeStatus(string $value): void
    {
        $allowed = collect(RiskStatus::cases())->pluck('value')->all();

        if (! in_array($value, $allowed, true)) {
            return;
        }

        $this->risk->update(['status' => $value]);
        $this->risk->refresh();
    }

    public function openAddMitigation(): void
    {
        $this->reset(['new_mitigation_title', 'new_mitigation_description', 'new_mitigation_target_date']);
        $this->new_mitigation_status = RiskMitigationStatus::Planned->value;
        Flux::modal('mitigation-add')->show();
    }

    public function addMitigation(): void
    {
        $validated = $this->validate([
            'new_mitigation_title' => ['required', 'string', 'max:255'],
            'new_mitigation_description' => ['nullable', 'string', 'max:2000'],
            'new_mitigation_status' => ['required', 'in:'.collect(RiskMitigationStatus::cases())->pluck('value')->implode(',')],
            'new_mitigation_target_date' => ['nullable', 'date'],
        ]);

        $this->risk->mitigations()->create([
            'title' => $validated['new_mitigation_title'],
            'description' => $validated['new_mitigation_description'] ?: null,
            'status' => $validated['new_mitigation_status'],
            'target_date' => $validated['new_mitigation_target_date'],
        ]);

        $this->risk->load('mitigations.responsibleEmployee');
        Flux::modal('mitigation-add')->close();
    }

    public function cycleMitigationStatus(string $mitigationId): void
    {
        $mitigation = RiskMitigation::whereKey($mitigationId)
            ->where('risk_id', $this->risk->id)
            ->first();

        if (! $mitigation) {
            return;
        }

        $next = match ($mitigation->status) {
            RiskMitigationStatus::Planned => RiskMitigationStatus::InProgress,
            RiskMitigationStatus::InProgress => RiskMitigationStatus::Implemented,
            RiskMitigationStatus::Implemented => RiskMitigationStatus::Verified,
            RiskMitigationStatus::Verified => RiskMitigationStatus::Planned,
        };

        $mitigation->update([
            'status' => $next,
            'implemented_at' => $next === RiskMitigationStatus::Implemented || $next === RiskMitigationStatus::Verified
                ? ($mitigation->implemented_at ?? now()->toDateString())
                : null,
        ]);

        $this->risk->load('mitigations.responsibleEmployee');
    }

    public function deleteMitigation(string $mitigationId): void
    {
        RiskMitigation::whereKey($mitigationId)
            ->where('risk_id', $this->risk->id)
            ->delete();

        $this->risk->load('mitigations.responsibleEmployee');
    }

    public function deleteRisk(): void
    {
        $this->risk->delete();
        $this->redirectRoute('risks.index', navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('risks.index')" wire:navigate>
            {{ __('Zurück zum Register') }}
        </flux:button>

        <div class="mt-2 flex items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $risk->title }}</flux:heading>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <flux:badge :color="$risk->category->color()">{{ $risk->category->label() }}</flux:badge>
                    <flux:badge :color="$risk->severityColor()">
                        {{ __('Score:') }} {{ $risk->score() }} · {{ $risk->severityLabel() }}
                    </flux:badge>
                    @if ($risk->residualScore() !== null)
                        <flux:badge color="emerald">
                            {{ __('Restrisiko:') }} {{ $risk->residualScore() }}
                        </flux:badge>
                    @endif
                    <flux:badge :color="$risk->status->color()">{{ $risk->status->label() }}</flux:badge>
                    @if ($risk->isOverdue())
                        <flux:badge color="rose">{{ __('Review überfällig') }}</flux:badge>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <flux:dropdown>
                    <flux:button size="sm">{{ __('Status ändern') }}</flux:button>
                    <flux:menu>
                        @foreach (RiskStatus::cases() as $statusCase)
                            <flux:menu.item wire:click="changeStatus('{{ $statusCase->value }}')">
                                {{ $statusCase->label() }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
                <flux:button
                    size="sm"
                    variant="danger"
                    icon="trash"
                    wire:click="deleteRisk"
                    wire:confirm="{{ __('Dieses Risiko wirklich löschen?') }}"
                >
                    {{ __('Löschen') }}
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Beschreibung') }}</flux:heading>
            <flux:text class="mt-2 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">
                {{ $risk->description ?: __('— keine Beschreibung —') }}
            </flux:text>
            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <flux:text class="text-xs uppercase text-zinc-500">{{ __('Eigentümer') }}</flux:text>
                    <div>{{ $risk->owner?->name ?? '—' }}</div>
                </div>
                <div>
                    <flux:text class="text-xs uppercase text-zinc-500">{{ __('Strategie') }}</flux:text>
                    <div>{{ $risk->treatment_strategy?->label() ?? '—' }}</div>
                </div>
                <div>
                    <flux:text class="text-xs uppercase text-zinc-500">{{ __('Review fällig') }}</flux:text>
                    <div @class(['text-rose-600 dark:text-rose-400 font-medium' => $risk->isOverdue()])>
                        {{ $risk->review_due_at?->format('d.m.Y') ?? '—' }}
                    </div>
                </div>
                <div>
                    <flux:text class="text-xs uppercase text-zinc-500">{{ __('Erstellt') }}</flux:text>
                    <div>{{ $risk->created_at->format('d.m.Y') }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Verknüpfte Systeme') }}</flux:heading>
            @if ($risk->systems->isEmpty())
                <flux:text class="mt-2 text-sm text-zinc-500">{{ __('Keine Systeme verknüpft.') }}</flux:text>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($risk->systems as $system)
                        <li>
                            <a href="{{ route('systems.show', $system) }}" wire:navigate class="text-sm hover:underline">
                                {{ $system->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="md">{{ __('Maßnahmen') }}</flux:heading>
            <flux:button size="sm" variant="primary" icon="plus" wire:click="openAddMitigation">
                {{ __('Hinzufügen') }}
            </flux:button>
        </div>

        @forelse ($risk->mitigations as $mitigation)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 py-3 first:border-t-0 dark:border-zinc-800">
                <div class="min-w-0 flex-1">
                    <div class="font-medium">{{ $mitigation->title }}</div>
                    @if ($mitigation->description)
                        <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $mitigation->description }}
                        </flux:text>
                    @endif
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                        @if ($mitigation->responsibleEmployee)
                            <span>{{ $mitigation->responsibleEmployee->first_name }} {{ $mitigation->responsibleEmployee->last_name }}</span>
                        @endif
                        @if ($mitigation->target_date)
                            <span>{{ __('Ziel:') }} {{ $mitigation->target_date->format('d.m.Y') }}</span>
                        @endif
                        @if ($mitigation->implemented_at)
                            <span>{{ __('Umgesetzt:') }} {{ $mitigation->implemented_at->format('d.m.Y') }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="cycleMitigationStatus('{{ $mitigation->id }}')" class="cursor-pointer">
                        <flux:badge :color="$mitigation->status->color()">{{ $mitigation->status->label() }}</flux:badge>
                    </button>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        wire:click="deleteMitigation('{{ $mitigation->id }}')"
                        wire:confirm="{{ __('Maßnahme löschen?') }}"
                    />
                </div>
            </div>
        @empty
            <flux:text class="text-sm text-zinc-500">{{ __('Noch keine Maßnahmen erfasst.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="mitigation-add" class="max-w-xl">
        <form wire:submit="addMitigation" class="space-y-5">
            <flux:heading size="lg">{{ __('Maßnahme hinzufügen') }}</flux:heading>
            <flux:input wire:model="new_mitigation_title" :label="__('Titel')" required />
            <flux:textarea wire:model="new_mitigation_description" :label="__('Beschreibung')" rows="3" />
            <flux:select wire:model="new_mitigation_status" :label="__('Status')">
                @foreach (RiskMitigationStatus::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="new_mitigation_target_date" :label="__('Zieldatum')" type="date" />
            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Hinzufügen') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
