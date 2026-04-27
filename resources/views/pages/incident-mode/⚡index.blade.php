<?php

use App\Models\Company;
use App\Support\Incident\Cockpit;
use App\Support\Incident\CockpitData;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Krisen-Cockpit')] class extends Component {
    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()?->currentCompany();
    }

    #[Computed]
    public function cockpit(): ?CockpitData
    {
        $company = $this->company;
        if (! $company) {
            return null;
        }

        return Cockpit::for($company);
    }

    #[Computed]
    public function isEnabled(): bool
    {
        $company = $this->company;
        if (! $company) {
            return false;
        }

        return Cockpit::isEnabledFor($company);
    }
}; ?>

<section class="w-full">
    @if (! $this->company)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @elseif (! $this->isEnabled)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Live-Inzident-Modus deaktiviert') }}</flux:heading>
            <flux:subheading>
                {{ __('Aktivieren Sie den Modus in den Systemeinstellungen, um im Ernstfall ein reduziertes Krisen-Cockpit zu sehen.') }}
            </flux:subheading>
            <flux:button class="mt-4" :href="route('system-settings.index')" wire:navigate icon="cog-8-tooth">
                {{ __('Zu den Einstellungen') }}
            </flux:button>
        </div>
    @else
        <div class="space-y-6">
            <div>
                <flux:heading size="xl">{{ __('Krisen-Cockpit') }}</flux:heading>
                <flux:subheading>
                    {{ __('Reduzierte Sicht für den Ernstfall – Krisenstab, Wiederanlauf-Reihenfolge, Schritte und Meldepflichten.') }}
                </flux:subheading>
            </div>

            @if (! $this->cockpit->hasActiveRun())
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <div class="flex items-start gap-3">
                        <flux:icon.shield-check class="mt-0.5 h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        <div>
                            <flux:heading size="lg" class="text-emerald-900 dark:text-emerald-100">{{ __('Kein aktiver Notfall') }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-emerald-800 dark:text-emerald-200">
                                {{ __('Wenn ein Szenario gestartet wird (z. B. von der Szenarien-Seite), öffnet sich hier automatisch das Krisen-Cockpit.') }}
                            </flux:text>
                            <flux:button class="mt-4" variant="ghost" :href="route('scenarios.index')" wire:navigate>
                                {{ __('Szenarien öffnen') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            @else
                {{-- Sektionen werden im Worktree-Agent B vollständig befüllt --}}
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-100">
                    {{ __('Aktiver Lauf:') }} <strong>{{ $this->cockpit->activeRun->scenario?->name ?? '–' }}</strong>
                    · {{ __('Gestartet:') }} {{ $this->cockpit->activeRun->started_at?->isoFormat('DD.MM.YYYY HH:mm') }}
                </div>
            @endif
        </div>
    @endif
</section>
