<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ __('Notfallaushang') }} · {{ $location->name }}</title>
    <style>
        :root { color-scheme: light; }
        body {
            font-family: ui-sans-serif, system-ui, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            margin: 0;
            padding: 0;
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
        .toolbar button {
            color: white;
            background: #0f172a;
            border: none;
            padding: 0.45rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font: inherit;
            font-weight: 600;
        }
        .sheet {
            max-width: 800px;
            margin: 0 auto 2rem;
            background: white;
            border: 1px solid #e2e8f0;
        }
        .banner {
            background: #e11d48;
            color: white;
            text-align: center;
            padding: 1.6rem 2rem 1.4rem;
        }
        .banner h1 {
            margin: 0;
            font-size: 3rem;
            letter-spacing: 0.08em;
        }
        .banner p {
            margin: 0.4rem 0 0;
            font-size: 1.1rem;
            color: #ffe4e6;
        }
        .body {
            padding: 2rem 2.5rem 2.5rem;
            text-align: center;
        }
        .location {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0;
        }
        .scenario {
            margin: 0.35rem 0 0;
            font-size: 1.05rem;
            color: #475569;
        }
        .qr {
            margin: 1.8rem auto 0.75rem;
            width: 280px;
        }
        .qr svg { width: 100%; height: auto; display: block; }
        .scan-hint {
            font-size: 1.05rem;
            font-weight: 600;
            margin: 0;
        }
        .steps {
            margin: 1.6rem auto 0;
            max-width: 560px;
            text-align: left;
            padding: 0;
            list-style: none;
            counter-reset: step;
        }
        .steps li {
            counter-increment: step;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            padding: 0.5rem 0;
            font-size: 1rem;
        }
        .steps li::before {
            content: counter(step);
            flex: 0 0 1.7rem;
            height: 1.7rem;
            border-radius: 999px;
            background: #0f172a;
            color: white;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .fallback {
            margin: 1.7rem auto 0;
            max-width: 560px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 1rem;
            font-size: 0.9rem;
            color: #475569;
        }
        .fallback code {
            display: block;
            margin-top: 0.3rem;
            font-size: 0.85rem;
            word-break: break-all;
            color: #0f172a;
        }
        .foot {
            border-top: 1px solid #e2e8f0;
            padding: 0.85rem 2.5rem;
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #94a3b8;
        }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { border: none; max-width: none; margin: 0; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>{{ __('Notfallaushang — ausdrucken und gut sichtbar am Standort anbringen.') }}</span>
        <button onclick="window.print()">{{ __('Drucken') }}</button>
    </div>

    <div class="sheet">
        <div class="banner">
            <h1>{{ __('IM NOTFALL') }}</h1>
            <p>{{ __('Ruhe bewahren — dieser Aushang führt Sie zur richtigen Checkliste.') }}</p>
        </div>

        <div class="body">
            <p class="location">{{ $location->name }}</p>
            @if ($scenario)
                <p class="scenario">{{ __('Szenario') }}: {{ $scenario->name }}</p>
            @else
                <p class="scenario">{{ __('Das passende Szenario wählen Sie nach dem Scan in der App.') }}</p>
            @endif

            <div class="qr">{!! \App\Support\QrCode::svg($payloadJson, 280) !!}</div>
            <p class="scan-hint">{{ __('Mit der PlanB-Notfall-App scannen — funktioniert auch ohne Internet.') }}</p>

            <ol class="steps">
                <li>{{ __('Notfall-App öffnen und „Aushang scannen" wählen (oder das App-Symbol gedrückt halten).') }}</li>
                <li>{{ __('Diesen QR-Code scannen — die Checkliste öffnet sich sofort, auch offline.') }}</li>
                <li>{{ __('Schritte der Reihe nach abarbeiten und abhaken. Die Leitung sieht den Fortschritt live.') }}</li>
            </ol>

            @if ($fallbackUrl)
                <div class="fallback">
                    {{ __('Kein Smartphone mit App zur Hand? Notfallhandbuch im Browser öffnen:') }}
                    <code>{{ $fallbackUrl }}</code>
                </div>
            @endif
        </div>

        <div class="foot">
            <span>PlanB · {{ __('Notfallhandbuch') }}</span>
            <span>{{ __('Stand') }}: {{ now()->format('d.m.Y') }}</span>
        </div>
    </div>
</body>
</html>
