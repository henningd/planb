<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Aktivitätsprotokoll · {{ $company->name }}</title>
    @php
        $logoDataUri = null;
        $logoPath = public_path('wappen.png');
        if (is_file($logoPath)) {
            $logoDataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));
        $rangeLabel = match (true) {
            $from !== '' && $to !== '' => $from.' – '.$to,
            $from !== '' => 'ab '.$from,
            $to !== '' => 'bis '.$to,
            default => 'Gesamter Zeitraum',
        };

        $entityTypeFilter = trim((string) ($filters['entity_type'] ?? ''));
        $actionFilter = trim((string) ($filters['action'] ?? ''));
        $searchFilter = trim((string) ($filters['search'] ?? ''));
    @endphp
    <style>
        @page { size: A4 landscape; margin: 14mm 12mm 14mm 12mm; }
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
        .filter-bar {
            font-size: 8.5pt;
            color: #4b5563;
            margin-bottom: 4mm;
        }
        .filter-bar span.tag {
            display: inline-block;
            padding: 0.5mm 1.5mm;
            background: #f3f4f6;
            border-radius: 1mm;
            margin-right: 1.5mm;
        }
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
        col.col-date { width: 12%; }
        col.col-user { width: 13%; }
        col.col-action { width: 10%; }
        col.col-entity-type { width: 11%; }
        col.col-entity { width: 18%; }
        col.col-changes { width: 36%; }
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
            <h1>Aktivitätsprotokoll</h1>
            <div class="meta">
                <strong>{{ $company->name }}</strong><br>
                Zeitraum: {{ $rangeLabel }} ·
                Erstellt: {{ $generatedAt->format('d.m.Y H:i') }} ·
                Einträge: {{ $entries->count() }}
            </div>
        </div>
    </div>

    @if ($entityTypeFilter !== '' || $actionFilter !== '' || $searchFilter !== '')
        <div class="filter-bar">
            Aktive Filter:
            @if ($entityTypeFilter !== '')
                <span class="tag">Objekt-Typ: {{ $entityTypeFilter }}</span>
            @endif
            @if ($actionFilter !== '')
                <span class="tag">Aktion: {{ $actionFilter }}</span>
            @endif
            @if ($searchFilter !== '')
                <span class="tag">Suche: "{{ $searchFilter }}"</span>
            @endif
        </div>
    @endif

    @if ($entries->isEmpty())
        <div class="empty">Keine Einträge im gewählten Zeitraum.</div>
    @else
        <table>
            <colgroup>
                <col class="col-date">
                <col class="col-user">
                <col class="col-action">
                <col class="col-entity-type">
                <col class="col-entity">
                <col class="col-changes">
            </colgroup>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($entries as $entry)
                    @php($row = app(\App\Http\Controllers\AuditLogExportController::class)->rowFor($entry))
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-note">Vertraulich · {{ $company->name }} · {{ $generatedAt->format('d.m.Y H:i') }}</div>
</body>
</html>
