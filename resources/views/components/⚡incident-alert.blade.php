<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Firmenweiter Echtzeit-Alarm: liegt in jedem Dashboard-Layout und lauscht über
 * Reverb/Echo auf {@see \App\Events\IncidentStarted}. Wird ein Notfall ausgelöst
 * (App oder Dashboard), erscheint sofort ein roter Banner – plus eine echte
 * Browser-Benachrichtigung, falls der Nutzer sie erlaubt hat.
 */
new class extends Component {
    public ?string $companyId = null;

    /** @var array{title: string, url: ?string, by: ?string}|null */
    public ?array $alert = null;

    public function mount(): void
    {
        $this->companyId = Auth::user()?->currentCompany()?->id;
    }

    /**
     * @param  array{run_id: string, scenario_id: string, scenario_title: string, started_by: ?string}  $payload
     */
    #[On('echo-private:company.{companyId},.incident.started')]
    public function onIncidentStarted(array $payload): void
    {
        $this->alert = [
            'title' => $payload['scenario_title'] ?? __('Notfall'),
            'url' => isset($payload['run_id'])
                ? route('scenario-runs.show', ['run' => $payload['run_id']])
                : null,
            'by' => $payload['started_by'] ?? null,
        ];

        $this->dispatch('incident-alert', title: $this->alert['title'], by: $this->alert['by']);
    }

    public function dismiss(): void
    {
        $this->alert = null;
    }
}; ?>

<div>
    @if ($alert)
        <div class="fixed inset-x-0 top-0 z-50 flex items-center justify-center gap-3 bg-rose-600 px-4 py-3 text-sm font-semibold text-white shadow-lg">
            <svg class="h-5 w-5 shrink-0 animate-pulse" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
            <span>
                {{ __('Notfall ausgelöst:') }}
                <span class="font-bold">{{ $alert['title'] }}</span>
                @if ($alert['by'])
                    <span class="opacity-80">— {{ __('von') }} {{ $alert['by'] }}</span>
                @endif
            </span>
            @if ($alert['url'])
                <a href="{{ $alert['url'] }}" wire:navigate class="rounded bg-white/20 px-3 py-1 transition hover:bg-white/30">{{ __('Zum Ablauf') }}</a>
            @endif
            <button type="button" wire:click="dismiss" class="ml-1 rounded px-2 py-1 transition hover:bg-white/20" aria-label="{{ __('Schließen') }}">✕</button>
        </div>
    @endif

    @script
    <script>
        // Beim Laden einmal um Erlaubnis für Browser-Benachrichtigungen bitten.
        if (window.Notification && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // Auf den serverseitig ausgelösten Alarm reagieren: native Notification zeigen.
        $wire.on('incident-alert', (payload) => {
            const data = Array.isArray(payload) ? payload[0] : payload;
            const title = (data && data.title) ? data.title : 'Notfall';
            const by = data && data.by;
            const body = by ? ('Ausgelöst von ' + by) : 'Ein Notfall wurde ausgelöst.';
            try {
                if (window.Notification && Notification.permission === 'granted') {
                    new Notification('Notfall gemeldet: ' + title, { body });
                }
            } catch (e) { /* Benachrichtigungen sind optional */ }
        });
    </script>
    @endscript
</div>
