<?php

use App\Enums\AiSystemLogType;
use App\Models\AiSystem;
use App\Models\AiSystemLogEntry;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('KI-System')] class extends Component {
    public AiSystem $aiSystem;

    public string $logType = 'review';

    public string $logSummary = '';

    public ?string $logOccurredAt = null;

    public function mount(AiSystem $aiSystem): void
    {
        abort_if($aiSystem->company_id !== Auth::user()->currentCompany()?->id, 403);

        $this->aiSystem = $aiSystem->load('responsibleRole');
    }

    /**
     * @return Collection<int, AiSystemLogEntry>
     */
    #[Computed]
    public function logEntries(): Collection
    {
        return $this->aiSystem->logEntries()->with('user')->get();
    }

    public function addLogEntry(): void
    {
        $validated = $this->validate([
            'logType' => ['required', new Enum(AiSystemLogType::class)],
            'logSummary' => ['required', 'string', 'max:5000'],
            'logOccurredAt' => ['nullable', 'date'],
        ]);

        AiSystemLogEntry::create([
            'company_id' => $this->aiSystem->company_id,
            'ai_system_id' => $this->aiSystem->id,
            'user_id' => Auth::id(),
            'type' => $validated['logType'],
            'summary' => $validated['logSummary'],
            'occurred_at' => $validated['logOccurredAt'] ?: now()->toDateString(),
        ]);

        $this->reset(['logSummary', 'logOccurredAt']);
        $this->logType = 'review';
        unset($this->logEntries);

        Flux::toast(variant: 'success', text: __('Protokoll-Eintrag gespeichert.'));
    }
}; ?>

<section class="mx-auto w-full max-w-3xl">
    <div class="mb-2">
        <flux:link :href="route('ai-systems.index')" wire:navigate class="text-sm">
            ← {{ __('Alle KI-Systeme') }}
        </flux:link>
    </div>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div class="min-w-0">
            <flux:heading size="xl">{{ $aiSystem->name }}</flux:heading>
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                <flux:badge :color="$aiSystem->risk_class->color()" size="sm">{{ $aiSystem->risk_class->label() }}</flux:badge>
                <flux:badge color="zinc" size="sm">{{ $aiSystem->role->label() }}</flux:badge>
                @if ($aiSystem->isReviewOverdue())
                    <flux:badge color="red" size="sm" icon="arrow-path">{{ __('Prüfung überfällig') }}</flux:badge>
                @endif
            </div>
        </div>
        <flux:button size="sm" variant="filled" icon="pencil" :href="route('ai-systems.index')" wire:navigate>
            {{ __('Bearbeiten') }}
        </flux:button>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-100">
            <span class="font-semibold">{{ __('Pflichtenlage') }}:</span> {{ $aiSystem->risk_class->obligationHint() }}
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base" class="mb-3">{{ __('Systemangaben') }}</flux:heading>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                @foreach ([
                    __('Zweck / Einsatzkontext') => $aiSystem->purpose,
                    __('Anbieter / Hersteller') => $aiSystem->provider_name,
                    __('Annex-III-Kategorie') => $aiSystem->annex_category,
                    __('Menschliche Aufsicht') => $aiSystem->human_oversight,
                    __('Datenquellen / Trainingsdaten') => $aiSystem->data_sources,
                    __('Transparenzmaßnahmen') => $aiSystem->transparency_measures,
                    __('Konformitätsstatus') => $aiSystem->conformity_status,
                    __('EU-Datenbank-Registrierung') => $aiSystem->eu_db_registration,
                    __('Zuständige interne Rolle') => $aiSystem->responsibleRole?->name,
                ] as $label => $value)
                    @if ($value)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="whitespace-pre-line text-zinc-800 dark:text-zinc-200">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
                <div>
                    <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Letzte / nächste Prüfung') }}</dt>
                    <dd class="text-zinc-800 dark:text-zinc-200">
                        {{ $aiSystem->last_reviewed_at?->format('d.m.Y') ?? '—' }} / {{ $aiSystem->next_review_at?->format('d.m.Y') ?? '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base">{{ __('Protokoll & Nachweise') }}</flux:heading>
            <flux:subheading class="mb-4">{{ __('Revisionssichere Nachweisführung: Prüfungen, Aufsichts-Eingriffe, Tests, Vorfälle, Änderungen, Schulungen. Änderungen an den Stammdaten werden zusätzlich automatisch im Audit-Log erfasst.') }}</flux:subheading>

            <form wire:submit="addLogEntry" class="mb-5 space-y-3 rounded-lg border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:select wire:model="logType" :label="__('Art')" required>
                        @foreach (App\Enums\AiSystemLogType::options() as $option)
                            <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="logOccurredAt" :label="__('Datum')" type="date" />
                </div>
                <flux:textarea wire:model="logSummary" :label="__('Was ist passiert / geprüft worden?')" rows="2" required placeholder="z. B. Halbjährliche Prüfung der menschlichen Aufsicht durchgeführt, keine Abweichungen." />
                <div class="flex justify-end">
                    <flux:button variant="primary" type="submit" icon="plus" size="sm">{{ __('Eintrag hinzufügen') }}</flux:button>
                </div>
            </form>

            @forelse ($this->logEntries as $entry)
                <div wire:key="log-{{ $entry->id }}" class="flex gap-3 border-t border-zinc-100 py-3 first:border-t-0 dark:border-zinc-800">
                    <div class="shrink-0">
                        <flux:badge :color="$entry->type->color()" size="sm">{{ $entry->type->label() }}</flux:badge>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-line">{{ $entry->summary }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $entry->occurred_at->format('d.m.Y') }}@if ($entry->user) · {{ $entry->user->name }}@endif
                        </div>
                    </div>
                </div>
            @empty
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Noch keine Protokoll-Einträge.') }}</flux:text>
            @endforelse
        </div>
    </div>
</section>
