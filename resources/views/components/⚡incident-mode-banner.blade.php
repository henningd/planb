<?php

use App\Models\ScenarioRun;
use App\Support\Incident\Cockpit;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function activeRun(): ?ScenarioRun
    {
        $company = Auth::user()?->currentCompany();
        if (! $company || ! Cockpit::isEnabledFor($company)) {
            return null;
        }

        return ScenarioRun::query()
            ->where('company_id', $company->id)
            ->whereNull('ended_at')
            ->whereNull('aborted_at')
            ->with('scenario')
            ->orderByDesc('started_at')
            ->first();
    }
}; ?>

<div>
    @if ($this->activeRun)
        @php
            $isReal = $this->activeRun->mode?->value === 'real';
            $bg = $isReal ? 'bg-rose-600 hover:bg-rose-700' : 'bg-indigo-600 hover:bg-indigo-700';
            $label = $isReal ? __('Aktiver Notfall:') : __('Laufende Übung:');
        @endphp
        <a
            href="{{ route('incident-mode.index') }}"
            wire:navigate
            class="block px-4 py-2 text-sm font-semibold text-white shadow-sm transition {{ $bg }}"
        >
            <span class="flex items-start gap-2">
                @if ($isReal)
                    <svg class="mt-0.5 h-4 w-4 shrink-0 animate-pulse" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                @else
                    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path d="M9.394 1.072a.75.75 0 0 1 1.212 0l3.029 4.034c.273.364.087.886-.337.961l-1.918.34v6.343a4 4 0 0 1-2.92 3.852l-3.5 1.05a.75.75 0 0 1-.96-.72V6.407l-1.918-.34c-.424-.075-.61-.597-.337-.961l3.03-4.034ZM10 4.5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-1.5 0v-6.5A.75.75 0 0 1 10 4.5Z"/></svg>
                @endif
                <span class="min-w-0 leading-snug">{{ $label }} <span class="font-bold">{{ $this->activeRun->title ?? $this->activeRun->scenario?->name ?? __('Szenario') }}</span></span>
            </span>
            <span class="mt-1 flex items-center gap-1 ps-6 text-xs opacity-90 underline underline-offset-2">
                {{ __('Zum Krisen-Cockpit') }}
                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
            </span>
        </a>
    @endif
</div>
