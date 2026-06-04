<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Krisenprotokoll · {{ $company->name }}</title>
    @php
        $logoDataUri = null;
        $logoPath = public_path('wappen.png');
        // dompdf braucht die GD-Erweiterung um PNG einzubetten — auf Servern
        // ohne php-gd (oder im Test) lassen wir das Logo lieber weg, statt
        // die Generierung scheitern zu lassen.
        if (is_file($logoPath) && extension_loaded('gd')) {
            $logoDataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        $typeLabel = static fn (string $type): string => match ($type) {
            'note' => 'Notiz',
            'decision' => 'Entscheidung',
            'action' => 'Maßnahme',
            'step' => 'Schritt',
            'alert' => 'Alarmierung',
            'system' => 'System',
            default => $type,
        };
    @endphp
    <style>
        @page { size: A4 portrait; margin: 14mm 12mm 14mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1a1a1a;
            font-size: 9pt;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 6mm;
            margin-bottom: 6mm;
        }
        .header img {
            width: 18mm;
            height: 18mm;
            margin-right: 6mm;
            object-fit: contain;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0 0 1mm 0;
            color: #111827;
        }
        .header .meta {
            font-size: 9pt;
            color: #4b5563;
        }
        .meta strong { color: #111827; }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        thead th {
            background: #1f2937;
            color: #ffffff;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            padding: 2mm 2mm;
            border: 1px solid #1f2937;
        }
        tbody td {
            padding: 1.5mm 2mm;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 8.5pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        tbody tr:nth-child(even) td { background: #f9fafb; }
        col.col-time { width: 18%; }
        col.col-user { width: 20%; }
        col.col-type { width: 16%; }
        col.col-message { width: 46%; }
        .empty {
            padding: 12mm;
            text-align: center;
            color: #6b7280;
            font-style: italic;
        }
        .footer-note {
            margin-top: 6mm;
            font-size: 8pt;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        @if ($logoDataUri)
            <img src="{{ $logoDataUri }}" alt="">
        @endif
        <div>
            <h1>Krisenprotokoll</h1>
            <div class="meta">
                <strong>{{ $company->name }}</strong><br>
                Szenario: {{ $scenarioName }}<br>
                Start: {{ $run->started_at?->format('d.m.Y H:i') ?? '–' }} ·
                Ende: {{ $run->ended_at?->format('d.m.Y H:i') ?? 'läuft' }}<br>
                Erstellt: {{ $generatedAt->format('d.m.Y H:i') }} ·
                Einträge: {{ $entries->count() }}
            </div>
        </div>
    </div>

    @if ($entries->isEmpty())
        <div class="empty">Keine Logbuch-Einträge für diesen Lauf.</div>
    @else
        <table>
            <colgroup>
                <col class="col-time">
                <col class="col-user">
                <col class="col-type">
                <col class="col-message">
            </colgroup>
            <thead>
                <tr>
                    <th>Zeitpunkt</th>
                    <th>Benutzer</th>
                    <th>Typ</th>
                    <th>Eintrag</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entries as $entry)
                    <tr>
                        <td>{{ $entry->occurred_at?->format('d.m.Y H:i') ?? '' }}</td>
                        <td>{{ $entry->user?->name ?? '—' }}</td>
                        <td>{{ $typeLabel((string) $entry->type) }}</td>
                        <td>{{ $entry->message }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-note">Vertraulich · {{ $company->name }} · {{ $generatedAt->format('d.m.Y H:i') }}</div>
</body>
</html>
