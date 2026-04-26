<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notfall- und Krisenhandbuch · {{ $company->name }}</title>
    @php
        $currentVersion = $company->currentHandbookVersion();
        $latestVersion = $company->handbookVersions->first();
        $versionString = $currentVersion?->version ?? $latestVersion?->version ?? '1.0';
        $companySlug = strtoupper(\Illuminate\Support\Str::slug($company->name, ''));
        $companyInitials = strtoupper(collect(preg_split('/\s+/', $company->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(3)->implode(''));
        $aktenzeichen = sprintf('Az.: NHB-%s-%s-%s', $companyInitials !== '' ? $companyInitials : 'X', now()->format('Y'), $versionString);
        $hq = $company->locations->firstWhere('is_headquarters', true) ?? $company->locations->first();
        $approver = $currentVersion?->approvedBy?->fullName() ?? $currentVersion?->approved_by_name;
        $crisisHolders = $company->employees
            ->filter(fn ($e) => $e->crisis_role !== null)
            ->sortBy(fn ($e) => sprintf('%d-%d', match ($e->crisis_role->value) {
                'management' => 1,
                'emergency_officer' => 2,
                'it_lead' => 3,
                'dpo' => 4,
                'communications_lead' => 5,
                default => 9,
            }, $e->is_crisis_deputy ? 1 : 0));
        $authorities = $providers->filter(fn ($p) => $p->type?->isAuthority() ?? false);
        $externalProviders = $providers->reject(fn ($p) => $p->type?->isAuthority() ?? false);
        $emblemUrl = asset('wappen.png');
        $pageTopCenter = sprintf('%s — Notfall- und Krisenhandbuch', $company->name);

        // Für die @page Margin-Box brauchen wir die PNG mit fester Pixelgröße,
        // sonst gibt Chrome die volle Bildauflösung in den 30mm-Header und zerschießt
        // das Layout ab Seite 2. Wir betten daher die PNG in eine SVG-Hülle ein,
        // die explizit 64×64 Pixel groß ist – die Margin-Box kann damit zuverlässig skalieren.
        $emblemHeaderDataUri = null;
        $emblemPath = public_path('wappen.png');
        if (is_file($emblemPath)) {
            $b64Png = base64_encode(file_get_contents($emblemPath));
            $svgWrapper = '<?xml version="1.0" encoding="UTF-8"?>'
                .'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" '
                .'width="64" height="64" viewBox="0 0 64 64">'
                .'<image x="0" y="0" width="64" height="64" preserveAspectRatio="xMidYMid meet" '
                .'xlink:href="data:image/png;base64,'.$b64Png.'"/>'
                .'</svg>';
            $emblemHeaderDataUri = 'data:image/svg+xml;base64,'.base64_encode($svgWrapper);
        }
    @endphp
    <style>
        /* ======== PDF / DRUCK: Pro-Seite-Header via @page Margin-Boxes ======== */
        @page {
            size: A4;
            margin: 30mm 20mm 22mm 22mm;

            @top-left {
                @if ($emblemHeaderDataUri)
                    content: url('{{ $emblemHeaderDataUri }}');
                @endif
                vertical-align: bottom;
                padding-bottom: 1mm;
            }
            @top-center {
                content: '{{ $pageTopCenter }}';
                font-family: Arial, Helvetica, sans-serif;
                font-size: 9pt;
                color: #555;
                letter-spacing: 0.02em;
                vertical-align: bottom;
                padding-bottom: 2mm;
            }
            @top-right {
                content: '{{ $aktenzeichen }}';
                font-family: Arial, Helvetica, sans-serif;
                font-size: 9pt;
                font-weight: bold;
                color: #555;
                letter-spacing: 0.05em;
                vertical-align: bottom;
                padding-bottom: 2mm;
            }
            @bottom-left {
                content: '{{ $company->name }} · Vertraulich{{ ($showPdfHashFooter ?? false) ? ' · Revisionsdokument' : '' }}';
                font-family: Arial, Helvetica, sans-serif;
                font-size: 8.5pt;
                color: #888;
                vertical-align: top;
                padding-top: 2mm;
            }
            @bottom-right {
                content: 'Seite ' counter(page) ' von ' counter(pages);
                font-family: Arial, Helvetica, sans-serif;
                font-size: 8.5pt;
                color: #888;
                vertical-align: top;
                padding-top: 2mm;
            }
        }

        /* Deckblatt: weder Margin-Header noch Footer, das große Wappen steht im Inhalt. */
        @page :first {
            @top-left { content: none; }
            @top-center { content: none; }
            @top-right { content: none; }
            @bottom-left { content: none; }
            @bottom-right { content: none; }
        }
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1a1a1a;
            background: #f3f3f3;
            font-size: 11pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
        }
        .sheet {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto 8mm;
            padding: 25mm 22mm 22mm 22mm;
            background: white;
            border: 1px solid #c9c9c9;
        }
        .cover-block {
            margin: 18mm 0 14mm;
            text-align: left;
        }
        .cover-block .kicker {
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #444;
            font-weight: bold;
        }
        h1 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 22pt;
            font-weight: bold;
            margin: 4mm 0 2mm;
            line-height: 1.15;
            text-align: left;
            hyphens: none;
        }
        h2 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14pt;
            font-weight: bold;
            margin: 12mm 0 3mm;
            padding: 0 0 1.5mm;
            border-bottom: 1.5px solid #1a1a1a;
            page-break-after: avoid;
            text-align: left;
            hyphens: none;
        }
        h3 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11.5pt;
            font-weight: bold;
            margin: 7mm 0 2mm;
            page-break-after: avoid;
            text-align: left;
            hyphens: none;
        }
        h4 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            font-weight: bold;
            margin: 5mm 0 1mm;
            text-align: left;
            hyphens: none;
        }
        p { margin: 0 0 2.5mm; }
        ul, ol { margin: 0 0 3mm; padding-left: 5mm; }
        li { margin-bottom: 1mm; }
        em, i { font-style: italic; }
        .legal { font-style: italic; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0 4mm;
            font-size: 10pt;
            page-break-inside: avoid;
            text-align: left;
            hyphens: none;
        }
        th, td {
            border: 0.5pt solid #888;
            padding: 1.5mm 2mm;
            vertical-align: top;
            text-align: left;
        }
        th {
            background: #ececec;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 0.04em;
        }
        td.label { width: 38%; background: #fafafa; font-weight: bold; }
        .meta-table th { width: 38%; background: #fafafa; font-weight: bold; text-transform: none; font-size: 10pt; letter-spacing: 0; }
        .meta-table td, .meta-table th { vertical-align: top; }

        /* Krisenrollen-Tabelle (4.1): kompakte 3-Spalten mit gestackter Erreichbarkeit. */
        .role-table td { vertical-align: top; }
        .role-table .role-function {
            font-style: italic;
            color: #555;
            font-size: 9pt;
            margin-top: 0.5mm;
        }
        .role-table .contact-label {
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-size: 7.5pt;
            color: #777;
            font-weight: bold;
            line-height: 1.2;
        }
        .role-table .contact-label-spaced { margin-top: 1.5mm; }
        .role-table .contact-value {
            display: block;
            font-size: 10pt;
            line-height: 1.3;
            margin-top: 0.3mm;
        }
        .role-table .contact-email {
            word-break: break-all;
            hyphens: none;
        }
        .role-table .contact-note {
            font-size: 9pt;
            color: #555;
            line-height: 1.3;
            margin-top: 0.3mm;
        }

        /* Geltungsbereich-Tabelle (3.4): Bullet-Listen in beiden Spalten. */
        .scope-table td { vertical-align: top; padding: 2mm 3mm; }
        .scope-table ul {
            list-style: disc outside;
            margin: 0;
            padding-left: 4.5mm;
            font-size: 10pt;
            line-height: 1.5;
            text-align: left;
            hyphens: none;
        }
        .scope-table li { margin-bottom: 1mm; }

        /* Versionshistorie: zweizeilige Anordnung pro Eintrag.
           Erste Zeile: strukturierte Felder. Zweite Zeile: Änderungsgrund über die volle Breite. */
        .version-history .version-group { page-break-inside: avoid; }
        .version-history .version-head td { border-bottom: 0; }
        .version-history .version-reason td {
            border-top: 0;
            background: #fafafa;
            padding: 1.5mm 3mm 2.5mm 3mm;
            font-size: 9.5pt;
            line-height: 1.5;
            text-align: justify;
            hyphens: auto;
        }
        .version-history .version-reason .reason-label {
            display: inline-block;
            font-style: italic;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 8pt;
            margin-right: 1mm;
        }
        .toc-table td { border: 0; padding: 0.7mm 0; }
        .toc-table td.num { width: 10mm; }
        .toc-table td.dots { border-bottom: 1px dotted #888; }
        .signature {
            margin-top: 12mm;
            font-size: 10pt;
        }
        .signature-line {
            border-bottom: 0.5pt solid #1a1a1a;
            margin: 12mm 0 1mm;
            height: 6mm;
        }
        .vermerk {
            border: 0.5pt solid #1a1a1a;
            padding: 3mm 4mm;
            margin: 6mm 0;
            font-size: 10pt;
            background: #fafafa;
            page-break-inside: avoid;
        }
        .vermerk-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 0.05em;
            margin-bottom: 1mm;
        }
        .footer-note {
            margin-top: 12mm;
            padding-top: 3mm;
            border-top: 0.5pt solid #888;
            font-size: 8.5pt;
            color: #555;
            text-align: center;
        }
        .page-break { page-break-before: always; }
        .toolbar {
            position: sticky;
            top: 0;
            background: white;
            border-bottom: 1px solid #ccc;
            padding: 2.5mm 8mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
        }
        .toolbar button, .toolbar a {
            padding: 1.5mm 4mm;
            font-size: 10pt;
            border: 1px solid #888;
            background: white;
            cursor: pointer;
            text-decoration: none;
            color: #1a1a1a;
            font-family: Arial, Helvetica, sans-serif;
        }
        .toolbar button.primary { background: #1a1a1a; color: white; border-color: #1a1a1a; }
        .share-banner {
            border: 1px solid #888;
            padding: 3mm 4mm;
            margin: 4mm auto;
            max-width: 210mm;
            font-size: 10pt;
            background: #fafafa;
        }
        .small { font-size: 9pt; color: #444; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .keep { page-break-inside: avoid; }
        .step-num { font-weight: bold; }
        .vertraulich-stempel {
            border: 1.5pt solid #1a1a1a;
            display: inline-block;
            padding: 2mm 5mm;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-size: 9pt;
            margin-top: 8mm;
        }
        .emblem-cover {
            display: block;
            margin: 0 auto 6mm;
            width: 55mm;
            height: auto;
        }
        .emblem-mark {
            width: 13mm;
            height: auto;
            flex-shrink: 0;
        }
        /* Bildschirm-Header oberhalb jeder Sheet (Bildschirm-Ansicht).
           Im Druck unsichtbar — dort übernimmt die @page Margin-Box. */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6mm;
            font-size: 9pt;
            color: #555;
            background: #f3f3f3;
            border: 0;
            margin: -25mm -22mm 8mm -22mm;
            padding: 6mm 22mm;
        }
        .doc-header .org { flex: 1; }
        .doc-header .ref { font-weight: bold; letter-spacing: 0.05em; text-align: right; color: #1a1a1a; }

        @media print {
            body { background: white; }
            .toolbar, .share-banner { display: none; }
            /* Inline-Header entfällt im Print — der @page Margin-Header tritt an seine Stelle. */
            .doc-header { display: none; }
            .sheet {
                box-shadow: none;
                border: 0;
                margin: 0;
                padding: 0;
                min-height: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <strong>Notfall- und Krisenhandbuch</strong>
            <span class="small"> · {{ $company->name }} · {{ $aktenzeichen }} · Stand {{ now()->format('d.m.Y') }}</span>
            @if (! empty($share))
                <span class="small"> · Read-only-Freigabe für „{{ $share->label }}"</span>
            @endif
        </div>
        <div style="display:flex; gap:2mm;">
            @if (empty($share))
                <a href="{{ route('dashboard') }}">&larr; Zur&uuml;ck</a>
            @endif
            <button class="primary" onclick="window.print()">Als PDF speichern / Drucken</button>
        </div>
    </div>

    @if (! empty($share))
        <div class="share-banner">
            <strong>Read-only-Zugriff ohne Login.</strong>
            Freigegeben für: {{ $share->label }}.
            Gültig bis: {{ $share->expires_at->format('d.m.Y H:i') }} Uhr.
        </div>
    @endif

    {{-- ============ DECKBLATT ============ --}}
    <div class="sheet cover-sheet">
        <img class="emblem-cover" src="{{ $emblemUrl }}" alt="">

        <div class="cover-block" style="text-align:center;">
            <div class="kicker">Notfall- und Krisenhandbuch</div>
            <h1 style="text-align:center;">{{ $company->name }}</h1>
            <p class="small" style="margin-top:1mm;">
                Geschäftskontinuität und IT-Notfallmanagement<br>
                <em>gemäß BSI-Standard 200-4 sowie BSI IT-Grundschutz (BSI-Standard 200-2)</em>
            </p>
        </div>

        <table class="meta-table">
            <tr>
                <th>Verantwortlicher / Organisation</th>
                <td>
                    {{ $company->name }}@if ($company->legal_form), {{ $company->legal_form->label() }} @endif<br>
                    Branche: {{ $company->industry->label() }} @if ($company->employee_count) · {{ $company->employee_count }} Mitarbeitende @endif @if ($company->locations_count) · {{ $company->locations_count }} Standorte @endif
                </td>
            </tr>
            @if ($company->locations->isNotEmpty())
                <tr>
                    <th>Standort-Adressen</th>
                    <td>
                        @foreach ($company->locations as $loc)
                            {{ $loc->name }}: {{ $loc->street }}, {{ $loc->postal_code }} {{ $loc->city }}@if ($loc->is_headquarters) (Hauptsitz)@endif{{ ! $loop->last ? '; ' : '' }}
                        @endforeach
                    </td>
                </tr>
            @endif
            <tr>
                <th>Aktenzeichen</th>
                <td class="nowrap"><strong>{{ $aktenzeichen }}</strong></td>
            </tr>
            <tr>
                <th>Version</th>
                <td>
                    {{ $versionString }}
                    @if ($currentVersion && $currentVersion->approved_at)
                        <span class="small">(freigegeben am {{ $currentVersion->approved_at->format('d.m.Y') }}@if ($approver) durch {{ $approver }}@endif)</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Geltung ab</th>
                <td>{{ $company->valid_from?->format('d.m.Y') ?? '—' }}</td>
            </tr>
            <tr>
                <th>Letzte Prüfung</th>
                <td>{{ $company->last_reviewed_at?->format('d.m.Y') ?? '—' }}</td>
            </tr>
            <tr>
                <th>Nächste Prüfung</th>
                <td>{{ $company->reviewDueAt()?->format('d.m.Y') ?? '—' }}</td>
            </tr>
            <tr>
                <th>Stand</th>
                <td>{{ now()->format('d.m.Y H:i') }} Uhr</td>
            </tr>
        </table>

        <p style="margin-top:8mm;" class="legal">
            Dieses Dokument ist vertraulich und ausschließlich für den internen Gebrauch innerhalb der vorgenannten Organisation bestimmt. Eine Weitergabe an Dritte bedarf der ausdrücklichen Zustimmung der Geschäftsführung.
        </p>

        <div class="vertraulich-stempel">Vertraulich &mdash; nur interner Gebrauch</div>
    </div>

    {{-- ============ INHALTSVERZEICHNIS (Seite 2) ============ --}}
    <div class="sheet page-break">
        <h2 style="margin-top:0;">Inhaltsverzeichnis</h2>
        <table class="toc-table">
            @foreach ([
                '1' => 'Versionshistorie',
                '2' => 'Definitionen: Störung, Notfall, Krise',
                '3' => 'Geltungsbereich und Zweck',
                '4' => 'Krisenorganisation und Verantwortlichkeiten',
                '5' => 'Kontakte und Eskalationskette',
                '6' => 'Notfall-Level und Eskalationsstufen',
                '7' => 'Verhaltenskodex im Notfall',
                '8' => 'Verfügbare Mittel und Befugnisse',
                '9' => 'Systeme und Betriebskontinuität',
                '10' => 'Notfall-Szenarien und Playbooks',
                '11' => 'Kommunikation im Notfall',
                '12' => 'Meldepflichten und dokumentierte Vorfälle',
                '13' => 'Pflege und Testplan',
            ] as $num => $title)
                <tr>
                    <td class="num">{{ $num }}.</td>
                    <td>{{ $title }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    {{-- ============ KAPITEL 1: VERSIONSHISTORIE ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>1. Versionshistorie</h2>
        <p>Jede Änderung an diesem Handbuch ist nachfolgend dokumentiert. Maßgeblich ist die Version, die auf dem Deckblatt aufgeführt ist. Eine neue Version tritt erst nach Freigabe durch die Geschäftsführung in Kraft <em>(vgl. BSI-Standard 200-4, Kap. 3.4 Lebenszyklus)</em>.</p>

        @if ($company->handbookVersions->isEmpty())
            <p class="small">Keine Versionen dokumentiert.</p>
        @else
            <table class="version-history">
                <thead>
                    <tr>
                        <th style="width: 14%;">Version</th>
                        <th style="width: 22%;">Datum</th>
                        <th style="width: 38%;">Geändert von</th>
                        <th>Freigabe</th>
                    </tr>
                </thead>
                @foreach ($company->handbookVersions as $v)
                    <tbody class="version-group">
                        <tr class="version-head">
                            <td><strong>{{ $v->version }}</strong></td>
                            <td>{{ $v->changed_at->format('d.m.Y') }}</td>
                            <td>{{ $v->changedBy?->fullName() ?? '—' }}</td>
                            <td>
                                @if ($v->approved_at)
                                    {{ $v->approved_at->format('d.m.Y') }}
                                    @if ($v->approvedBy?->fullName() ?? $v->approved_by_name)
                                        <span class="small">· {{ $v->approvedBy?->fullName() ?? $v->approved_by_name }}</span>
                                    @endif
                                @else
                                    <em>offen</em>
                                @endif
                            </td>
                        </tr>
                        <tr class="version-reason">
                            <td colspan="4">
                                <span class="reason-label">Änderungsgrund:</span>
                                {{ $v->change_reason }}
                            </td>
                        </tr>
                    </tbody>
                @endforeach
            </table>
        @endif

        <h3>Aktuelle Freigabe</h3>
        @if ($currentVersion)
            <p>
                Version <strong>{{ $currentVersion->version }}</strong> wurde am {{ $currentVersion->approved_at->format('d.m.Y') }}
                @if ($approver) durch <em>{{ $approver }}</em> @endif
                freigegeben und ist in Kraft.
            </p>
        @else
            <p><em>Es liegt aktuell keine freigegebene Version vor.</em></p>
        @endif

        <div class="signature">
            <table style="border:0;">
                <tr>
                    <td style="border:0; width:50%;">
                        <div class="signature-line"></div>
                        <div class="small">Ort, Datum &mdash; Unterschrift Geschäftsführung</div>
                    </td>
                    <td style="border:0; width:50%; padding-left:10mm;">
                        <div class="signature-line"></div>
                        <div class="small">Ort, Datum &mdash; Unterschrift Notfallbeauftragte/r</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ============ KAPITEL 2: DEFINITIONEN ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>2. Definitionen: Störung, Notfall, Krise</h2>
        <p>Einheitliche Begriffe verhindern Missverständnisse im Ernstfall. Die folgende Abgrenzung orientiert sich an <em>BSI-Standard 200-4</em> und <em>ISO/IEC 22301</em>. Jedes Ereignis ist bei Entdeckung sofort einer dieser Kategorien zuzuordnen.</p>

        <h3>2.1 Störung</h3>
        <p><strong>Definition.</strong> Ein unerwartetes Ereignis, das den Normalbetrieb beeinträchtigt, jedoch durch den regulären IT-Betrieb oder die zuständige Fachabteilung behoben werden kann, ohne dass der Notfallplan aktiviert wird.</p>
        <p><strong>Reaktion.</strong> Bearbeitung über den regulären IT-Support beziehungsweise den Helpdesk; Dokumentation im Störungsticket. Der Notfallplan wird nicht aktiviert.</p>
        <p><strong>Eskalation.</strong> Übergang in einen <em>Notfall</em>, sofern die Recovery Time Objective (RTO) überschritten wird oder eine Ausbreitung erkennbar ist.</p>

        <h3>2.2 Notfall</h3>
        <p><strong>Definition.</strong> Ein Ereignis, das den normalen Geschäftsbetrieb erheblich beeinträchtigt oder unterbricht und das nicht mit den Mitteln des regulären Betriebs behoben werden kann. Der Notfallplan wird aktiviert.</p>
        <p><strong>Reaktion.</strong> Aktivierung des Notfallplans; Information des Krisenstabs; Nutzung der Systemblätter und Playbooks (Kapitel 9 und 10).</p>
        <p><strong>Eskalation.</strong> Übergang in eine <em>Krise</em>, sofern mehrere Standorte betroffen sind, existenzbedrohende Schäden eintreten oder behördliche Meldepflichten greifen.</p>

        <h3>2.3 Krise</h3>
        <p><strong>Definition.</strong> Eine außergewöhnliche Situation, die die Existenz der Organisation, ihre Reputation oder die Sicherheit von Personen bedroht. Erfordert übergeordnete Entscheidungen der Geschäftsführung sowie gegebenenfalls die Einbindung externer Spezialisten <em>(IT-Forensik, Rechtsberatung, Kommunikation)</em>.</p>
        <p><strong>Reaktion.</strong> Aktivierung der vollständigen Krisenorganisation; Kommunikation ausschließlich durch autorisierte Personen.</p>

        <h3>2.4 Abgrenzung Notfallhandbuch und Betriebsdokumentation</h3>
        <p>Dieses Handbuch beschreibt ausschließlich, <em>was</em> zu tun ist (Schritte, Rollen, Eskalation). Das technische <em>Wie</em> (Befehle, Pfade, Tools) gehört in die Betriebsdokumentation und in die Runbooks, auf die in den Systemblättern verwiesen wird. Ziel ist, dass dieses Handbuch stabil bleibt, auch wenn sich Technik und Software ändern.</p>
    </div>

    {{-- ============ KAPITEL 3: GELTUNGSBEREICH ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>3. Geltungsbereich und Zweck</h2>

        <h3>3.1 Zweck</h3>
        <p>Dieses Notfall- und Krisenhandbuch stellt die Handlungsfähigkeit der Organisation bei ungeplanten Störungen, Systemausfällen oder Krisen sicher. Es definiert Verantwortlichkeiten, Eskalationswege und konkrete nächste Schritte ohne technische Umsetzungsdetails.</p>

        <h3>3.2 Geltungsbereich</h3>
        <table class="meta-table">
            <tr><th>Organisation</th><td>{{ $company->name }}</td></tr>
            <tr><th>Rechtsform</th><td>{{ $company->legal_form?->label() ?? '—' }}</td></tr>
            <tr><th>Branche</th><td>{{ $company->industry->label() }}</td></tr>
            <tr><th>Mitarbeitende</th><td>{{ $company->employee_count ?? '—' }}</td></tr>
            <tr><th>Standorte (Anzahl)</th><td>{{ $company->locations_count ?? $company->locations->count() }}</td></tr>
            @if ($company->locations->isNotEmpty())
                <tr>
                    <th>Standort-Adressen</th>
                    <td>
                        @foreach ($company->locations as $loc)
                            <div>{{ $loc->name }}: {{ $loc->street }}, {{ $loc->postal_code }} {{ $loc->city }}@if ($loc->is_headquarters) <em>(Hauptsitz)</em>@endif</div>
                        @endforeach
                    </td>
                </tr>
            @endif
            <tr><th>Geltung ab</th><td>{{ $company->valid_from?->format('d.m.Y') ?? '—' }}</td></tr>
        </table>

        <h3>3.3 Aufsichts- und compliance-rechtliche Einordnung</h3>
        <table class="meta-table">
            <tr><th>KRITIS-relevant</th><td>{{ $company->kritis_relevant?->label() ?? '—' }}</td></tr>
            <tr><th>NIS2-Einordnung</th><td>{{ $company->nis2_classification?->label() ?? '—' }}</td></tr>
            <tr>
                <th>Datenschutz-Aufsichtsbehörde</th>
                <td>
                    {{ $company->data_protection_authority_name ?? '—' }}
                    @if ($company->data_protection_authority_phone)<br>Telefon: {{ $company->data_protection_authority_phone }}@endif
                    @if ($company->data_protection_authority_website)<br>Website: {{ $company->data_protection_authority_website }}@endif
                </td>
            </tr>
        </table>

        <h3>3.4 Was dieses Handbuch enthält &mdash; und was nicht</h3>
        <table class="scope-table">
            <thead>
                <tr><th>Enthalten</th><th>Nicht enthalten</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <ul>
                            <li>Eskalationswege und Verantwortlichkeiten (RACI)</li>
                            <li>Konkrete nächste Schritte je System und Szenario</li>
                            <li>Kontakte und Erreichbarkeiten</li>
                            <li>Stufenmodell Störung &ndash; Notfall &ndash; Krise</li>
                            <li>Kommunikationsvorlagen</li>
                            <li>Meldepflichten <em>(DSGVO, NIS2)</em></li>
                        </ul>
                    </td>
                    <td>
                        <ul>
                            <li>Technische Umsetzungsschritte (&rarr; Runbooks)</li>
                            <li>Installationsanleitungen oder Befehle</li>
                            <li>Detaillierte IT-Architektur (&rarr; Betriebsdokumentation)</li>
                            <li>Backup-Pfade und Tool-Einstellungen</li>
                            <li>HR-Regelungen bei Betriebsunterbrechung</li>
                            <li>Versicherungsfall-Prozesse</li>
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>

        <h3>3.5 Rechtsgrundlagen und Referenzen</h3>
        <ul>
            <li><em>BSI-Standard 200-4</em> &ndash; Business Continuity Management.</li>
            <li><em>BSI IT-Grundschutz (BSI-Standard 200-2).</em></li>
            <li><em>ISO/IEC 22301</em> &ndash; Business Continuity Management Systems.</li>
            <li><em>DSGVO Art. 32</em> (Sicherheit der Verarbeitung), <em>Art. 33/34</em> (Meldepflichten).</li>
            <li><em>NIS2-Richtlinie (EU) 2022/2555</em>, soweit anwendbar.</li>
        </ul>
    </div>

    {{-- ============ KAPITEL 4: KRISENORGANISATION ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>4. Krisenorganisation und Verantwortlichkeiten</h2>
        <p>Jede Schlüsselrolle ist mit einer Hauptperson und mindestens einer Vertretung besetzt. Vertretungen sind gleichberechtigt handlungsfähig, sobald die Hauptperson nicht erreichbar ist.</p>

        <h3>4.1 Krisenrollen &mdash; Besetzung und Vertretung</h3>
        @if ($crisisHolders->isEmpty())
            <p><em>Keine Krisenrollen vergeben.</em></p>
        @else
            <table class="role-table">
                <thead>
                    <tr>
                        <th style="width: 32%;">Krisenrolle</th>
                        <th style="width: 32%;">Person</th>
                        <th>Erreichbarkeit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($crisisHolders as $employee)
                        <tr>
                            <td>
                                <strong>{{ $employee->crisis_role->label() }}</strong>
                                <div class="role-function">{{ $employee->is_crisis_deputy ? 'Vertretung' : 'Hauptperson' }}</div>
                            </td>
                            <td>
                                {{ $employee->fullName() }}
                                @if ($employee->position)
                                    <div class="small">{{ $employee->position }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($employee->mobile_phone)
                                    <div class="contact-label">Mobil</div>
                                    <div class="contact-value">{{ $employee->mobile_phone }}</div>
                                @endif
                                @if ($employee->email)
                                    <div class="contact-label contact-label-spaced">E-Mail</div>
                                    <div class="contact-value contact-email">{{ $employee->email }}</div>
                                @endif
                                @if (! $employee->mobile_phone && ! $employee->email)
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>4.2 Aufgaben im Notfall</h3>
        <table>
            <tbody>
                <tr><td class="label">Notfallbeauftragte/r</td><td>Koordiniert alle Maßnahmen, trifft operative Entscheidungen, beruft Lagebesprechungen ein, dokumentiert den Vorfall, hält Protokoll.</td></tr>
                <tr><td class="label">IT-Verantwortliche/r</td><td>Bewertet den technischen Schaden, kontaktiert den IT-Dienstleister, leitet den Wiederanlauf nach Prioritätenliste ein, verweist auf Runbooks.</td></tr>
                <tr><td class="label">Datenschutzbeauftragte/r</td><td>Bewertet die Datenschutzrelevanz, prüft und überwacht DSGVO-Meldepflichten innerhalb der 72-Stunden-Frist <em>(Art. 33 DSGVO)</em>.</td></tr>
                <tr><td class="label">Kommunikationsverantwortliche/r</td><td>Verantwortet alle externen Kommunikationsmaßnahmen. Keine Aussage nach außen ohne Freigabe der Geschäftsführung.</td></tr>
                <tr><td class="label">Geschäftsführung</td><td>Letzte Entscheidungsinstanz. Freigabe externer Kommunikation. Genehmigung außerordentlicher Ausgaben und Maßnahmen.</td></tr>
            </tbody>
        </table>
    </div>

    {{-- ============ KAPITEL 5: KONTAKTE & ESKALATIONSKETTE ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>5. Kontakte und Eskalationskette</h2>

        <h3>5.1 Interne Notfallkontakte</h3>
        <p>Diese Kontakte gelten auch dann, wenn E-Mail- und VoIP-Systeme ausgefallen sind. Mobilnummern sind primär zu verwenden.</p>
        @if ($company->employees->isEmpty())
            <p><em>Keine Mitarbeiter hinterlegt.</em></p>
        @else
            <table class="role-table">
                <thead>
                    <tr>
                        <th style="width: 32%;">Person</th>
                        <th style="width: 28%;">Funktion</th>
                        <th>Erreichbarkeit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($company->employees->where('is_key_personnel', true)->merge($company->employees->whereNotNull('crisis_role'))->unique('id') as $emp)
                        <tr>
                            <td><strong>{{ $emp->fullName() }}</strong></td>
                            <td>{{ $emp->position ?? '—' }}</td>
                            <td>
                                @if ($emp->mobile_phone)
                                    <div class="contact-label">Mobil</div>
                                    <div class="contact-value">{{ $emp->mobile_phone }}</div>
                                @endif
                                @if ($emp->private_phone)
                                    <div class="contact-label contact-label-spaced">Privat</div>
                                    <div class="contact-value">{{ $emp->private_phone }}</div>
                                @endif
                                @if ($emp->email)
                                    <div class="contact-label contact-label-spaced">E-Mail</div>
                                    <div class="contact-value contact-email">{{ $emp->email }}</div>
                                @endif
                                @if (! $emp->mobile_phone && ! $emp->private_phone && ! $emp->email)
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>5.2 Externe Dienstleister</h3>
        @if ($externalProviders->isEmpty())
            <p><em>Keine Dienstleister hinterlegt.</em></p>
        @else
            <table class="role-table">
                <thead>
                    <tr>
                        <th style="width: 32%;">Dienstleister</th>
                        <th style="width: 28%;">Kategorie</th>
                        <th>Erreichbarkeit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($externalProviders as $p)
                        <tr>
                            <td>
                                <strong>{{ $p->name }}</strong>
                                @if ($p->contact_name)<div class="small">{{ $p->contact_name }}</div>@endif
                            </td>
                            <td>{{ $p->type?->label() ?? '—' }}</td>
                            <td>
                                @if ($p->hotline)
                                    <div class="contact-label">Hotline</div>
                                    <div class="contact-value">{{ $p->hotline }}</div>
                                    @if ($p->sla)
                                        <div class="contact-note">{{ $p->sla }}</div>
                                    @endif
                                @endif
                                @if ($p->email)
                                    <div class="contact-label contact-label-spaced">E-Mail</div>
                                    <div class="contact-value contact-email">{{ $p->email }}</div>
                                @endif
                                @if ($p->contract_number)
                                    <div class="contact-label contact-label-spaced">Vertrag</div>
                                    <div class="contact-value">{{ $p->contract_number }}</div>
                                @endif
                                @if (! $p->hotline && ! $p->email && ! $p->contract_number)
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>5.3 Behörden und Meldestellen</h3>
        @if ($authorities->isEmpty())
            <p><em>Keine Behördenkontakte hinterlegt.</em></p>
        @else
            <table class="role-table">
                <thead>
                    <tr>
                        <th style="width: 32%;">Behörde / Meldestelle</th>
                        <th style="width: 28%;">Kategorie</th>
                        <th>Erreichbarkeit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($authorities as $a)
                        <tr>
                            <td>
                                <strong>{{ $a->name }}</strong>
                                @if ($a->contact_name)<div class="small">{{ $a->contact_name }}</div>@endif
                            </td>
                            <td>{{ $a->type?->label() }}</td>
                            <td>
                                @if ($a->hotline)
                                    <div class="contact-label">Telefon</div>
                                    <div class="contact-value">{{ $a->hotline }}</div>
                                @endif
                                @if ($a->email)
                                    <div class="contact-label contact-label-spaced">E-Mail</div>
                                    <div class="contact-value contact-email">{{ $a->email }}</div>
                                @endif
                                @if (! $a->hotline && ! $a->email)
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>5.4 Eskalationskette</h3>
        <ol>
            <li>Auftretende Probleme werden zunächst durch die zuständige Person vor Ort geprüft.</li>
            <li>Kann das Problem nicht lokal gelöst werden, wird der zuständige interne Ansprechpartner eingebunden.</li>
            <li>Ist weiterhin keine Lösung möglich, werden externe Dienstleister kontaktiert.</li>
            <li>Bei kritischen Auswirkungen auf Betrieb, Kunden oder Sicherheit wird die Geschäftsführung informiert.</li>
        </ol>
    </div>

    {{-- ============ KAPITEL 6: NOTFALL-LEVEL ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>6. Notfall-Level und Eskalationsstufen</h2>
        <p>Das Stufenmodell folgt dem Prinzip der aufsteigenden Eskalation. Ein Ereignis beginnt stets auf der niedrigsten zutreffenden Stufe und wird bei Verschlechterung hochgestuft. Das Herabstufen (Deeskalation) erfolgt ausschließlich durch den/die Notfallbeauftragte/n und ist im Vorfall-Protokoll zu dokumentieren.</p>

        @foreach ($company->emergencyLevels as $level)
            <div class="vermerk">
                <div class="vermerk-title">Stufe {{ $level->sort }} &mdash; {{ $level->name }}</div>
                @if ($level->description)<p>{{ $level->description }}</p>@endif
                @if ($level->reaction)<p><strong>Reaktion:</strong> {{ $level->reaction }}</p>@endif
            </div>
        @endforeach
    </div>

    {{-- ============ KAPITEL 7: VERHALTENSKODEX ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>7. Verhaltenskodex im Notfall</h2>
        <p>Klare Verhaltensregeln verhindern Chaos und Doppelarbeit. Der nachfolgende Kodex gilt für alle beteiligten Personen ab dem Moment der Notfall-Aktivierung.</p>

        <h3>7.1 Lagebesprechungen</h3>
        <ul>
            <li>Lagebesprechungen sind kurz und strukturiert. Maximale Dauer: 15 Minuten.</li>
            <li>Turnus: Stufe 3 alle 60 Minuten, Stufe 2 alle 2 Stunden, Stufe 1 einmal täglich.</li>
            <li>Leitung ausschließlich durch den/die Notfallbeauftragte/n oder dessen/deren Vertretung.</li>
        </ul>

        <h4>Standardagenda jeder Lagebesprechung</h4>
        <ol>
            <li>Lagebericht (3 Min.): Was ist aktuell bekannt? Status der laufenden Maßnahmen?</li>
            <li>Neue Erkenntnisse (2 Min.): Was hat sich seit der letzten Besprechung geändert?</li>
            <li>Entscheidungen (5 Min.): Welche Entscheidungen müssen jetzt getroffen werden? Wer entscheidet?</li>
            <li>Aufgaben und Verantwortliche (3 Min.): Konkrete nächste Schritte &mdash; wer macht was bis wann?</li>
            <li>Nächste Besprechung (1 Min.): Zeitpunkt und Ort beziehungsweise Einwahl festlegen.</li>
            <li>Ende und Protokollvermerk: Kurze Zusammenfassung in das Vorfall-Protokoll eintragen.</li>
        </ol>

        <h3>7.2 Allgemeine Verhaltensregeln</h3>
        <ul>
            <li>Eine Stimme, ein Befehlsstrang: Alle Maßnahmen laufen über den/die Notfallbeauftragte/n.</li>
            <li>Fakten statt Spekulation: Es werden ausschließlich gesicherte Informationen kommuniziert.</li>
            <li>Echtzeit-Dokumentation: Jede Maßnahme wird unmittelbar im Vorfall-Protokoll festgehalten.</li>
            <li>Kurze, klare Aussagen: Wer, was, bis wann.</li>
            <li>Vertretungsregel: Wer nicht erreichbar ist, wird umgehend durch die Vertretung ersetzt.</li>
            <li>Externe Kommunikation ausschließlich nach Freigabe durch die Geschäftsführung.</li>
            <li>Keine Alleingänge. Maßnahmen außerhalb des Notfallplans bedürfen ausdrücklicher Freigabe.</li>
            <li>Keine Veröffentlichungen in sozialen Medien zum Vorfall.</li>
            <li>Keine technischen Details nach außen <em>(Systemzustände, Logs, Architektur)</em>.</li>
        </ul>
    </div>

    {{-- ============ KAPITEL 8: MITTEL & BEFUGNISSE ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>8. Verfügbare Mittel und Befugnisse</h2>

        <h3>8.1 Finanzielle Befugnisse im Notfall</h3>
        <p>Im Notfall können Entscheidungen und Ausgaben anfallen, die den normalen Rahmen überschreiten. Die folgende Übersicht regelt, wer welche Beträge ohne weitere Freigabe genehmigen darf.</p>
        <table>
            <thead>
                <tr><th>Rolle</th><th>Maximalbetrag je Einzelmaßnahme</th><th>Beispiele</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>IT-Verantwortliche/r</td>
                    <td class="nowrap">{{ $company->budget_it_lead !== null ? number_format((float) $company->budget_it_lead, 2, ',', '.').' €' : '—' }}</td>
                    <td>Sofort-Ersatzteile, Notfall-Software-Lizenz</td>
                </tr>
                <tr>
                    <td>Notfallbeauftragte/r</td>
                    <td class="nowrap">{{ $company->budget_emergency_officer !== null ? number_format((float) $company->budget_emergency_officer, 2, ',', '.').' €' : '—' }}</td>
                    <td>Externe Notfall-IT-Dienstleistung, Mietgeräte</td>
                </tr>
                <tr>
                    <td>Geschäftsführung</td>
                    <td class="nowrap">{{ $company->budget_management !== null ? number_format((float) $company->budget_management, 2, ',', '.').' €' : '—' }}</td>
                    <td>IT-Forensik, externe Kommunikationsberatung, Rechtsanwalt</td>
                </tr>
            </tbody>
        </table>
        <p class="legal small">Jede Notfallausgabe ist zu dokumentieren <em>(Datum, Betrag, Grund, Genehmigender)</em> und nach Abschluss des Vorfalls in der Buchhaltung nachzuerfassen.</p>

        @if ($company->insurancePolicies->isNotEmpty())
            <h3>8.2 Versicherungen</h3>
            <table class="role-table">
                <thead>
                    <tr>
                        <th style="width: 32%;">Versicherer</th>
                        <th style="width: 28%;">Art</th>
                        <th>Police und Konditionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($company->insurancePolicies as $policy)
                        <tr>
                            <td>
                                <strong>{{ $policy->insurer }}</strong>
                                @if ($policy->contact_name)<div class="small">{{ $policy->contact_name }}</div>@endif
                            </td>
                            <td>{{ $policy->type->label() }}</td>
                            <td>
                                @if ($policy->policy_number)
                                    <div class="contact-label">Police</div>
                                    <div class="contact-value">{{ $policy->policy_number }}</div>
                                @endif
                                @if ($policy->hotline)
                                    <div class="contact-label contact-label-spaced">Hotline</div>
                                    <div class="contact-value">{{ $policy->hotline }}</div>
                                @endif
                                @if ($policy->email)
                                    <div class="contact-label contact-label-spaced">E-Mail</div>
                                    <div class="contact-value contact-email">{{ $policy->email }}</div>
                                @endif
                                @if ($policy->deductible)
                                    <div class="contact-label contact-label-spaced">Selbstbehalt</div>
                                    <div class="contact-value">{{ $policy->deductible }}</div>
                                @endif
                                @if ($policy->reporting_window)
                                    <div class="contact-label contact-label-spaced">Meldefrist</div>
                                    <div class="contact-value">{{ $policy->reporting_window }}</div>
                                @endif
                                @if (! $policy->policy_number && ! $policy->hotline && ! $policy->email && ! $policy->deductible && ! $policy->reporting_window)
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($company->cyber_insurance_deductible)
                <p class="small">Cyber-Selbstbehalt laut Stammdaten: <strong>{{ $company->cyber_insurance_deductible }}</strong>. Versicherer ist <em>vor</em> größeren Ausgaben zu informieren.</p>
            @endif
        @endif

        @if ($company->emergencyResources->isNotEmpty())
            <h3>8.3 Verfügbare Sofortmittel und Ressourcen</h3>
            <table class="role-table">
                <thead>
                    <tr>
                        <th style="width: 28%;">Bezeichnung</th>
                        <th style="width: 22%;">Typ</th>
                        <th>Aufbewahrung und Zugriff</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($company->emergencyResources as $r)
                        <tr>
                            <td>
                                <strong>{{ $r->name ?: $r->type->label() }}</strong>
                                @if ($r->description)<div class="small">{{ $r->description }}</div>@endif
                            </td>
                            <td>{{ $r->type->label() }}</td>
                            <td>
                                @if ($r->location)
                                    <div class="contact-label">Aufbewahrungsort</div>
                                    <div class="contact-value">{{ $r->location }}</div>
                                @endif
                                @if ($r->access_holders)
                                    <div class="contact-label contact-label-spaced">Zugriffsberechtigte</div>
                                    <div class="contact-value">{{ $r->access_holders }}</div>
                                @endif
                                @if ($r->last_check_at)
                                    <div class="contact-label contact-label-spaced">Letzte Prüfung</div>
                                    <div class="contact-value">{{ $r->last_check_at->format('d.m.Y') }}</div>
                                @endif
                                @if (! $r->location && ! $r->access_holders && ! $r->last_check_at)
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @php($directProviders = $providers->filter(fn ($p) => $p->direct_order_limit !== null))
        @if ($directProviders->isNotEmpty())
            <h3>8.4 Direktbeauftragung externer Notfalldienstleister</h3>
            <p>Folgende externe Dienstleister können ohne vorherige Ausschreibung im Notfall direkt beauftragt werden, bis zu der jeweils angegebenen Höhe.</p>
            <table>
                <thead>
                    <tr><th>Dienstleister</th><th>Leistung</th><th>Vertrag</th><th>Direktbeauftragung bis</th></tr>
                </thead>
                <tbody>
                    @foreach ($directProviders as $p)
                        <tr>
                            <td><strong>{{ $p->name }}</strong></td>
                            <td>{{ $p->type?->label() ?? '—' }}</td>
                            <td>{{ $p->contract_number ?? '—' }}</td>
                            <td class="nowrap">{{ number_format((float) $p->direct_order_limit, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ============ KAPITEL 9: SYSTEME & BETRIEBSKONTINUITÄT ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>9. Systeme und Betriebskontinuität</h2>
        <p>
            <em>RTO (Recovery Time Objective)</em> bezeichnet die maximal tolerierbare Ausfallzeit, nach deren Ablauf Schäden kritisch werden.
            <em>RPO (Recovery Point Objective)</em> bezeichnet den maximal tolerierbaren Datenverlust und bestimmt die erforderliche Backup-Frequenz. Die Systemblätter dokumentieren das <em>Was</em>; das technische <em>Wie</em> steht in den Runbooks.
        </p>

        @foreach (\App\Enums\SystemCategory::cases() as $category)
            @php($systems = $company->systems->where('category', $category))
            @continue($systems->isEmpty())

            <h3>9.{{ $loop->iteration }} {{ $category->label() }}</h3>
            <p class="small">{{ $category->description() }}</p>
            <table>
                <thead>
                    <tr>
                        <th>System</th>
                        <th>Priorität / Stufe</th>
                        <th>RTO / RPO</th>
                        <th>Ersatzprozess</th>
                        <th>Runbook</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($systems as $system)
                        <tr>
                            <td>
                                <strong>{{ $system->name }}</strong>
                                @if ($system->description)<br><span class="small">{{ $system->description }}</span>@endif
                                @if ($system->serviceProviders->isNotEmpty())
                                    <br><span class="small">Dienstleister: {{ $system->serviceProviders->pluck('name')->join(', ') }}</span>
                                @endif
                            </td>
                            <td>
                                {{ $system->priority?->name ?? '—' }}
                                @if ($system->emergencyLevel)<br><span class="small">{{ $system->emergencyLevel->name }}</span>@endif
                            </td>
                            <td>
                                @if ($system->rto_minutes)RTO: {{ \App\Support\Duration::format($system->rto_minutes) }}<br>@endif
                                @if ($system->rpo_minutes)RPO: {{ \App\Support\Duration::format($system->rpo_minutes) }}@endif
                                @if (! $system->rto_minutes && ! $system->rpo_minutes)—@endif
                            </td>
                            <td>{{ $system->fallback_process ?? '—' }}</td>
                            <td>{{ $system->runbook_reference ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

        @if (! empty($recoveryPlan['stages']))
            <h3>9.{{ count(array_filter(\App\Enums\SystemCategory::cases(), fn ($c) => $company->systems->where('category', $c)->isNotEmpty())) + 1 }} Wiederanlauf-Reihenfolge</h3>
            <p>Verbindliche Reihenfolge nach Abhängigkeitsanalyse. Eine Stufe darf erst gestartet werden, wenn die vorherige vollständig läuft. Systeme einer Stufe können parallel angefahren werden.</p>
            <table>
                <thead>
                    <tr><th style="width: 12mm;">Stufe</th><th>Systeme</th></tr>
                </thead>
                <tbody>
                    @foreach ($recoveryPlan['stages'] as $i => $stage)
                        <tr>
                            <td><strong>{{ $i + 1 }}</strong></td>
                            <td>
                                @foreach ($stage as $s)
                                    <div>
                                        <strong>{{ $s->name }}</strong>@if ($s->dependencies->isNotEmpty()) <span class="small">(setzt voraus: {{ $s->dependencies->pluck('name')->join(', ') }})</span>@endif
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if (! empty($recoveryPlan['cycles']))
                <p class="legal"><strong>Hinweis &mdash; zirkuläre Abhängigkeit:</strong>
                    {{ collect($recoveryPlan['cycles'])->pluck('name')->join(', ') }}.
                    Bitte Abhängigkeiten überprüfen.
                </p>
            @endif
        @endif

        {{-- 9.X – Detaillierte System-Sicht: RACI-Verantwortliche und Aufgaben pro System --}}
        @if (! empty($systemsDetail))
            <h3>Verantwortlichkeiten und Aufgaben pro System</h3>
            <p class="small">RACI: <strong>R</strong> = Durchführend, <strong>A</strong> = Verantwortlich, <strong>C</strong> = Konsultiert, <strong>I</strong> = Informiert. Pro System die operativ besetzten Rollen und definierte Wartungs-/Prüfaufgaben.</p>

            @foreach ($systemsDetail as $entry)
                <div class="keep" style="margin-top: 4mm;">
                    <h4 style="margin-bottom: 1mm;">{{ $entry['system']->name }}</h4>

                    <table>
                        <thead>
                            <tr>
                                <th style="width: 25%;">R &mdash; Durchführend</th>
                                <th style="width: 25%;">A &mdash; Verantwortlich</th>
                                <th style="width: 25%;">C &mdash; Konsultiert</th>
                                <th style="width: 25%;">I &mdash; Informiert</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="vertical-align: top;">
                                    @forelse ($entry['raci']['R'] ?? [] as $line)<div>{{ $line }}</div>@empty<span class="small">&mdash;</span>@endforelse
                                </td>
                                <td style="vertical-align: top;">
                                    @forelse ($entry['raci']['A'] ?? [] as $line)<div>{{ $line }}</div>@empty<span class="small">&mdash;</span>@endforelse
                                </td>
                                <td style="vertical-align: top;">
                                    @forelse ($entry['raci']['C'] ?? [] as $line)<div>{{ $line }}</div>@empty<span class="small">&mdash;</span>@endforelse
                                </td>
                                <td style="vertical-align: top;">
                                    @forelse ($entry['raci']['I'] ?? [] as $line)<div>{{ $line }}</div>@empty<span class="small">&mdash;</span>@endforelse
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    @if (! empty($entry['tasks']))
                        <p class="small" style="margin-top: 2mm; margin-bottom: 1mm;"><strong>Aufgaben</strong></p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Aufgabe</th>
                                    <th style="width: 22mm;">Fällig</th>
                                    <th style="width: 28mm;">Status</th>
                                    <th>Verantwortlich (R / A)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entry['tasks'] as $task)
                                    <tr>
                                        <td>
                                            <strong>{{ $task['title'] }}</strong>
                                            @if (! empty($task['description']))<br><span class="small">{{ $task['description'] }}</span>@endif
                                        </td>
                                        <td>{{ $task['due'] ?? '—' }}</td>
                                        <td>{{ $task['status'] }}</td>
                                        <td>
                                            @if ($task['r'] === '' && $task['a'] === '')
                                                <span class="small">— kein RACI hinterlegt —</span>
                                            @else
                                                @if ($task['r'] !== '')<div><strong>R:</strong> {{ $task['r'] }}</div>@endif
                                                @if ($task['a'] !== '')<div><strong>A:</strong> {{ $task['a'] }}</div>@endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    {{-- ============ KAPITEL 10: SZENARIEN & PLAYBOOKS ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>10. Notfall-Szenarien und Playbooks</h2>
        <p>Jedes Playbook beschreibt: Auslöser <em>(Trigger)</em>, konkrete Schritte und die jeweils Verantwortlichen. Das technische <em>Wie</em> steht in den referenzierten Runbooks. Im Ernstfall wird das Playbook aufgeschlagen und die Schritte in Reihenfolge abgearbeitet.</p>

        @foreach ($company->scenarios as $scenario)
            <div class="keep">
                <h3>10.{{ $loop->iteration }} {{ $scenario->name }}</h3>
                @if ($scenario->description)<p>{{ $scenario->description }}</p>@endif
                @if ($scenario->trigger)
                    <p><strong>Auslöser.</strong> <em>{{ $scenario->trigger }}</em></p>
                @endif
                @if ($scenario->steps->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 8mm;">Nr.</th>
                                <th>Maßnahme</th>
                                <th style="width: 32%;">Verantwortlich</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scenario->steps as $step)
                                <tr>
                                    <td><strong>{{ $step->sort }}</strong></td>
                                    <td>
                                        <strong>{{ $step->title }}</strong>
                                        @if ($step->description)<br>{{ $step->description }}@endif
                                    </td>
                                    <td>{{ $step->responsible ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ============ KAPITEL 11: KOMMUNIKATION ============ --}}
    @if ($company->communicationTemplates->isNotEmpty())
        <div class="sheet page-break">
            <div class="doc-header">
                <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
                <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
                <div class="ref">{{ $aktenzeichen }}</div>
            </div>

            <h2>11. Kommunikation im Notfall</h2>
            <p>Klare und rechtzeitige Kommunikation ist genauso bedeutsam wie technische Maßnahmen. Alle externen Aussagen sind durch die Geschäftsführung freizugeben. Kein Mitarbeiter kommuniziert ohne Freigabe mit Presse oder Kunden.</p>

            <h3>11.1 Fallback-Reihenfolge der Kommunikationskanäle</h3>
            <table>
                <thead><tr><th style="width: 8mm;">Nr.</th><th>Kanal</th><th>Einsatz</th></tr></thead>
                <tbody>
                    <tr><td>1</td><td>Direkte Ansprache / Aushang</td><td>Immer möglich; bei vollständigem Systemausfall.</td></tr>
                    <tr><td>2</td><td>SMS / Mobiltelefon</td><td>E-Mail und VoIP nicht verfügbar.</td></tr>
                    <tr><td>3</td><td>E-Mail (intern)</td><td>Mailsystem verfügbar.</td></tr>
                    <tr><td>4</td><td>Team-Chat (Teams, Slack o.ä.)</td><td>Systeme verfügbar.</td></tr>
                    <tr><td>5</td><td>Website / Soziale Medien</td><td>Externe Kommunikation an Kunden, ausschließlich nach Freigabe der Geschäftsführung.</td></tr>
                </tbody>
            </table>

            <h3>11.2 Kommunikationsvorlagen</h3>
            @foreach (\App\Enums\CommunicationAudience::cases() as $audience)
                @php($audienceTemplates = $company->communicationTemplates->where('audience', $audience))
                @continue($audienceTemplates->isEmpty())
                <h4>{{ $audience->label() }}</h4>
                @foreach ($audienceTemplates as $tpl)
                    <div class="vermerk">
                        <div class="vermerk-title">{{ $tpl->name }} &mdash; {{ $tpl->channel->label() }}@if ($tpl->scenario) &mdash; Szenario: {{ $tpl->scenario->name }}@endif</div>
                        @if ($tpl->subject)
                            <p><strong>Betreff:</strong> {{ \App\Support\TemplatePlaceholders::resolve($tpl->subject, $company) }}</p>
                        @endif
                        <p style="white-space: pre-wrap;">{{ \App\Support\TemplatePlaceholders::resolve($tpl->body, $company) }}</p>
                        @if ($tpl->fallback)
                            <p class="small"><em>Wenn Kanal ausgefallen:</em> {{ \App\Support\TemplatePlaceholders::resolve($tpl->fallback, $company) }}</p>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    @endif

    {{-- ============ KAPITEL 12: MELDEPFLICHTEN ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>12. Meldepflichten und dokumentierte Vorfälle</h2>

        <h3>12.1 DSGVO &mdash; Meldepflicht bei Datenpannen</h3>
        <p>Gemäß <em>Art. 33 DSGVO</em> ist eine Meldung an die zuständige Aufsichtsbehörde innerhalb von 72 Stunden ab Kenntnis vorzunehmen, sofern ein Risiko für die Rechte und Freiheiten Betroffener besteht. Bei hohem Risiko sind zusätzlich die Betroffenen ohne unangemessene Verzögerung zu informieren <em>(Art. 34 DSGVO)</em>.</p>
        <table class="meta-table">
            <tr><th>Zuständige Aufsichtsbehörde</th><td>{{ $company->data_protection_authority_name ?? '—' }}@if ($company->data_protection_authority_phone) &middot; Tel.: {{ $company->data_protection_authority_phone }}@endif@if ($company->data_protection_authority_website)<br>{{ $company->data_protection_authority_website }}@endif</td></tr>
            @php($dpo = $crisisHolders->firstWhere('crisis_role.value', 'dpo'))
            <tr><th>Datenschutzbeauftragte/r</th><td>{{ $dpo?->fullName() ?? '—' }}@if ($dpo) &middot; {{ $dpo->mobile_phone }} &middot; {{ $dpo->email }}@endif</td></tr>
        </table>

        <h3>12.2 BSI / NIS2-Meldepflichten</h3>
        <table class="meta-table">
            <tr><th>KRITIS-Einordnung</th><td>{{ $company->kritis_relevant?->label() ?? '—' }}</td></tr>
            <tr><th>NIS2-Einordnung</th><td>{{ $company->nis2_classification?->label() ?? '—' }}</td></tr>
            <tr><th>BSI-Meldestelle</th><td>www.bsi.bund.de &middot; Tel.: 0228 99 9582-0</td></tr>
            <tr><th>Frühwarnung</th><td>Erhebliche Sicherheitsvorfälle innerhalb von 24 Stunden.</td></tr>
            <tr><th>Detailmeldung</th><td>Innerhalb von 72 Stunden.</td></tr>
        </table>

        <h3>12.3 Dokumentierte Vorfälle</h3>
        @if ($company->incidentReports->isEmpty())
            <p><em>Keine Vorfälle dokumentiert.</em></p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Bezeichnung</th>
                        <th>Typ</th>
                        <th>Zeitpunkt</th>
                        <th>Erfolgte Meldungen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($company->incidentReports as $i)
                        <tr>
                            <td>
                                <strong>{{ $i->title }}</strong>
                                @if ($i->notes)<br><span class="small">{{ $i->notes }}</span>@endif
                            </td>
                            <td>{{ $i->type->label() }}</td>
                            <td>{{ $i->occurred_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($i->obligations->isEmpty())
                                    <em>keine</em>
                                @else
                                    @foreach ($i->obligations as $o)
                                        <div>
                                            <strong>{{ $o->obligation->label() }}</strong>
                                            @if ($o->reported_at) &middot; {{ $o->reported_at->format('d.m.Y H:i') }}@endif
                                            @if ($o->note)<br><span class="small">{{ $o->note }}</span>@endif
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>12.4 Merkhilfe</h3>
        <p><em>Wann ist zu melden?</em> Immer wenn personenbezogene Daten betroffen sind und ein Risiko für die Betroffenen besteht.<br>
        <em>Frist:</em> 72 Stunden ab Kenntnis &mdash; gerechnet ab dem Zeitpunkt, zu dem ein Mitarbeiter Kenntnis erlangt.<br>
        <em>Im Zweifel:</em> Eher zu früh als zu spät melden; Details können nachgereicht werden.</p>
    </div>

    {{-- ============ KAPITEL 13: PFLEGE & TESTPLAN ============ --}}
    <div class="sheet page-break">
        <div class="doc-header">
            <img class="emblem-mark" src="{{ $emblemUrl }}" alt="">
            <div class="org">{{ $company->name }} &mdash; Notfall- und Krisenhandbuch</div>
            <div class="ref">{{ $aktenzeichen }}</div>
        </div>

        <h2>13. Pflege und Testplan</h2>
        <p>Ein Notfallhandbuch ist nur so wirksam wie seine letzte Aktualisierung. Ungetestete Pläne erzeugen im Ernstfall trügerische Sicherheit. <em>BSI-Standard 200-4</em> empfiehlt mindestens jährliche Tests sowie eine sofortige Aktualisierung bei wesentlichen Änderungen.</p>

        <h3>13.1 Pflichtupdates</h3>
        <ul>
            <li>Personalwechsel in Schlüsselrollen &mdash; Kontaktliste und Rollenbesetzung sofort aktualisieren.</li>
            <li>Neues oder geändertes System &mdash; Systemblatt anlegen oder anpassen.</li>
            <li>Neuer oder geänderter Dienstleister &mdash; Kontaktdaten und Vertragsnummer aktualisieren.</li>
            <li>Nach jedem Notfall der Stufe Hoch oder Kritisch &mdash; Lessons Learned einarbeiten, PIR-Ergebnisse umsetzen.</li>
            <li>Änderung gesetzlicher Anforderungen &mdash; Kapitel 12 (Meldepflichten) prüfen und anpassen.</li>
            <li>Umzug oder neuer Standort &mdash; Standortdaten, Kontakte, Mittel-Übersicht prüfen.</li>
            <li>Jährliche Regelprüfung &mdash; vollständiges Review aller Kapitel und Kontaktdaten.</li>
        </ul>

        <h3>13.2 Testplan</h3>
        @if ($company->handbookTests->isEmpty())
            <p><em>Kein Testplan hinterlegt.</em></p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Test</th>
                        <th>Inhalt</th>
                        <th>Intervall</th>
                        <th>Letzte Durchführung</th>
                        <th>Nächste Fälligkeit</th>
                        <th>Verantwortlich</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($company->handbookTests as $t)
                        <tr>
                            <td><strong>{{ $t->type->label() }}</strong>@if ($t->name)<br><span class="small">{{ $t->name }}</span>@endif</td>
                            <td>{{ $t->description ?? '—' }}</td>
                            <td>{{ $t->interval->label() }}</td>
                            <td>{{ $t->last_executed_at?->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ $t->next_due_at?->format('d.m.Y') ?? '—' }}{{ $t->isOverdue() ? ' (überfällig)' : '' }}</td>
                            <td>{{ $t->responsible?->fullName() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($company->scenarioRuns->isNotEmpty())
            <h3>13.3 Durchgeführte Übungen und Vorfälle</h3>
            <table>
                <thead>
                    <tr><th>Bezeichnung</th><th>Modus</th><th>Szenario</th><th>Beginn</th><th>Ende</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($company->scenarioRuns->sortByDesc('started_at') as $run)
                        <tr>
                            <td>
                                <strong>{{ $run->title }}</strong>
                                @if ($run->summary)<br><span class="small">{{ $run->summary }}</span>@endif
                            </td>
                            <td>{{ $run->mode->label() }}</td>
                            <td>{{ $run->scenario?->name ?? '—' }}</td>
                            <td>{{ $run->started_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>{{ $run->ended_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($run->isActive())
                                    <em>aktiv</em>
                                @elseif ($run->aborted_at)
                                    abgebrochen
                                @else
                                    abgeschlossen
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="footer-note">
            {{ $company->name }} &mdash; Notfall- und Krisenhandbuch &mdash; {{ $aktenzeichen }} &mdash; Stand {{ now()->format('d.m.Y H:i') }} Uhr
        </div>
    </div>

    <script>
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        }
    </script>
</body>
</html>
