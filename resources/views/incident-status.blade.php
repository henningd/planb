<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Selbst-Aktualisierung alle 15 Sekunden (funktioniert ohne App/JS-Framework). --}}
    <meta http-equiv="refresh" content="15">
    <meta name="robots" content="noindex, nofollow">
    <title>Lagestatus · {{ $run->title ?? $run->scenario?->name ?? 'Notfall' }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #e5e7eb; } .card { background: #1e293b !important; border-color: #334155 !important; } .muted { color: #94a3b8 !important; } .bar-track { background: #334155 !important; } }
        .wrap { max-width: 720px; margin: 0 auto; padding: 20px 16px 48px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin-bottom: 16px; }
        h1 { font-size: 20px; margin: 4px 0 2px; }
        h2 { font-size: 14px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin: 0 0 10px; }
        .muted { color: #6b7280; font-size: 13px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .b-aktiv { background: #dbeafe; color: #1e40af; }
        .b-eskaliert { background: #fee2e2; color: #991b1b; }
        .b-beendet { background: #dcfce7; color: #166534; }
        .b-abgebrochen { background: #e5e7eb; color: #374151; }
        .bar-track { background: #e5e7eb; border-radius: 999px; height: 12px; overflow: hidden; margin: 8px 0; }
        .bar-fill { background: #2563eb; height: 100%; }
        ul { list-style: none; padding: 0; margin: 0; }
        li { padding: 7px 0; border-top: 1px solid #f1f5f9; font-size: 14px; }
        @media (prefers-color-scheme: dark) { li { border-color: #334155; } }
        li:first-child { border-top: 0; }
        .done { color: #6b7280; text-decoration: line-through; }
        .dot { display: inline-block; width: 8px; height: 8px; border-radius: 999px; margin-right: 8px; vertical-align: middle; }
        .dot-open { background: #f59e0b; }
        .dot-done { background: #16a34a; }
        .foot { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="wrap">
        @php($progress = $run->progress())
        @php($phase = $run->phaseLabel())
        <div class="card">
            <div class="muted">{{ $company?->name }}</div>
            <h1>{{ $run->title ?? $run->scenario?->name ?? 'Notfall' }}</h1>
            <div style="margin-top:8px">
                <span class="badge b-{{ $phase }}">{{ ucfirst($phase) }}</span>
                @if ($run->isDrill())<span class="badge b-abgebrochen">Übung</span>@endif
            </div>

            <div style="margin-top:16px">
                <div class="muted">Fortschritt: {{ $progress['done'] }} / {{ $progress['total'] }} Schritte</div>
                <div class="bar-track"><div class="bar-fill" style="width: {{ $progress['percent'] }}%"></div></div>
            </div>

            <div class="muted" style="margin-top:10px">
                Begonnen: {{ $run->started_at?->format('d.m.Y H:i') ?? '—' }} Uhr
                @if ($run->ended_at) · Beendet: {{ $run->ended_at->format('d.m.Y H:i') }} Uhr @endif
            </div>
        </div>

        <div class="card">
            <h2>Offene Schritte</h2>
            @php($open = $run->steps->whereNull('checked_at'))
            @if ($open->isEmpty())
                <div class="muted">Keine offenen Schritte.</div>
            @else
                <ul>
                    @foreach ($open->take(15) as $step)
                        <li><span class="dot dot-open"></span>{{ $step->title }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        @php($doneSteps = $run->steps->whereNotNull('checked_at')->sortByDesc('checked_at'))
        @if ($doneSteps->isNotEmpty())
            <div class="card">
                <h2>Zuletzt erledigt</h2>
                <ul>
                    @foreach ($doneSteps->take(8) as $step)
                        <li><span class="dot dot-done"></span><span class="done">{{ $step->title }}</span>
                            <span class="muted"> · {{ $step->checked_at?->format('H:i') }} Uhr</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="foot">
            Aktualisiert sich automatisch · Stand {{ now()->format('d.m.Y H:i:s') }} Uhr<br>
            Vertraulicher Lagestatus — nur für berechtigte Empfänger.
        </div>
    </div>
</body>
</html>
