<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Firmenweiter Echtzeit-Alarm: liegt in jedem Dashboard-Layout und lauscht über
 * Reverb/Echo auf {@see \App\Events\IncidentStarted} / {@see \App\Events\IncidentEnded}.
 * Zeigt einen Banner (ARIA-Live), eine echte Browser-Benachrichtigung (dedupliziert
 * über einen Tag, Klick öffnet den Ablauf) und – bei einem echten Notfall – einen
 * Alarmton plus Tab-Titel-Signal, wenn der Tab im Hintergrund ist.
 */
new class extends Component {
    public ?string $companyId = null;

    /** @var array{kind: string, title: string, url: ?string, by: ?string}|null */
    public ?array $alert = null;

    public function mount(): void
    {
        $this->companyId = Auth::user()?->currentCompany()?->id;
    }

    private function runUrl(?string $runId): ?string
    {
        return $runId ? route('scenario-runs.show', ['run' => $runId]) : null;
    }

    /**
     * @param  array{run_id: string, scenario_id: string, scenario_title: string, started_by: ?string}  $payload
     */
    #[On('echo-private:company.{companyId},.incident.started')]
    public function onIncidentStarted(array $payload): void
    {
        $this->alert = [
            'kind' => 'started',
            'title' => $payload['scenario_title'] ?? __('Notfall'),
            'url' => $this->runUrl($payload['run_id'] ?? null),
            'by' => $payload['started_by'] ?? null,
        ];

        $this->emitAlert(__('Notfall gemeldet'), $payload['run_id'] ?? null);
        $this->dispatch('incident-changed'); // Dashboard/Banner live aktualisieren
    }

    /**
     * @param  array{run_id: string, scenario_title: string, outcome: string, ended_by: ?string}  $payload
     */
    #[On('echo-private:company.{companyId},.incident.ended')]
    public function onIncidentEnded(array $payload): void
    {
        $heading = ($payload['outcome'] ?? null) === 'aborted' ? __('Notfall abgebrochen') : __('Notfall beendet');

        $this->alert = [
            'kind' => ($payload['outcome'] ?? null) === 'aborted' ? 'aborted' : 'ended',
            'title' => $payload['scenario_title'] ?? __('Notfall'),
            'url' => $this->runUrl($payload['run_id'] ?? null),
            'by' => $payload['ended_by'] ?? null,
        ];

        $this->emitAlert($heading, $payload['run_id'] ?? null);
        $this->dispatch('incident-changed'); // Dashboard/Banner live aktualisieren
    }

    private function emitAlert(string $heading, ?string $runId): void
    {
        $this->dispatch(
            'incident-alert',
            heading: $heading,
            title: $this->alert['title'],
            by: $this->alert['by'],
            url: $this->alert['url'],
            kind: $this->alert['kind'],
            tag: 'planb-incident-'.($runId ?? 'x'),
        );
    }

    public function dismiss(): void
    {
        $this->alert = null;
    }
}; ?>

<div>
    @if ($alert)
        @php
            $started = $alert['kind'] === 'started';
            $bg = $started ? 'bg-rose-600' : 'bg-slate-700';
            $label = match ($alert['kind']) {
                'started' => __('Notfall ausgelöst:'),
                'aborted' => __('Notfall abgebrochen:'),
                default => __('Notfall beendet:'),
            };
        @endphp
        <div
            role="{{ $started ? 'alert' : 'status' }}"
            aria-live="{{ $started ? 'assertive' : 'polite' }}"
            class="fixed inset-x-0 top-0 z-50 flex items-center justify-center gap-3 px-4 py-3 text-sm font-semibold text-white shadow-lg {{ $bg }}"
        >
            @if ($started)
                <svg class="h-5 w-5 shrink-0 animate-pulse" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
            @else
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
            @endif
            <span>
                {{ $label }}
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
        (() => {
            const originalTitle = document.title;
            let flashTimer = null;

            const stopFlash = () => {
                if (flashTimer) {
                    clearInterval(flashTimer);
                    flashTimer = null;
                    document.title = originalTitle;
                }
            };
            const startFlash = (text) => {
                if (flashTimer || !document.hidden) return;
                let on = false;
                flashTimer = setInterval(() => {
                    document.title = on ? originalTitle : '🔴 ' + text;
                    on = !on;
                }, 1000);
            };
            document.addEventListener('visibilitychange', () => { if (!document.hidden) stopFlash(); });
            window.addEventListener('focus', stopFlash);

            // Erlaubnis für Benachrichtigungen erst nach einer Nutzergeste anfragen
            // (viele Browser ignorieren/blocken den Prompt sonst).
            if (window.Notification && Notification.permission === 'default') {
                const ask = () => { try { Notification.requestPermission(); } catch (e) {} };
                window.addEventListener('click', ask, { once: true });
                window.addEventListener('keydown', ask, { once: true });
            }

            // Kurzer Alarmton per Web-Audio (kein Asset nötig).
            const beep = () => {
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (!Ctx) return;
                    const ctx = new Ctx();
                    const tone = (freq, start, dur) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.value = freq;
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        const t = ctx.currentTime + start;
                        gain.gain.setValueAtTime(0.0001, t);
                        gain.gain.exponentialRampToValueAtTime(0.3, t + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.0001, t + dur);
                        osc.start(t);
                        osc.stop(t + dur);
                    };
                    tone(880, 0, 0.25);
                    tone(660, 0.3, 0.35);
                } catch (e) { /* Ton optional */ }
            };

            $wire.on('incident-alert', (payload) => {
                const data = Array.isArray(payload) ? payload[0] : payload;
                const heading = (data && data.heading) ? data.heading : 'Notfall';
                const title = (data && data.title) ? data.title : 'Notfall';
                const by = data && data.by;
                const url = data && data.url;
                const kind = (data && data.kind) ? data.kind : 'started';
                const tag = (data && data.tag) ? data.tag : 'planb-incident';
                const body = by ? (title + ' — ' + by) : title;

                // Native Benachrichtigung: Tag dedupliziert über mehrere Tabs;
                // Klick fokussiert das Fenster und öffnet den Ablauf.
                try {
                    if (window.Notification && Notification.permission === 'granted') {
                        const notification = new Notification(heading, { body, tag });
                        notification.onclick = () => {
                            window.focus();
                            if (url) window.location.href = url;
                            notification.close();
                        };
                    }
                } catch (e) { /* Benachrichtigungen sind optional */ }

                // Nur bei echtem Notfall: Alarmton + Tab-Titel-Signal (falls Tab im Hintergrund).
                if (kind === 'started') {
                    beep();
                    startFlash(title);
                }
            });
        })();
    </script>
    @endscript
</div>
