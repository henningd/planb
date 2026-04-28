<?php

namespace App\Support\Marketing;

/**
 * Marketing-Inhalte für die sechs Detailseiten unter /funktionen/{slug}.
 * Reine Texte/Inhalts-Daten — gehört bewusst nicht in das Settings-System,
 * weil es kein konfigurierbarer State pro Mandant ist, sondern produkt-
 * weite Aussagen zu Funktionen.
 */
class FeatureCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'compliance-dashboard' => [
                'slug' => 'compliance-dashboard',
                'title' => 'Compliance-Dashboard',
                'tagline' => 'Reifegrad nach BSI 200-4 und NIS2 — automatisch errechnet aus echten Daten.',
                'icon_color' => 'indigo',
                'lead' => 'Statt Selbstauskunft ein gewichteter Score über elf Pflicht-Checks: Pflichtrollen besetzt, Systeme klassifiziert, Notfall-Tests aktuell, kritische Risiken behandelt. Ergebnis: ein Wert von 0–100, ein Trend über 30 Tage und drei priorisierte Aktionen mit dem größten Hebel.',
                'sections' => [
                    [
                        'heading' => 'Was darin steckt',
                        'body' => 'Elf Checks aus den Bereichen Organisation, Systeme & Abhängigkeiten, Tests & Übungen, Dokumentation. Jeder Check trägt mit einem Gewicht zum Gesamt-Score bei. Sind Daten bereits da (z. B. Rolle besetzt, Test durchgeführt), greift das automatisch — ohne dass jemand auf einen Button drückt.',
                    ],
                    [
                        'heading' => 'Bezug zu Standards',
                        'body' => 'Die Checks sind an BSI 200-4 (Notfallmanagement) und NIS2-Anforderungen orientiert: Krisenorganisation, Asset-Inventar, Tests, Dokumentation. Für ISO 27001 ergänzt das Risiko-Register die fehlende Säule.',
                    ],
                    [
                        'heading' => 'Top-Aktionen',
                        'body' => 'Aus den nicht erfüllten Checks errechnet die Plattform den potenziellen Score-Gewinn (Gewicht × verlorene Punkte) und zeigt die drei wirksamsten nächsten Schritte. So weißt du, wo Aufwand sich am meisten lohnt.',
                    ],
                    [
                        'heading' => 'Trend über 30 Tage',
                        'body' => 'Tägliche Snapshots zeigen, wie sich die Reife entwickelt. Plötzliche Einbrüche (z. B. weil eine Pflichtrolle frei wurde, ein Test überfällig ist) werden sichtbar.',
                    ],
                ],
                'screenshots' => [
                    ['file' => 'compliance-overview.png', 'caption' => 'Kachelübersicht mit aktuellem Score, Reifegrad-Label und Status-Counts.'],
                    ['file' => 'compliance-trend.png', 'caption' => '30-Tage-Verlauf des Compliance-Scores — ein steigender Trend ist auch für Auditoren ein klares Signal.'],
                ],
                'demo_hint' => 'Mit jedem Tag wächst der Verlauf automatisch. Veränderungen am Reifegrad lassen sich rückwirkend nachvollziehen, ohne dass jemand manuell Bericht führt.',
                'cta_label' => 'Compliance live ansehen',
                'cta_route' => 'compliance.index',
            ],

            'risiko-register' => [
                'slug' => 'risiko-register',
                'title' => 'Risiko-Register',
                'tagline' => 'ISO-27001-konformes Register mit 5×5-Heatmap, Maßnahmen und Restrisiko.',
                'icon_color' => 'rose',
                'lead' => 'Risiken werden bewertet (Wahrscheinlichkeit × Schaden, jeweils 1–5), behandelt (Maßnahmen mit Status), und das Restrisiko nach Umsetzung wird separat geführt. Jedes Risiko hat einen Eigentümer und einen Review-Termin — überfällige Reviews färben sich rot.',
                'sections' => [
                    [
                        'heading' => '5×5-Heatmap',
                        'body' => 'Die Heatmap zeigt auf einen Blick, wo die Risiken liegen: Wahrscheinlichkeit auf der y-Achse, Schaden auf der x-Achse, Score = Produkt. Klick auf eine Zelle filtert die Liste darunter auf genau diese Konstellation.',
                    ],
                    [
                        'heading' => 'Maßnahmen mit Status-Zyklus',
                        'body' => 'Pro Risiko mehrere Maßnahmen mit Verantwortlichem (Mitarbeiter), Zieldatum, Status (geplant → in Umsetzung → umgesetzt → verifiziert). Pro Klick auf das Status-Badge geht die Maßnahme einen Schritt weiter.',
                    ],
                    [
                        'heading' => 'Aufgaben-Inbox-Brücke',
                        'body' => 'Maßnahmen können per Klick als System-Aufgabe materialisiert werden — sie tauchen dann in der zentralen Aufgaben-Inbox auf, mit Bezug zurück zum Risiko und zum verknüpften System. Voraussetzung: das Risiko ist mit mindestens einem System verknüpft.',
                    ],
                    [
                        'heading' => 'Compliance-Wirkung',
                        'body' => 'Zwei der elf Compliance-Checks beziehen sich auf das Risiko-Register: kritische Risiken (Score ≥ 15) müssen behandelt sein, und kein Risiko darf einen überfälligen Review-Termin haben.',
                    ],
                ],
                'screenshots' => [
                    ['file' => 'risks-heatmap.png', 'caption' => '5×5-Heatmap zeigt auf einen Blick, wo die wirklich kritischen Risiken liegen.'],
                    ['file' => 'risks-detail.png', 'caption' => 'Risiko-Detail mit Maßnahmen, verknüpften Systemen, Eigentümer und Restrisiko nach Behandlung.'],
                ],
                'demo_hint' => 'Aus einem typischen Mittelständler-Profil entstehen schnell sechs bis zehn relevante Risiken — vom Ransomware-Vorfall über Stromausfall bis zum Ausfall des einzigen IT-Dienstleisters.',
                'cta_label' => 'Risiko-Register live ansehen',
                'cta_route' => 'risks.index',
            ],

            'lessons-learned' => [
                'slug' => 'lessons-learned',
                'title' => 'Lessons Learned',
                'tagline' => 'Strukturierte After-Action-Auswertung pro Vorfall und Übung.',
                'icon_color' => 'violet',
                'lead' => 'Nach jeder Übung und jedem Ernstfall: Was war die Ursache? Was lief gut? Was nicht? Welche konkreten Maßnahmen folgen daraus, mit Verantwortlichem und Fälligkeit? Lessons können einer Handbuch-Version zugeordnet werden — so ist nachweisbar, dass Erkenntnisse einfließen.',
                'sections' => [
                    [
                        'heading' => 'Pro Vorfall oder Übung',
                        'body' => 'Eine Lesson Learned wird entweder einem Incident oder einem Scenario-Run zugeordnet, oder als „freie" Auswertung erfasst. Drei Strukturfelder (Ursache, Was lief gut, Was nicht) plus beliebig viele Action-Items.',
                    ],
                    [
                        'heading' => 'Action-Items',
                        'body' => 'Jede Maßnahme bekommt einen Verantwortlichen aus der Mitarbeiter-Liste, ein Fälligkeitsdatum und einen Status (offen → in Bearbeitung → erledigt → verworfen). Überfällige Items werden farbig hervorgehoben.',
                    ],
                    [
                        'heading' => 'Verknüpfung mit Versionen',
                        'body' => 'Lessons können einer Handbuch-Version zugeordnet werden, um zu dokumentieren, dass eine Erkenntnis in eine konkrete Iteration eingeflossen ist. In der Versionshistorie zeigt ein Badge, wie viele Lessons zu jeder Version gehören.',
                    ],
                    [
                        'heading' => 'Audit-Wert',
                        'body' => 'Versicherer und Wirtschaftsprüfer fragen: „Wie haben Sie aus dem Vorfall gelernt?" Die Lessons-Learned-Domäne liefert die strukturierte Antwort — inklusive offener und erledigter Folge-Maßnahmen.',
                    ],
                ],
                'screenshots' => [
                    ['file' => 'lessons-list.png', 'caption' => 'Übersicht aller Auswertungen mit Bezug auf Übung, Vorfall oder Handbuch-Version.'],
                    ['file' => 'lessons-detail.png', 'caption' => 'Detail-Ansicht mit Ursachenanalyse und Maßnahmen in unterschiedlichen Bearbeitungsstati.'],
                ],
                'demo_hint' => 'Typische Auswertungen: Tabletop-Übungen mit zu langsamer Eskalation, Phishing-Wellen mit hoher Klickrate, Stromausfall-Übungen mit zu schwacher USV — und konkrete Folgemaßnahmen daraus.',
                'cta_label' => 'Lessons Learned live ansehen',
                'cta_route' => 'lessons-learned.index',
            ],

            'war-room' => [
                'slug' => 'war-room',
                'title' => 'Live-Krisenstab (War-Room)',
                'tagline' => 'Echtzeit-Sicht auf den laufenden Krisenstab — mehrere Personen, ein gemeinsames Bild.',
                'icon_color' => 'amber',
                'lead' => 'Wenn ein Scenario-Run als „echte Lage" gestartet wird, sehen alle Beteiligten in Echtzeit, wer welchen Schritt erledigt hat. Anwesenheits-Liste oben, sofortige Updates auf abgehakte Schritte und gespeicherte Notizen — ohne Reload.',
                'sections' => [
                    [
                        'heading' => 'Presence-Channel',
                        'body' => 'Wer gerade auf dem Run-Detail ist, erscheint mit Avatar oder Initialen oben in der Präsenz-Leiste. Der Krisenstab sieht auf einen Blick, wer mit anpackt und wer noch ausgesteuert ist.',
                    ],
                    [
                        'heading' => 'Live-Step-Updates',
                        'body' => 'Klickt einer der Anwesenden einen Schritt als erledigt, wird das innerhalb einer Sekunde bei allen anderen sichtbar — kurzes Highlight, neuer Status. Notizen am Schritt werden ebenso live verteilt.',
                    ],
                    [
                        'heading' => 'Technik',
                        'body' => 'Laravel Reverb (WebSocket-Server) sendet Events synchron beim Speichern, Echo + Pusher-Protokoll im Frontend empfangen sie. Authentifizierung pro Channel: nur Mitglieder des Mandanten-Teams haben Zugriff.',
                    ],
                    [
                        'heading' => 'Was es nicht ist',
                        'body' => 'Kein Chat, kein Video. Der War-Room ergänzt Slack/Teams nicht, sondern ist die operative Status-Sicht parallel dazu. Slack/Teams-Versand wird über die Krisen-Kommunikations-Vorlagen abgedeckt.',
                    ],
                ],
                'screenshots' => [
                    ['file' => 'war-room.png', 'caption' => 'Run-Detail mit Anwesenheits-Banner und Live-Step-Liste — der Krisenstab arbeitet sichtbar parallel.'],
                ],
                'demo_hint' => 'Sobald ein Szenario-Lauf als „echte Lage" gestartet ist, sehen alle Beteiligten dieselbe Sicht in Echtzeit — wer wo gerade arbeitet und welche Schritte abgehakt sind.',
                'cta_label' => 'Scenario-Runs live ansehen',
                'cta_route' => 'scenario-runs.index',
            ],

            'audit-export' => [
                'slug' => 'audit-export',
                'title' => 'Audit-Log und Mandanten-Export',
                'tagline' => 'Lückenlose Änderungshistorie und vollständiger DSGVO-Auskunfts-Export auf Klick.',
                'icon_color' => 'emerald',
                'lead' => 'Jede Änderung an audit-relevanten Daten landet automatisch im Audit-Log: wer hat wann was geändert, mit Vorher-Nachher-Werten. Filterbar, exportierbar als CSV oder PDF, und im großen Stil als vollständiges Mandanten-ZIP.',
                'sections' => [
                    [
                        'heading' => 'Was geloggt wird',
                        'body' => 'Mandanten-Daten mit dem `LogsAudit`-Trait — Mitarbeiter, Systeme, Versicherungen, Risiken, Lessons Learned, Tokens, Branding-Anpassungen, Versionsfreigaben. Pro Eintrag: Zeitpunkt, User, Objekt, Aktion (created/updated/deleted), Änderungen als JSON-Diff.',
                    ],
                    [
                        'heading' => 'Filter und Export',
                        'body' => 'Im Audit-Log-Viewer filterst du nach Datumsbereich und Aktion. Mit zwei Knöpfen exportierst du das Ergebnis als CSV (für Tabellenkalkulation) oder PDF (für die Akte beim Auditor).',
                    ],
                    [
                        'heading' => 'Vollständiges Mandanten-ZIP',
                        'body' => 'Der „Vollständiges Archiv (ZIP)"-Knopf in den System-Settings liefert ein einziges ZIP mit: alle Stammdaten als JSON, das komplette Audit-Log als CSV, alle revisionssicheren Handbuch-PDFs, eine README. Genau das, was DSGVO-Auskunftsanfragen verlangen.',
                    ],
                    [
                        'heading' => 'Aufbewahrung',
                        'body' => 'Pro Mandant einstellbar — von „unbegrenzt" bis „nach X Tagen löschen". Default ist unbegrenzt; in regulierten Branchen meist auf 7 oder 10 Jahre gesetzt.',
                    ],
                ],
                'screenshots' => [
                    ['file' => 'audit-log.png', 'caption' => 'Audit-Log mit Datumsfilter und Aktions-Filter — jede sicherheitsrelevante Änderung mit Vorher-/Nachher-Werten.'],
                    ['file' => 'audit-export-zip.png', 'caption' => '„Vollständiges Archiv (ZIP)"-Knopf in den System-Settings — ein Klick liefert die komplette DSGVO-Auskunft.'],
                ],
                'demo_hint' => 'Im laufenden Betrieb sammeln sich schnell hunderte Audit-Einträge an — Filter und Export sind genau für diesen Fall gebaut.',
                'cta_label' => 'Audit-Log live ansehen',
                'cta_route' => 'audit-log.index',
            ],

            'monitoring' => [
                'slug' => 'monitoring',
                'title' => 'Monitoring-Integration',
                'tagline' => 'Zabbix oder Prometheus → automatischer Incident in PlanB, Eskalation greift sofort.',
                'icon_color' => 'sky',
                'lead' => 'Externe Monitoring-Tools (Zabbix, Prometheus Alertmanager) feuern an einen authentifizierten Webhook der Plattform. Bei kritischer Severity wird automatisch ein Incident angelegt und mit dem richtigen System verknüpft — die Eskalations-Kette läuft, ohne dass jemand zur Tastatur greifen muss.',
                'sections' => [
                    [
                        'heading' => 'Auth über API-Tokens',
                        'body' => 'Pro Mandant erstellst du Bearer-Tokens (gehasht gespeichert, Klartext nur einmalig sichtbar). Tokens haben Scopes (z. B. monitoring.write), können widerrufen werden, last_used_at wird automatisch aktualisiert.',
                    ],
                    [
                        'heading' => 'System-Mapping',
                        'body' => 'Pro System pflegst du eine Liste von Hostnamen oder Labels (z. B. srv-prod-01, mail.local). Wenn ein Alert einen dieser Begriffe in Host oder Subject trägt, wird er automatisch dem System zugeordnet.',
                    ],
                    [
                        'heading' => 'Severity-Schwelle',
                        'body' => 'Nur Alerts mit Severity high, disaster, critical oder page lösen einen Incident aus. Information und Warning werden geloggt, aber nicht eskaliert — sonst wäre der Krisenstab im Lärm verloren.',
                    ],
                    [
                        'heading' => 'Idempotenz und Folge-Alerts',
                        'body' => 'Jeder Alert hat einen Idempotency-Key (Zabbix event_id, Prometheus fingerprint). Doppelte Calls erzeugen keine Duplikate. Folge-Alerts für dasselbe System innerhalb 24h hängen sich an den offenen Incident an — eine Notiz pro Folgemeldung.',
                    ],
                    [
                        'heading' => 'Outbound für Krisen-Kommunikation',
                        'body' => 'Vorlagen mit Kanal Slack, Microsoft Teams oder E-Mail werden direkt an den hinterlegten Channel gepostet. Audit-Spur: pro Versand wird festgehalten, wer wann was bekommen hat.',
                    ],
                ],
                'screenshots' => [
                    ['file' => 'monitoring-tokens.png', 'caption' => 'API-Tokens-Verwaltung mit aktivem und widerrufenem Token plus Webhook-Endpunkten.'],
                    ['file' => 'monitoring-alerts.png', 'caption' => 'Liste der letzten eingegangenen Alerts: Quelle, Status, mappendes System, Verarbeitung.'],
                ],
                'demo_hint' => 'Eingehende Alarme werden je nach Severity, Host-Mapping und Status entweder zu einem Incident eskaliert, an einen offenen Incident angehängt, oder als Information geloggt — das vollständige Verarbeitungs-Log ist jederzeit einsehbar.',
                'cta_label' => 'API & Webhooks live ansehen',
                'cta_route' => 'api-tokens.index',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }
}
