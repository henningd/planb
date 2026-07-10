<?php

use App\Enums\InsuranceType;
use App\Models\InsurancePolicy;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Versicherung')] class extends Component {
    public InsurancePolicy $policy;

    public bool $confirmingDelete = false;

    public function mount(InsurancePolicy $policy): void
    {
        abort_unless(Auth::user()?->currentCompany(), 403);

        $this->policy = $policy;
    }

    public function delete()
    {
        $this->policy->delete();

        Flux::toast(variant: 'success', text: __('Versicherung gelöscht.'));

        return redirect()->route('insurance-policies.index');
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('insurance-policies.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Versicherungen') }}
        </flux:link>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-start justify-between gap-4 p-6">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl">{{ $policy->insurer }}</flux:heading>
                    <flux:badge :color="$policy->type === InsuranceType::Cyber ? 'sky' : 'zinc'" size="sm">
                        {{ $policy->type->label() }}
                    </flux:badge>
                </div>
                @if ($policy->contact_name)
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Ansprechpartner:in') }}: {{ $policy->contact_name }}
                    </flux:text>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <flux:button icon="pencil" :href="route('insurance-policies.index')" wire:navigate>
                    {{ __('Bearbeiten') }}
                </flux:button>
                <flux:button variant="danger" icon="trash" wire:click="$set('confirmingDelete', true)">
                    {{ __('Löschen') }}
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Police-Daten') }}</flux:heading>
            <dl class="mt-4 space-y-3 text-sm">
                @if ($policy->policy_number)
                    <div class="flex items-start gap-3">
                        <flux:icon.document-text class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Policennummer') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $policy->policy_number }}</dd>
                        </div>
                    </div>
                @endif
                @if ($policy->valid_from || $policy->valid_until)
                    <div class="flex items-start gap-3">
                        <flux:icon.calendar class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Laufzeit') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">
                                {{ $policy->valid_from?->format('d.m.Y') ?? '—' }} – {{ $policy->valid_until?->format('d.m.Y') ?? '—' }}
                                @if ($policy->isExpired()) <flux:badge color="red" size="sm">{{ __('abgelaufen') }}</flux:badge>@endif
                            </dd>
                        </div>
                    </div>
                @endif
                @if ($policy->coverage_amount)
                    <div class="flex items-start gap-3">
                        <flux:icon.banknotes class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Deckungssumme') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $policy->coverage_amount }}</dd>
                        </div>
                    </div>
                @endif
                @if ($policy->responsibleRole)
                    <div class="flex items-start gap-3">
                        <flux:icon.identification class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Zuständige interne Rolle') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $policy->responsibleRole->name }}</dd>
                        </div>
                    </div>
                @endif
                @if (! $policy->policy_number && ! $policy->valid_from && ! $policy->valid_until && ! $policy->coverage_amount && ! $policy->responsibleRole)
                    <flux:text class="text-sm text-zinc-500">{{ __('Keine weiteren Police-Daten hinterlegt.') }}</flux:text>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Notfall-Kontakt') }}</flux:heading>
            <dl class="mt-4 space-y-3 text-sm">
                @if ($policy->hotline)
                    <div class="flex items-start gap-3">
                        <flux:icon.phone class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Notfall-Hotline') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">
                                <a href="tel:{{ $policy->hotline }}" class="hover:underline">{{ $policy->hotline }}</a>
                            </dd>
                        </div>
                    </div>
                @endif
                @if ($policy->email)
                    <div class="flex items-start gap-3">
                        <flux:icon.envelope class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('E-Mail') }}</dt>
                            <dd class="truncate text-zinc-900 dark:text-zinc-100">
                                <a href="mailto:{{ $policy->email }}" class="hover:underline">{{ $policy->email }}</a>
                            </dd>
                        </div>
                    </div>
                @endif
                @if (! $policy->hotline && ! $policy->email)
                    <flux:text class="text-sm text-zinc-500">{{ __('Keine Notfall-Kontaktdaten hinterlegt.') }}</flux:text>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <flux:heading size="lg">{{ __('Schadenbedingungen') }}</flux:heading>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                @if ($policy->reporting_window)
                    <div class="flex items-start gap-3">
                        <flux:icon.clock class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Meldefrist') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $policy->reporting_window }}</dd>
                        </div>
                    </div>
                @endif
                @if ($policy->deductible)
                    <div class="flex items-start gap-3">
                        <flux:icon.banknotes class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Selbstbehalt') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $policy->deductible }}</dd>
                        </div>
                    </div>
                @endif
                @if (! $policy->reporting_window && ! $policy->deductible)
                    <flux:text class="text-sm text-zinc-500 sm:col-span-2">{{ __('Keine Schadenbedingungen hinterlegt.') }}</flux:text>
                @endif
            </dl>
            @if ($policy->required_documents)
                <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Benötigte Unterlagen im Schadenfall') }}</div>
                    <flux:text class="mt-1 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200">{{ $policy->required_documents }}</flux:text>
                </div>
            @endif
            @if ($policy->approval_required || $policy->approval_note)
                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                    <strong>{{ __('Freigabe nötig') }}:</strong>
                    {{ $policy->approval_note ?: __('Vor Beauftragung von Forensik / Sanierung / Ersatzbeschaffung ist die Freigabe des Versicherers einzuholen.') }}
                </div>
            @endif
        </div>

        @if ($policy->scenarios->isNotEmpty() || $policy->claims_process_tested_at || $policy->last_reviewed_at || $policy->next_review_at)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <flux:heading size="lg">{{ __('Szenariobezug, Prüfung & Nachweise') }}</flux:heading>
                @if ($policy->scenarios->isNotEmpty())
                    <div class="mt-4">
                        <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Greift bei folgenden Szenarien') }}</div>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @foreach ($policy->scenarios as $scenario)
                                <flux:badge color="zinc" size="sm">{{ $scenario->name }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                @endif
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Schadenmeldeweg getestet') }}</dt>
                        <dd class="text-zinc-900 dark:text-zinc-100">{{ $policy->claims_process_tested_at?->format('d.m.Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Letzte Prüfung') }}</dt>
                        <dd class="text-zinc-900 dark:text-zinc-100">{{ $policy->last_reviewed_at?->format('d.m.Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Nächste Prüfung') }}</dt>
                        <dd @class(['text-rose-600 dark:text-rose-400 font-medium' => $policy->isReviewOverdue(), 'text-zinc-900 dark:text-zinc-100' => ! $policy->isReviewOverdue()])>
                            {{ $policy->next_review_at?->format('d.m.Y') ?? '—' }}{{ $policy->isReviewOverdue() ? ' ('.__('überfällig').')' : '' }}
                        </dd>
                    </div>
                </dl>
            </div>
        @endif

        @if ($policy->notes)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <flux:heading size="lg">{{ __('Notizen') }}</flux:heading>
                <flux:text class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $policy->notes }}</flux:text>
            </div>
        @endif
    </div>

    <div class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
        {{ __('Zuletzt aktualisiert :at', ['at' => $policy->updated_at?->isoFormat('LLL')]) }}
    </div>

    <flux:modal wire:model.self="confirmingDelete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Versicherung löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:button type="button" variant="filled" wire:click="$set('confirmingDelete', false)">
                    {{ __('Abbrechen') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
