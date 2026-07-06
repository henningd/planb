<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Übungsbericht · {{ $company->name }}</title>
    @php
        use App\Models\ScenarioRunAcknowledgement;
        use App\Support\Reports\DrillReport;

        $logoDataUri = null;
        $logoPath = public_path('wappen.png');
        // dompdf braucht die GD-Erweiterung um PNG einzubetten — auf Servern
        // ohne php-gd (oder im Test) lassen wir das Logo lieber weg, statt
        // die Generierung scheitern zu lassen.
        if (is_file($logoPath) && extension_loaded('gd')) {
            $logoDataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
        }
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
            border-bottom: 2px solid #1f2937;
            padding-bottom: 6mm;
            margin-bottom: 6mm;
        }
        .header img {
            width: 18mm;
            height: 18mm;
            object-fit: contain;
            float: right;
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
        h2 {
            font-size: 11pt;
            color: #111827;
            margin: 6mm 0 2mm 0;
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
        .kpi-table td {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 2.5mm 2mm;
        }
        .kpi-table .kpi-label {
            font-size: 7.5pt;
            color: #6b7280;
            text-transform: uppercase;
        }
        .kpi-table .kpi-value {
            font-size: 11pt;
            font-weight: bold;
            color: #111827;
        }
        .gaps {
            margin-top: 4mm;
            border: 1px solid #f59e0b;
            background: #fffbeb;
            padding: 3mm 4mm;
        }
        .gaps h3 {
            margin: 0 0 1.5mm 0;
            font-size: 9.5pt;
            color: #92400e;
        }
        .gaps ul { margin: 0; padding-left: 5mm; color: #92400e; }
        .ok {
            margin-top: 4mm;
            border: 1px solid #10b981;
            background: #ecfdf5;
            padding: 3mm 4mm;
            color: #065f46;
        }
        .empty {
            padding: 6mm;
            text-align: center;
            color: #6b7280;
            font-style: italic;
            border: 1px solid #e5e7eb;
        }
        .footer-note {
            margin-top: 6mm;
            font-size: 8pt;
            color: #6b7280;
            text-align: right;
        }
        .badge-open { color: #b45309; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        @if ($logoDataUri)
            <img src="{{ $logoDataUri }}" alt="">
        @endif
        <h1>Übungsbericht</h1>
        <div class="meta">
            <strong>{{ $company->name }}</strong><br>
            Szenario: {{ $run->scenario?->name ?? $run->title }} · Titel: {{ $run->title }}<br>
            Modus: Übung (Drill) ·
            Ausgang: {{ $report->outcomeLabel() }}
            @if ($report->wasEscalated()) · Eskalation ausgelöst: {{ $run->escalated_at?->format('d.m.Y H:i') }} @endif
            <br>
            Start: {{ $run->started_at?->format('d.m.Y H:i') ?? '–' }} ·
            Ende: {{ $report->endedAt()?->format('d.m.Y H:i') ?? '–' }} ·
            Gestartet von: {{ $run->startedBy?->name ?? '–' }}<br>
            Erstellt: {{ $generatedAt->format('d.m.Y H:i') }}
        </div>
    </div>

    <h2>Kennzahlen</h2>
    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-label">Dauer</div>
                <div class="kpi-value">{{ DrillReport::formatDuration($report->durationSeconds()) }}</div>
            </td>
            <td>
                <div class="kpi-label">Erste Quittierung</div>
                <div class="kpi-value">{{ DrillReport::formatDuration($report->secondsToFirstAcknowledgement()) }}</div>
            </td>
            <td>
                <div class="kpi-label">Übernahme nach</div>
                <div class="kpi-value">{{ DrillReport::formatDuration($report->secondsToTakeover()) }}</div>
            </td>
            <td>
                <div class="kpi-label">Schritte erledigt</div>
                <div class="kpi-value">{{ $report->stepsDone() }} / {{ $report->stepsTotal() }}</div>
            </td>
            <td>
                <div class="kpi-label">Eskalation</div>
                <div class="kpi-value">{{ $report->wasEscalated() ? 'Ja' : 'Nein' }}</div>
            </td>
        </tr>
    </table>

    @if (count($report->gaps()) > 0)
        <div class="gaps">
            <h3>Festgestellte Lücken</h3>
            <ul>
                @foreach ($report->gaps() as $gap)
                    <li>{{ $gap }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="ok">
            Keine Lücken festgestellt — alle Schritte erledigt, Alarm quittiert und Verantwortung übernommen.
        </div>
    @endif

    <h2>Schritte ({{ $report->stepsDone() }} von {{ $report->stepsTotal() }} erledigt)</h2>
    @if ($run->steps->isEmpty())
        <div class="empty">Keine Schritte in diesem Durchlauf.</div>
    @else
        <table>
            <colgroup>
                <col style="width: 6%">
                <col style="width: 36%">
                <col style="width: 18%">
                <col style="width: 20%">
                <col style="width: 20%">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Schritt</th>
                    <th>Zuständig</th>
                    <th>Erledigt von</th>
                    <th>Erledigt am</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($run->steps as $step)
                    <tr>
                        <td>{{ $step->sort }}</td>
                        <td>
                            {{ $step->title }}
                            @if ($step->note)
                                <br><em>Notiz: {{ $step->note }}</em>
                            @endif
                        </td>
                        <td>{{ $step->responsible ?: '–' }}</td>
                        <td>
                            @if ($step->checked_at)
                                {{ $step->checkedBy?->name ?? '–' }}
                            @else
                                <span class="badge-open">offen</span>
                            @endif
                        </td>
                        <td>{{ $step->checked_at?->format('d.m.Y H:i') ?? '–' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Alarm-Quittierungen ({{ $report->acknowledgedUserCount() }})</h2>
    @if ($run->acknowledgements->isEmpty())
        <div class="empty">Keine Quittierungen — der Alarm wurde von niemandem bestätigt.</div>
    @else
        <table>
            <colgroup>
                <col style="width: 34%">
                <col style="width: 22%">
                <col style="width: 22%">
                <col style="width: 22%">
            </colgroup>
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Status</th>
                    <th>Zeitpunkt</th>
                    <th>Nach Alarmstart</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($run->acknowledgements as $ack)
                    <tr>
                        <td>{{ $ack->user?->name ?? '–' }}</td>
                        <td>{{ $ack->status === ScenarioRunAcknowledgement::STATUS_TAKING_OVER ? 'Übernommen' : 'Gesehen' }}</td>
                        <td>{{ $ack->acknowledged_at?->format('d.m.Y H:i:s') ?? '–' }}</td>
                        <td>
                            @if ($ack->acknowledged_at && $run->started_at)
                                {{ DrillReport::formatDuration(max(0, (int) $run->started_at->diffInSeconds($ack->acknowledged_at))) }}
                            @else
                                –
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Beteiligte ({{ $report->participantCount() }})</h2>
    @if ($report->participantNames()->isEmpty())
        <div class="empty">Keine Beteiligten erfasst.</div>
    @else
        <div>{{ $report->participantNames()->implode(', ') }}</div>
    @endif

    <div class="footer-note">
        Vertraulich · {{ $company->name }} · Übungsnachweis · Erstellt {{ $generatedAt->format('d.m.Y H:i') }}
    </div>
</body>
</html>
