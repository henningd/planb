<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Notfallkarte · {{ $company->name }}</title>
    @php
        /**
         * RTO-Minuten in eine kompakte „Xh Ym"/„Ym"-Darstellung wandeln.
         */
        $formatRto = function (?int $minutes): string {
            if ($minutes === null) {
                return '—';
            }
            if ($minutes < 60) {
                return $minutes.'m';
            }
            $hours = intdiv($minutes, 60);
            $rest = $minutes % 60;

            return $hours.'h '.$rest.'m';
        };

        /**
         * Vorhandene Telefonnummern einer Person als String zusammenstellen.
         */
        $phones = function ($employee): string {
            if ($employee === null) {
                return '';
            }
            $parts = [];
            if (! empty($employee->mobile_phone)) {
                $parts[] = 'Mobil '.$employee->mobile_phone;
            }
            if (! empty($employee->work_phone)) {
                $parts[] = 'Festnetz '.$employee->work_phone;
            }

            return implode(' · ', $parts);
        };
    @endphp
    <style>
        @page { size: A4 portrait; margin: 12mm 12mm 12mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            font-size: 9pt;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 3px solid #111827;
            padding-bottom: 3mm;
            margin-bottom: 4mm;
        }
        .header h1 {
            font-size: 22pt;
            letter-spacing: 1px;
            margin: 0;
            color: #111827;
        }
        .header .meta {
            font-size: 9pt;
            color: #374151;
            margin-top: 1mm;
        }
        .header .meta strong { color: #111827; }
        .section { margin-bottom: 4mm; }
        .section h2 {
            font-size: 11pt;
            margin: 0 0 1.5mm 0;
            padding: 1mm 2mm;
            background: #111827;
            color: #ffffff;
        }
        .staff-item {
            padding: 1.2mm 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .staff-item .role {
            font-weight: bold;
            color: #111827;
        }
        .staff-item .person { color: #111827; }
        .staff-item .deputy {
            color: #4b5563;
            font-size: 8pt;
            padding-left: 4mm;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        thead th {
            background: #374151;
            color: #ffffff;
            text-align: left;
            font-weight: bold;
            font-size: 8.5pt;
            padding: 1.5mm 2mm;
            border: 1px solid #374151;
        }
        tbody td {
            padding: 1.2mm 2mm;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 8.5pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        tbody tr:nth-child(even) td { background: #f9fafb; }
        .empty {
            color: #6b7280;
            font-style: italic;
            font-size: 8.5pt;
            padding: 1mm 0;
        }
        .footer-note {
            margin-top: 5mm;
            padding-top: 2mm;
            border-top: 1px solid #d1d5db;
            font-size: 8pt;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>NOTFALLKARTE</h1>
        <div class="meta">
            <strong>{{ $company->name }}</strong> ·
            Stand: {{ $generatedAt->format('d.m.Y H:i') }}
        </div>
    </div>

    <div class="section">
        <h2>Krisenstab</h2>
        @php($hasStaff = collect($crisisStaff)->contains(fn ($item) => $item['main'] !== null || $item['deputies']->isNotEmpty()))
        @if (! $hasStaff)
            <div class="empty">— keine Einträge —</div>
        @else
            @foreach ($crisisStaff as $item)
                @if ($item['main'] !== null || $item['deputies']->isNotEmpty())
                    <div class="staff-item">
                        <span class="role">{{ $item['role_label'] }}:</span>
                        @if ($item['main'] !== null)
                            <span class="person">
                                {{ $item['main']->fullName() }}@if (! empty($item['main']->position)) ({{ $item['main']->position }})@endif
                                @php($mainPhones = $phones($item['main']))
                                @if ($mainPhones !== '') — {{ $mainPhones }}@endif
                            </span>
                        @else
                            <span class="empty">— nicht besetzt —</span>
                        @endif
                        @foreach ($item['deputies'] as $deputy)
                            <div class="deputy">
                                Vertretung: {{ $deputy->fullName() }}@if (! empty($deputy->position)) ({{ $deputy->position }})@endif
                                @php($depPhones = $phones($deputy))
                                @if ($depPhones !== '') — {{ $depPhones }}@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endif
    </div>

    <div class="section">
        <h2>Wiederanlauf-Reihenfolge</h2>
        @if (empty($recoveryOrder))
            <div class="empty">— keine Einträge —</div>
        @else
            <table>
                <colgroup>
                    <col style="width: 55%;">
                    <col style="width: 30%;">
                    <col style="width: 15%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>System</th>
                        <th>Notfall-Level</th>
                        <th>RTO</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recoveryOrder as $row)
                        <tr>
                            <td>{{ $row['system']->name }}</td>
                            <td>{{ $row['level_name'] ?? '—' }}</td>
                            <td>{{ $formatRto($row['rto_minutes']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h2>Dienstleister-Hotlines</h2>
        @if ($serviceProviders->isEmpty())
            <div class="empty">— keine Einträge —</div>
        @else
            <table>
                <colgroup>
                    <col style="width: 45%;">
                    <col style="width: 30%;">
                    <col style="width: 25%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Typ</th>
                        <th>Hotline</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($serviceProviders as $provider)
                        <tr>
                            <td>{{ $provider->name }}</td>
                            <td>{{ $provider->type?->label() ?? '—' }}</td>
                            <td>{{ $provider->hotline }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer-note">
        Stand: {{ $generatedAt->format('d.m.Y H:i') }} — bitte ausdrucken und offline griffbereit halten.
    </div>
</body>
</html>
