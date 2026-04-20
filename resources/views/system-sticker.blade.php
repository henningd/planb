<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ __('Notfall-Aushang') }} · {{ $system->name }}</title>
    <style>
        :root { color-scheme: light; }
        body {
            font-family: ui-sans-serif, system-ui, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .sheet {
            max-width: 800px;
            margin: 0 auto;
            padding: 2.5rem;
            background: white;
            min-height: 100vh;
        }
        .toolbar {
            max-width: 800px;
            margin: 0 auto;
            padding: 1rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: #64748b;
        }
        .toolbar a,
        .toolbar button {
            color: #0f172a;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            background: white;
            padding: 0.35rem 0.75rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font: inherit;
        }
        .toolbar button.primary {
            background: #0f172a;
            color: white;
            border-color: #0f172a;
        }

        .kicker {
            font-size: 0.875rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin: 0 0 0.25rem;
        }
        h1 {
            font-size: 2.5rem;
            margin: 0 0 0.25rem;
            line-height: 1.1;
        }
        .meta {
            color: #475569;
            margin: 0 0 1.5rem;
        }
        .callout {
            background: #fef3c7;
            border: 1px solid #fde68a;
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            color: #713f12;
            font-weight: 500;
            margin: 1.5rem 0 2rem;
        }
        .qr-block {
            display: flex;
            align-items: flex-start;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .qr {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            border-radius: 0.5rem;
        }
        .qr svg { display: block; }
        .qr-hint {
            font-size: 0.875rem;
            color: #475569;
            max-width: 320px;
        }

        h2 {
            font-size: 1.1rem;
            margin: 1.75rem 0 0.5rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.35rem;
        }
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        td.label { color: #64748b; width: 12rem; }
        .hotline { font-weight: 600; }
        footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 0.75rem;
        }

        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { padding: 0.75rem; max-width: none; margin: 0; min-height: 0; }
            h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>{{ __('Notfall-Aushang') }} · {{ $system->name }}</div>
        <div style="display:flex; gap:0.5rem;">
            <a href="{{ route('systems.index') }}">← {{ __('Zurück') }}</a>
            <button class="primary" onclick="window.print()">{{ __('Drucken') }}</button>
        </div>
    </div>

    <div class="sheet">
        <div class="kicker">{{ __('Notfall-Aushang') }}</div>
        <h1>{{ $system->name }}</h1>
        <p class="meta">
            {{ $system->category->label() }}
            @if ($system->priority) · {{ __('Priorität') }}: {{ $system->priority->name }} @endif
        </p>

        <div class="callout">
            {{ __('Bei Ausfall dieses Systems bitte zuerst den hier aufgeführten Dienstleister kontaktieren. Diesen Aushang am Serverraum / Systemstandort anbringen.') }}
        </div>

        <div class="qr-block">
            <div class="qr">
                {!! \App\Support\QrCode::svg($url, 220) !!}
            </div>
            <div class="qr-hint">
                <strong>{{ __('Mit dem Smartphone scannen') }}</strong>
                <p style="margin: 0.35rem 0 0;">
                    {{ __('Öffnet direkt diese Info-Seite im Firmen-Login. So haben Sie im Ernstfall alle Details zu Verantwortlichen und Wiederanlauf griffbereit.') }}
                </p>
            </div>
        </div>

        <h2>{{ __('Kenngrößen') }}</h2>
        <table>
            @if ($system->rto_minutes)
                <tr>
                    <td class="label">{{ __('Max. Ausfallzeit (RTO)') }}</td>
                    <td>{{ \App\Support\Duration::format($system->rto_minutes) }}</td>
                </tr>
            @endif
            @if ($system->rpo_minutes)
                <tr>
                    <td class="label">{{ __('Max. Datenverlust (RPO)') }}</td>
                    <td>{{ \App\Support\Duration::format($system->rpo_minutes) }}</td>
                </tr>
            @endif
            @if ($system->downtime_cost_per_hour)
                <tr>
                    <td class="label">{{ __('Ausfallkosten') }}</td>
                    <td>{{ number_format($system->downtime_cost_per_hour, 0, ',', '.') }} € / {{ __('Stunde') }}</td>
                </tr>
            @endif
            @if ($system->description)
                <tr>
                    <td class="label">{{ __('Beschreibung') }}</td>
                    <td>{{ $system->description }}</td>
                </tr>
            @endif
        </table>

        @if ($system->serviceProviders->isNotEmpty())
            <h2>{{ __('Dienstleister') }}</h2>
            <table>
                @foreach ($system->serviceProviders as $provider)
                    <tr>
                        <td style="width: 40%;">
                            <strong>{{ $provider->name }}</strong>
                            @if ($provider->contact_name) <div style="color:#64748b;">{{ $provider->contact_name }}</div>@endif
                        </td>
                        <td>
                            @if ($provider->hotline)<div class="hotline">☎ {{ $provider->hotline }}</div>@endif
                            @if ($provider->email)<div style="color:#475569;">{{ $provider->email }}</div>@endif
                            @if ($provider->contract_number)<div style="color:#94a3b8; font-size:0.85rem;">{{ __('Vertrag') }}: {{ $provider->contract_number }}</div>@endif
                            @if ($provider->sla)<div style="color:#94a3b8; font-size:0.85rem;">{{ __('SLA') }}: {{ $provider->sla }}</div>@endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if ($system->dependencies->isNotEmpty())
            <h2>{{ __('Abhängigkeiten') }}</h2>
            <p style="margin: 0.25rem 0 0.75rem; color: #475569; font-size: 0.9rem;">
                {{ __('Diese Systeme müssen zuerst laufen:') }}
            </p>
            <table>
                @foreach ($system->dependencies as $dep)
                    <tr>
                        <td>{{ $dep->name }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <footer>
            {{ __('Stand') }}: {{ now()->format('d.m.Y') }} · {{ __('Erstellt mit PlanB') }}
        </footer>
    </div>

    <script>
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        }
    </script>
</body>
</html>
