<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Notfall-App — Zugänge</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #18181b; font-size: 12px; line-height: 1.5; margin: 0; }
        .sheet { padding: 40px 48px; page-break-after: always; }
        .sheet:last-child { page-break-after: avoid; }
        .kicker { font-size: 11px; color: #71717a; }
        h1 { font-size: 22px; margin: 2px 0 0; }
        .person { margin-top: 18px; padding: 12px 16px; border: 1px solid #e4e4e7; border-radius: 10px; }
        .person .name { font-size: 16px; font-weight: bold; }
        .person .email { color: #52525b; font-size: 12px; }
        .qr-wrap { margin-top: 20px; text-align: center; }
        .qr-wrap img { width: 220px; height: 220px; }
        .code { margin-top: 14px; text-align: center; font-family: DejaVu Sans Mono, monospace; font-size: 26px; font-weight: bold; letter-spacing: 6px; }
        .validity { margin-top: 6px; text-align: center; color: #71717a; font-size: 11px; }
        h2 { font-size: 14px; margin: 26px 0 8px; border-bottom: 1px solid #e4e4e7; padding-bottom: 4px; }
        ol { margin: 0; padding-left: 20px; }
        ol li { margin-bottom: 8px; }
        .hint { margin-top: 18px; padding: 10px 12px; background: #fafafa; border-left: 3px solid #a1a1aa; border-radius: 4px; font-size: 11px; color: #52525b; }
    </style>
</head>
<body>
@foreach ($entries as $entry)
    <div class="sheet">
        <div class="kicker">{{ config('app.name', 'PlanB') }} · {{ $company->name }}</div>
        <h1>Ihr Zugang zur Notfall-App</h1>

        <div class="person">
            <div class="name">{{ $entry['name'] }}</div>
            <div class="email">{{ $entry['email'] }}</div>
        </div>

        <div class="qr-wrap">
            <img src="{{ $entry['qr'] }}" alt="Onboarding-QR-Code">
        </div>

        <div class="code">{{ implode('-', str_split($entry['code'], 4)) }}</div>
        <div class="validity">Gültig bis {{ $expiresAt->format('d.m.Y') }} · nur einmal einlösbar</div>

        <h2>So richten Sie die App ein</h2>
        <ol>
            <li><strong>App installieren:</strong> Suchen Sie im App Store (iPhone) bzw. Google Play Store (Android) nach „PlanB Notfall-App“ und installieren Sie sie.</li>
            <li><strong>QR-Code scannen:</strong> Öffnen Sie die App, tippen Sie auf „QR scannen“ und richten Sie die Kamera auf den Code oben.</li>
            <li><strong>Oder manuell eingeben:</strong> Tippen Sie auf „Manuell einrichten“ und geben Sie Ihre E-Mail-Adresse <strong>{{ $entry['email'] }}</strong> und den Code <strong>{{ implode('-', str_split($entry['code'], 4)) }}</strong> ein.</li>
        </ol>

        <div class="hint">
            Dieses Blatt ist persönlich und nur für Sie bestimmt. Der Code funktioniert ausschließlich mit Ihrer E-Mail-Adresse,
            kann nur einmal verwendet werden und verfällt am {{ $expiresAt->format('d.m.Y') }}. Nach der Einrichtung können Sie das Blatt entsorgen.
        </div>
    </div>
@endforeach
</body>
</html>
