<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Notfallhandbuch') }} · {{ $company->name }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        .page {
            max-width: 880px;
            margin: 0 auto;
            padding: 2.5rem;
            background: white;
        }
        h1 { font-size: 2rem; margin: 0 0 0.25rem; }
        h2 {
            font-size: 1.5rem;
            margin: 2.5rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
            page-break-after: avoid;
        }
        h3 {
            font-size: 1.1rem;
            margin: 1.25rem 0 0.5rem;
            page-break-after: avoid;
        }
        p { margin: 0.5rem 0; }
        .meta { color: #64748b; font-size: 0.875rem; }
        .cover { padding: 4rem 0 2rem; border-bottom: 2px solid #e2e8f0; }
        .cover .kicker { text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.75rem; color: #6366f1; font-weight: 600; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            page-break-inside: avoid;
        }
        .card .title { font-weight: 600; }
        .card .sub { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
        .badge {
            display: inline-block;
            font-size: 0.75rem;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 500;
        }
        .badge-primary { background: #ecfdf5; color: #047857; }
        .badge-critical { background: #fef2f2; color: #b91c1c; }
        .badge-high { background: #fffbeb; color: #b45309; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        th, td { text-align: left; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; vertical-align: top; }
        th { background: #f8fafc; font-weight: 600; }
        ol { padding-left: 1.25rem; }
        ol li { margin-bottom: 0.25rem; }
        .callout {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 0.75rem 1rem;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        .section-intro { color: #64748b; font-size: 0.95rem; margin-bottom: 1rem; }
        .toolbar {
            position: sticky;
            top: 0;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }
        .toolbar button, .toolbar a {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            border: 1px solid #e2e8f0;
            background: white;
            cursor: pointer;
            text-decoration: none;
            color: #1e293b;
        }
        .toolbar button.primary { background: #4f46e5; color: white; border-color: #4f46e5; }
        footer {
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.8rem;
        }
        .page-break { page-break-before: always; }

        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .page { padding: 0; max-width: none; margin: 0; }
            h1 { font-size: 1.75rem; }
            h2 { font-size: 1.25rem; }
            .cover { padding: 1rem 0 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <strong>{{ __('Notfallhandbuch') }}</strong>
            <span class="meta"> · {{ $company->name }} · {{ now()->format('d.m.Y') }}</span>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="{{ route('dashboard') }}">← {{ __('Zurück') }}</a>
            <button class="primary" onclick="window.print()">{{ __('Als PDF speichern / Drucken') }}</button>
        </div>
    </div>

    <div class="page">
        {{-- Cover --}}
        <section class="cover">
            <div class="kicker">{{ __('Notfall- und Krisenhandbuch') }}</div>
            <h1>{{ $company->name }}</h1>
            <p class="meta">
                {{ $company->industry->label() }}
                @if ($company->employee_count) · {{ $company->employee_count }} {{ __('Mitarbeitende') }} @endif
                @if ($company->locations_count) · {{ $company->locations_count }} {{ __('Standorte') }} @endif
            </p>
            <p class="meta">{{ __('Stand') }}: {{ now()->format('d.m.Y H:i') }}</p>
            <div class="callout">
                {{ __('Dieses Handbuch dokumentiert Rollen, Systeme, Szenarien und Meldepflichten für den Ernstfall. Halten Sie es offline und griffbereit.') }}
            </div>
        </section>

        {{-- Ansprechpartner --}}
        <section>
            <h2>{{ __('1. Ansprechpartner') }}</h2>
            <p class="section-intro">{{ __('Wer entscheidet. Wer kontaktiert wird. Wer kommuniziert.') }}</p>

            @if ($company->contacts->isEmpty())
                <p class="meta">{{ __('Keine Ansprechpartner hinterlegt.') }}</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Rolle') }}</th>
                            <th>{{ __('Telefon') }}</th>
                            <th>{{ __('E-Mail') }}</th>
                            <th>{{ __('Typ') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($company->contacts as $contact)
                            <tr>
                                <td>
                                    <strong>{{ $contact->name }}</strong>
                                    @if ($contact->is_primary) <span class="badge badge-primary">{{ __('Hauptkontakt') }}</span> @endif
                                </td>
                                <td>{{ $contact->role }}</td>
                                <td>{{ $contact->phone }}</td>
                                <td>{{ $contact->email }}</td>
                                <td>{{ $contact->type->label() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        {{-- Notfall-Level --}}
        <section>
            <h2>{{ __('2. Notfall-Level') }}</h2>
            <p class="section-intro">{{ __('Eskalationsstufen und die jeweils vorgesehene Reaktion.') }}</p>
            @foreach ($company->emergencyLevels as $level)
                <div class="card" style="margin-bottom: 0.75rem;">
                    <div class="title">{{ $level->sort }}. {{ $level->name }}</div>
                    @if ($level->description)<div class="sub">{{ $level->description }}</div>@endif
                    @if ($level->reaction)
                        <p style="margin-top: 0.5rem;"><strong>{{ __('Reaktion') }}:</strong> {{ $level->reaction }}</p>
                    @endif
                </div>
            @endforeach
        </section>

        {{-- Systeme --}}
        <section class="page-break">
            <h2>{{ __('3. Systeme & Betriebskontinuität') }}</h2>
            <p class="section-intro">{{ __('Welche Systeme braucht der Betrieb – und in welcher Reihenfolge kommen sie im Ernstfall zurück.') }}</p>

            @foreach (\App\Enums\SystemCategory::cases() as $category)
                @php($systems = $company->systems->where('category', $category))
                <h3>{{ $category->label() }}</h3>
                <p class="meta">{{ $category->description() }}</p>
                @if ($systems->isEmpty())
                    <p class="meta" style="font-style: italic;">{{ __('Kein System in dieser Kategorie.') }}</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('System') }}</th>
                                <th>{{ __('Priorität') }}</th>
                                <th>{{ __('Zielwerte') }}</th>
                                <th>{{ __('Dienstleister') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($systems as $system)
                                <tr>
                                    <td>
                                        <strong>{{ $system->name }}</strong>
                                        @if ($system->description) <div class="sub">{{ $system->description }}</div> @endif
                                    </td>
                                    <td>
                                        @if ($system->priority)
                                            <span class="badge {{ $system->priority->sort === 1 ? 'badge-critical' : ($system->priority->sort === 2 ? 'badge-high' : '') }}">
                                                {{ $system->priority->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($system->rto_minutes)
                                            <div>RTO: {{ \App\Support\Duration::format($system->rto_minutes) }}</div>
                                        @endif
                                        @if ($system->rpo_minutes)
                                            <div>RPO: {{ \App\Support\Duration::format($system->rpo_minutes) }}</div>
                                        @endif
                                        @if ($system->downtime_cost_per_hour)
                                            <div>{{ number_format($system->downtime_cost_per_hour, 0, ',', '.') }} € / h</div>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach ($system->serviceProviders as $p)
                                            <div>{{ $p->name }}@if ($p->hotline) · {{ $p->hotline }}@endif</div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach
        </section>

        {{-- Dienstleister --}}
        <section class="page-break">
            <h2>{{ __('4. Externe Dienstleister') }}</h2>
            <p class="section-intro">{{ __('Diese Daten zählen im Ernstfall – auch wenn E-Mail und VoIP ausgefallen sind.') }}</p>

            @if ($providers->isEmpty())
                <p class="meta">{{ __('Keine Dienstleister hinterlegt.') }}</p>
            @else
                <div class="grid-2">
                    @foreach ($providers as $provider)
                        <div class="card">
                            <div class="title">{{ $provider->name }}</div>
                            @if ($provider->contact_name) <div class="sub">{{ $provider->contact_name }}</div> @endif
                            <div style="margin-top: 0.5rem; font-size: 0.9rem;">
                                @if ($provider->hotline)<div><strong>{{ __('Hotline') }}:</strong> {{ $provider->hotline }}@if ($provider->sla) ({{ $provider->sla }})@endif</div>@endif
                                @if ($provider->email)<div><strong>E-Mail:</strong> {{ $provider->email }}</div>@endif
                                @if ($provider->contract_number)<div><strong>{{ __('Vertrag') }}:</strong> {{ $provider->contract_number }}</div>@endif
                            </div>
                            @if ($provider->systems->isNotEmpty())
                                <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #64748b;">
                                    {{ __('Zuständig für') }}:
                                    {{ $provider->systems->pluck('name')->join(', ') }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Szenarien --}}
        <section class="page-break">
            <h2>{{ __('5. Szenarien & Playbooks') }}</h2>
            <p class="section-intro">{{ __('Schritt-für-Schritt-Ablauf für typische Notfälle.') }}</p>

            @foreach ($company->scenarios as $scenario)
                <h3>{{ $scenario->name }}</h3>
                @if ($scenario->description) <p>{{ $scenario->description }}</p> @endif
                @if ($scenario->trigger)
                    <p class="meta"><strong>{{ __('Auslöser') }}:</strong> {{ $scenario->trigger }}</p>
                @endif
                <ol>
                    @foreach ($scenario->steps as $step)
                        <li>
                            <strong>{{ $step->title }}</strong>
                            @if ($step->description) — {{ $step->description }} @endif
                            @if ($step->responsible) <span class="badge">{{ $step->responsible }}</span> @endif
                        </li>
                    @endforeach
                </ol>
            @endforeach
        </section>

        <footer>
            {{ __('Notfallhandbuch von') }} {{ $company->name }} · {{ __('erstellt mit PlanB') }} · {{ now()->format('d.m.Y H:i') }}
        </footer>
    </div>

    <script>
        // Auto-prompt print if opened with ?print=1
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        }
    </script>
</body>
</html>
