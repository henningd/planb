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
        <a
            href="{{ route('incident-mode.index') }}"
            wire:navigate
            class="flex items-center justify-center gap-2 bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700"
        >
            <svg class="h-4 w-4 animate-pulse" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
            <span>{{ __('Aktiver Notfall:') }} <span class="font-bold">{{ $this->activeRun->scenario?->name ?? __('Szenario') }}</span></span>
            <span class="opacity-80">{{ __('— Zum Krisen-Cockpit') }}</span>
        </a>
    @endif
</div>
