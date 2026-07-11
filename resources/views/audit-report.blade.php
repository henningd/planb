<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit-/Governance-Bericht — {{ $company->name }}</title>
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1a1a1a; font-size: 10.5pt; line-height: 1.4; margin: 0; padding: 0; background: #f3f4f6; }
        .page { max-width: 190mm; margin: 0 auto; background: #fff; padding: 14mm; }
        h1 { font-size: 20pt; margin: 0 0 2mm; }
        h2 { font-size: 13pt; margin: 8mm 0 2mm; padding-bottom: 1mm; border-bottom: 1.5pt solid #333; page-break-after: avoid; }
        h3 { font-size: 10.5pt; text-transform: uppercase; letter-spacing: 0.04em; color: #555; margin: 4mm 0 1mm; page-break-after: avoid; }
        p { margin: 0 0 2mm; }
        .muted { color: #666; }
        .small { font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; margin: 1mm 0 3mm; font-size: 9.5pt; page-break-inside: avoid; }
        th, td { border: 0.5pt solid #999; padding: 1.3mm 2mm; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #ececec; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.03em; }
        td.k { width: 34%; background: #fafafa; font-weight: bold; }
        .process { page-break-inside: avoid; margin-top: 6mm; }
        .crit { display: inline-block; font-size: 8pt; font-weight: bold; text-transform: uppercase; padding: 0.5mm 1.8mm; border-radius: 2pt; border: 0.5pt solid #999; }
        .crit-existenzkritisch { background: #fde8ec; border-color: #e11d48; color: #9f1239; }
        .crit-hoch { background: #fff4e5; border-color: #d97706; color: #92400e; }
        .empty { color: #888; font-style: italic; font-size: 9pt; }
        .toolbar { max-width: 190mm; margin: 6mm auto; display: flex; justify-content: space-between; gap: 8px; }
        .btn { font: inherit; padding: 8px 14px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; cursor: pointer; text-decoration: none; color: #111; }
        .btn.primary { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .footer-note { margin-top: 8mm; padding-top: 2mm; border-top: 0.5pt solid #ccc; font-size: 8pt; color: #666; }
        @media print {
            body { background: #fff; }
            .page { max-width: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @unless ($isPdf ?? false)
        <div class="toolbar no-print">
            <a class="btn" href="{{ route('business-processes.index') }}">&larr; Zurück</a>
            <button class="btn primary" onclick="window.print()">Als PDF speichern / Drucken</button>
        </div>
    @endunless

    <div class="page">
        <h1>Audit-/Governance-Bericht</h1>
        <p class="muted">{{ $company->name }} &mdash; Business-Impact-Analyse (BIA) mit verknüpften Risiken, Maßnahmen und Offenen Punkten.</p>
        <p class="small muted">Version {{ $versionString ?? '—' }} &middot; Stand: {{ $generatedAt->format('d.m.Y H:i') }} Uhr &middot; Umfang: {{ $processes->count() }} Geschäftsprozess(e). Dieser Bericht ergänzt das Ernstfall-Handbuch um die ausführliche Governance-Sicht (BSI 200-4, NIS2 Art. 21).</p>

        @if ($complianceReport)
            <h2>Reifegrad / Überblick</h2>
            <p class="small muted">Gewichteter Gesamt-Reifegrad des BCMS über alle Prüfpunkte (BSI 200-4 / NIS2). Details im Compliance-Dashboard.</p>
            <table class="meta-table">
                <tr>
                    <th style="width: 40%;">Gesamt-Reifegrad</th>
                    <td><strong>{{ $complianceReport->score() }} / 100</strong> &mdash; {{ $complianceReport->readinessLabel() }}</td>
                </tr>
                @foreach ($complianceReport->categories as $categoryReport)
                    @if ($categoryReport->totalCounted() > 0)
                        <tr>
                            <th>{{ $categoryReport->category->label() }}</th>
                            <td>{{ $categoryReport->score() }} / 100 &middot; {{ $categoryReport->passCount() }}/{{ $categoryReport->totalCounted() }} Prüfpunkte erfüllt</td>
                        </tr>
                    @endif
                @endforeach
            </table>

            @php($openChecks = collect($complianceReport->items)->filter(fn ($e) => $e['result']->isCounted() && $e['result']->score < 100)->sortBy(fn ($e) => $e['result']->score)->values())
            @if ($openChecks->isNotEmpty())
                <h3>Handlungsbedarf — schwächste Prüfpunkte</h3>
                <p class="small muted">Prüfpunkte, die noch nicht voll erfüllt sind (schwächste zuerst). Diese Liste ist die Maßnahmenagenda für das nächste Management-Review.</p>
                <table>
                    <thead><tr><th style="width: 30%;">Prüfpunkt</th><th>Score</th><th>Befund / nächster Schritt</th></tr></thead>
                    <tbody>
                        @foreach ($openChecks as $entry)
                            <tr>
                                <td><strong>{{ $entry['check']->label }}</strong><div class="small">{{ $entry['check']->category->label() }}</div></td>
                                <td>{{ $entry['result']->score }}/100</td>
                                <td class="small">{{ $entry['result']->message }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif

        <h2>Geschäftsprozesse</h2>
        @forelse ($processes as $process)
            <div class="process">
                <h3 style="text-transform:none; font-size:12pt; color:#1a1a1a; letter-spacing:0;">
                    {{ $process->name }}
                    <span class="crit crit-{{ $process->criticality->value }}">{{ $process->criticality->label() }}</span>
                </h3>
                @if ($process->description)<p class="small">{{ $process->description }}</p>@endif

                <table>
                    <tbody>
                        <tr><td class="k">MTPD / RTO / RPO</td><td>{{ \App\Support\Duration::inHours($process->mtpd_minutes) ?? '—' }} / {{ \App\Support\Duration::inHours($process->rto_minutes) ?? '—' }} / {{ \App\Support\Duration::inHours($process->rpo_minutes) ?? '—' }}</td></tr>
                        <tr><td class="k">Abhängige Systeme</td><td>{{ $process->systems->pluck('name')->join(', ') ?: '—' }}</td></tr>
                        <tr><td class="k">Ersatzprozess</td><td>{{ $process->fallback_process ?: '—' }}</td></tr>
                        <tr><td class="k">Verantwortlich</td><td>{{ $process->responsible?->fullName() ?? ($process->responsibleRole ? 'Rolle: '.$process->responsibleRole->name : '—') }}</td></tr>
                        @if ($process->peak_times)<tr><td class="k">Stoßzeiten</td><td>{{ $process->peak_times }}</td></tr>@endif
                        <tr><td class="k">Letzte / nächste Prüfung</td><td>{{ $process->last_reviewed_at?->format('d.m.Y') ?? '—' }} / {{ $process->next_review_at?->format('d.m.Y') ?? '—' }}{{ $process->isReviewOverdue() ? ' (überfällig)' : '' }}</td></tr>
                    </tbody>
                </table>

                <h3>Risiken</h3>
                @if ($process->risks->isEmpty())
                    <p class="empty">Keine verknüpften Risiken.</p>
                @else
                    <table>
                        <thead><tr><th>Risiko</th><th>Kategorie</th><th>Score</th><th>Status</th><th>Eigentümer</th></tr></thead>
                        <tbody>
                            @foreach ($process->risks as $risk)
                                <tr>
                                    <td>{{ $risk->title }}</td>
                                    <td>{{ $risk->category->label() }}</td>
                                    <td>{{ $risk->score() }}@if ($risk->residualScore() !== null) → {{ $risk->residualScore() }}@endif</td>
                                    <td>{{ $risk->status->label() }}</td>
                                    <td>{{ $risk->owner?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <h3>Maßnahmen</h3>
                @if ($process->preventiveMeasures->isEmpty())
                    <p class="empty">Keine verknüpften Maßnahmen.</p>
                @else
                    <table>
                        <thead><tr><th>Maßnahme</th><th>Status</th><th>Nächste Fälligkeit</th><th>Verantwortlich</th></tr></thead>
                        <tbody>
                            @foreach ($process->preventiveMeasures as $measure)
                                <tr>
                                    <td>{{ $measure->title }}</td>
                                    <td>{{ $measure->status->label() }}</td>
                                    <td>{{ $measure->next_due_at?->format('d.m.Y') ?? '—' }}</td>
                                    <td>{{ $measure->responsible?->fullName() ?? ($measure->responsibleRole ? 'Rolle: '.$measure->responsibleRole->name : '—') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <h3>Offene Punkte</h3>
                @if ($process->openItems->isEmpty())
                    <p class="empty">Keine verknüpften Offenen Punkte.</p>
                @else
                    <table>
                        <thead><tr><th>Thema</th><th>Status</th><th>Frist</th><th>Wiedervorlage</th><th>Überführt</th></tr></thead>
                        <tbody>
                            @foreach ($process->openItems as $item)
                                <tr>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ $item->status->label() }}</td>
                                    <td>{{ $item->due_at?->format('d.m.Y') ?? '—' }}</td>
                                    <td>{{ $item->review_at?->format('d.m.Y') ?? '—' }}</td>
                                    <td>{{ $item->conversion?->shortLabel() ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @empty
            <p class="empty">Es sind noch keine Geschäftsprozesse erfasst.</p>
        @endforelse

        @if ($aiSystems->isNotEmpty())
            <h2>KI-Systeme (EU-KI-Verordnung)</h2>
            <p class="small muted">Prüfpunkte: alle KI-Systeme erfasst und eingestuft? Keine verbotenen Praktiken? Menschliche Aufsicht dokumentiert? Konformität/Registrierung bei Hochrisiko? Prüftermine aktuell? Schwerwiegende Vorfälle nach Art. 73 gemeldet?</p>
            @php($openArt73 = $aiSystems->sum('open_art73_count'))
            @if ($openArt73 > 0)
                <p class="small"><strong>Achtung:</strong> {{ $openArt73 }} als meldepflichtig (Art. 73) markierte(r) KI-Vorfall/-Vorfälle noch ohne dokumentierte Meldung an die Marktüberwachungsbehörde.</p>
            @endif
            <table>
                <thead>
                    <tr>
                        <th>System</th>
                        <th>Rolle</th>
                        <th>Risikoklasse</th>
                        <th>Aufsicht / Konformität</th>
                        <th>Nächste Prüfung</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($aiSystems as $sys)
                        <tr>
                            <td>
                                <strong>{{ $sys->name }}</strong>
                                @if ($sys->provider_name)<br><span class="small">{{ $sys->provider_name }}</span>@endif
                                @if ($sys->responsibleRole)<br><span class="small">intern: {{ $sys->responsibleRole->name }}</span>@endif
                            </td>
                            <td>{{ $sys->role->label() }}</td>
                            <td>{{ $sys->risk_class->label() }}</td>
                            <td class="small">
                                {{ $sys->human_oversight ? 'Aufsicht dokumentiert' : 'Aufsicht offen' }}
                                @if ($sys->conformity_status)<br>{{ $sys->conformity_status }}@endif
                                @if ($sys->eu_db_registration)<br>EU-DB: {{ $sys->eu_db_registration }}@endif
                            </td>
                            <td>{{ $sys->next_review_at?->format('d.m.Y') ?? '—' }}@if ($sys->isReviewOverdue()) <strong>(überfällig)</strong>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($insurancePolicies->isNotEmpty())
            <h2>Versicherungen und Schadenabsicherung</h2>
            <p class="small muted">Prüfpunkte: Policen aktuell? Ansprechpartner hinterlegt? Schadenmeldeweg getestet? Deckung zu den Top-Risiken passend (Szenariobezug)? Nächste Prüfung der Versicherungsdaten terminiert?</p>
            <table>
                <thead>
                    <tr>
                        <th>Versicherer / Art</th>
                        <th>Laufzeit</th>
                        <th>Deckung / SB</th>
                        <th>Meldeweg getestet</th>
                        <th>Nächste Prüfung</th>
                        <th>Szenariobezug</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($insurancePolicies as $policy)
                        <tr>
                            <td>
                                <strong>{{ $policy->insurer }}</strong><br>
                                <span class="small">{{ $policy->type->label() }}</span>
                                @if ($policy->contact_name || $policy->responsibleRole)
                                    <br><span class="small">{{ $policy->contact_name ?: '—' }}@if ($policy->responsibleRole) · intern: {{ $policy->responsibleRole->name }}@endif</span>
                                @endif
                            </td>
                            <td>{{ $policy->valid_until?->format('d.m.Y') ?? '—' }}@if ($policy->isExpired()) <strong>(abgelaufen)</strong>@endif</td>
                            <td>{{ $policy->coverage_amount ?: '—' }}@if ($policy->deductible)<br><span class="small">SB {{ $policy->deductible }}</span>@endif</td>
                            <td>{{ $policy->claims_process_tested_at?->format('d.m.Y') ?? 'nicht getestet' }}</td>
                            <td>{{ $policy->next_review_at?->format('d.m.Y') ?? '—' }}@if ($policy->isReviewOverdue()) <strong>(überfällig)</strong>@endif</td>
                            <td class="small">{{ $policy->scenarios->pluck('name')->join(', ') ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($authorityContacts->isNotEmpty())
            <h2>Behörden, Meldestellen und externe Stellen</h2>
            <p class="small muted">Prüfpunkte: Sind die im Ernst-/Meldefall zuständigen Stellen erfasst — mit Anlass, Frist, Kontaktweg, zuständiger Rolle und passender Kommunikationsvorlage?</p>
            <table>
                <thead>
                    <tr><th>Stelle</th><th>Anlass</th><th>Frist</th><th>Kontaktweg</th><th>Rolle / Vorlage</th></tr>
                </thead>
                <tbody>
                    @foreach ($authorityContacts as $authority)
                        <tr>
                            <td><strong>{{ $authority->name }}</strong><div class="small">{{ $authority->type?->label() }}</div></td>
                            <td>{{ $authority->occasion ?: '—' }}</td>
                            <td>{{ $authority->deadline ?: '—' }}</td>
                            <td class="small">
                                @if ($authority->phone){{ $authority->phone }}@endif
                                @if ($authority->email)@if ($authority->phone) &middot; @endif{{ $authority->email }}@endif
                                @if ($authority->contact_way)@if ($authority->phone || $authority->email) &middot; @endif{{ $authority->contact_way }}@endif
                                @if (! $authority->phone && ! $authority->email && ! $authority->contact_way)—@endif
                            </td>
                            <td class="small">
                                {{ $authority->responsibleRole?->name ?? '—' }}
                                @if ($authority->communicationTemplate)<br>Vorlage: {{ $authority->communicationTemplate->name }}@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($trainingRecords->isNotEmpty())
            <h2>Schulungen und Awareness</h2>
            <p class="small muted">Prüfpunkte: Welche Schulungen sind geplant/durchgeführt? Wer wurde geschult? Wann ist die nächste Fälligkeit? Welche Nachweise liegen vor?</p>
            <table>
                <thead>
                    <tr><th>Thema</th><th>Person</th><th>Status</th><th>Nächste Fälligkeit</th><th>Nachweis & abgeleitete Maßnahmen</th></tr>
                </thead>
                <tbody>
                    @foreach ($trainingRecords as $training)
                        <tr>
                            <td><strong>{{ $training->topic }}</strong><div class="small">{{ $training->type?->label() }}</div></td>
                            <td>{{ $training->employee?->fullName() ?? '—' }}@if ($training->responsible)<div class="small">verantw.: {{ $training->responsible->fullName() }}</div>@endif</td>
                            <td>{{ $training->completed_at ? 'durchgeführt am '.$training->completed_at->format('d.m.Y') : 'geplant' }}</td>
                            <td>{{ $training->next_due_at?->format('d.m.Y') ?? '—' }}@if ($training->isOverdue()) <strong>(überfällig)</strong>@endif</td>
                            <td class="small">
                                {{ $training->notes ?: '—' }}
                                @if ($training->openItems->isNotEmpty())
                                    <div>Maßnahmen:
                                        @foreach ($training->openItems as $item)
                                            <div>&rarr; {{ $item->title }} ({{ $item->status->label() }})</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($systemTasks->isNotEmpty())
            <h2>Aufgaben und Nachverfolgung</h2>
            <p class="small muted">Prüfpunkte: offene, überfällige und im Berichtszeitraum ({{ $periodStart->format('d.m.Y') }}–{{ $generatedAt->format('d.m.Y') }}) erledigte Aufgaben; Aufgaben mit Risikobezug.</p>
            <table>
                <thead>
                    <tr><th>Aufgabe</th><th>System</th><th>Zuständig</th><th>Fällig</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($systemTasks as $task)
                        <tr>
                            <td>
                                <strong>{{ $task->title }}</strong>
                                @if ($task->riskMitigation) <span class="small">· aus Risiko</span>@endif
                            </td>
                            <td>{{ $task->system?->name ?? '—' }}</td>
                            <td class="small">{{ $task->assignees->pluck('first_name')->merge($task->roleAssignees->pluck('name'))->join(', ') ?: '—' }}</td>
                            <td>{{ $task->due_date?->format('d.m.Y') ?? '—' }}</td>
                            <td>
                                @if ($task->isDone())
                                    erledigt{{ $task->completed_at ? ' am '.$task->completed_at->format('d.m.Y') : '' }}
                                @elseif ($task->isOverdue())
                                    <strong>überfällig</strong>
                                @else
                                    offen
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($lessonsLearned->isNotEmpty())
            <h2>Lessons Learned</h2>
            <p class="small muted">Erkenntnisse aus Vorfällen und Übungen samt abgeleiteter Maßnahmen (offene/überfällige Maßnahmen sind nachzuverfolgen).</p>
            @foreach ($lessonsLearned as $lesson)
                <table class="meta-table" style="margin-bottom: 4px;">
                    <tr><th style="width: 22%;">Erkenntnis</th><td><strong>{{ $lesson->title }}</strong>@if ($lesson->finalized_at) <span class="small">· finalisiert {{ $lesson->finalized_at->format('d.m.Y') }}</span>@else <span class="small">· Entwurf</span>@endif</td></tr>
                    @if ($lesson->root_cause)<tr><th>Ursache</th><td>{{ $lesson->root_cause }}</td></tr>@endif
                    @if ($lesson->what_went_poorly)<tr><th>Verbesserungsbedarf</th><td>{{ $lesson->what_went_poorly }}</td></tr>@endif
                </table>
                @if ($lesson->actionItems->isNotEmpty())
                    <table>
                        <thead><tr><th>Abgeleitete Maßnahme</th><th>Zuständig</th><th>Frist</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($lesson->actionItems as $action)
                                <tr>
                                    <td>{{ $action->description }}</td>
                                    <td class="small">{{ $action->responsibleEmployee?->fullName() ?? '—' }}</td>
                                    <td>{{ $action->due_date?->format('d.m.Y') ?? '—' }}</td>
                                    <td>{{ $action->status->label() }}@if ($action->isOverdue()) <strong>(überfällig)</strong>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach
        @endif

        <h2>Management Review / BCMS-Governance</h2>
        <p class="small muted">Konsolidierter Governance-Überblick der Geschäftsführung: geprüfte Risiken, offene Punkte, überfällige Maßnahmen, Tests, Lessons Learned, Schulungsstand, Entscheidungen und nächste Review-Fälligkeit.</p>
        <table class="meta-table">
            <tr><th style="width: 40%;">Erfasste Risiken</th><td>{{ $governanceSnapshot['risksTotal'] }}</td></tr>
            <tr><th>Offene Punkte (offen / davon überfällig)</th><td>{{ $governanceSnapshot['openItemsOpen'] }} / {{ $governanceSnapshot['openItemsOverdue'] }}</td></tr>
            <tr><th>Überfällige Präventionsmaßnahmen</th><td>{{ $governanceSnapshot['measuresOverdue'] }}</td></tr>
            <tr><th>Tests / Übungen (gesamt / davon überfällig)</th><td>{{ $governanceSnapshot['testsTotal'] }} / {{ $governanceSnapshot['testsOverdue'] }}</td></tr>
            <tr><th>Lessons Learned (offene Maßnahmen)</th><td>{{ $governanceSnapshot['lessonsTotal'] }} ({{ $governanceSnapshot['lessonsOpenActions'] }})</td></tr>
            <tr><th>Schulungen (durchgeführt / geplant / überfällig)</th><td>{{ $governanceSnapshot['trainingDone'] }} / {{ $governanceSnapshot['trainingPlanned'] }} / {{ $governanceSnapshot['trainingOverdue'] }}</td></tr>
            <tr><th>Aufgaben (offen / überfällig / im Zeitraum erledigt)</th><td>{{ $governanceSnapshot['tasksOpen'] }} / {{ $governanceSnapshot['tasksOverdue'] }} / {{ $governanceSnapshot['tasksDoneInPeriod'] }}</td></tr>
            <tr><th>Nächste Management-Review fällig</th><td>{{ $governanceSnapshot['nextReviewAt']?->format('d.m.Y') ?? '—' }}</td></tr>
        </table>

        @if ($managementReviews->isNotEmpty())
            <h3>Durchgeführte Management-Reviews</h3>
            @foreach ($managementReviews as $review)
                <table class="meta-table" style="margin-bottom: 4px;">
                    <tr><th style="width: 22%;">Review</th><td><strong>{{ $review->title }}</strong> · {{ $review->review_date?->format('d.m.Y') ?? '—' }}@if ($review->conducted_by) · Leitung: {{ $review->conducted_by }}@endif</td></tr>
                    @if ($review->participants)<tr><th>Teilnehmer</th><td>{{ $review->participants }}</td></tr>@endif
                    @if ($review->summary)<tr><th>Zusammenfassung</th><td>{{ $review->summary }}</td></tr>@endif
                    @if ($review->decisions)<tr><th>Entscheidungen der GF</th><td>{{ $review->decisions }}</td></tr>@endif
                    <tr><th>Nächste Review</th><td>{{ $review->next_review_at?->format('d.m.Y') ?? '—' }}@if ($review->isFollowUpOverdue()) <strong>(überfällig)</strong>@endif</td></tr>
                </table>
            @endforeach
        @else
            <p class="small"><em>Noch kein Management-Review dokumentiert.</em></p>
        @endif

        @if ($unlinkedRisks->isNotEmpty() || $unlinkedMeasures->isNotEmpty() || $unlinkedOpenItems->isNotEmpty())
            <h2>Anhang: Nicht zugeordnet</h2>
            <p class="small muted">Governance-Einträge ohne Zuordnung zu einem Geschäftsprozess. Für die Vollständigkeit der BIA sollten diese noch einem Prozess zugeordnet werden.</p>

            @if ($unlinkedRisks->isNotEmpty())
                <h3>Risiken ohne Prozessbezug</h3>
                <table>
                    <thead><tr><th>Risiko</th><th>Kategorie</th><th>Score</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($unlinkedRisks as $risk)
                            <tr><td>{{ $risk->title }}</td><td>{{ $risk->category->label() }}</td><td>{{ $risk->score() }}</td><td>{{ $risk->status->label() }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($unlinkedMeasures->isNotEmpty())
                <h3>Maßnahmen ohne Prozessbezug</h3>
                <table>
                    <thead><tr><th>Maßnahme</th><th>Status</th><th>Nächste Fälligkeit</th></tr></thead>
                    <tbody>
                        @foreach ($unlinkedMeasures as $measure)
                            <tr><td>{{ $measure->title }}</td><td>{{ $measure->status->label() }}</td><td>{{ $measure->next_due_at?->format('d.m.Y') ?? '—' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($unlinkedOpenItems->isNotEmpty())
                <h3>Offene Punkte ohne Prozessbezug</h3>
                <table>
                    <thead><tr><th>Thema</th><th>Status</th><th>Frist</th></tr></thead>
                    <tbody>
                        @foreach ($unlinkedOpenItems as $item)
                            <tr><td>{{ $item->title }}</td><td>{{ $item->status->label() }}</td><td>{{ $item->due_at?->format('d.m.Y') ?? '—' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif

        <div class="footer-note">
            {{ $company->name }} &mdash; Audit-/Governance-Bericht &mdash; Version {{ $versionString ?? '—' }} &mdash; Stand {{ $generatedAt->format('d.m.Y H:i') }} Uhr. Erzeugt aus dem digitalen Notfallhandbuch (PlanB).
        </div>
    </div>

    <script>
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        }
    </script>
</body>
</html>
