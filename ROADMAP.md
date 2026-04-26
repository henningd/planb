# PlanB Roadmap

Lebende Liste von Verbesserungsideen, sortiert nach Nutzen × Aufwand. Quick Wins
sind detailliert ausgeplant, der Rest umrissen — wir vertiefen die nächsten
Punkte, sobald wir an sie gehen.

---

## 🔥 Quick Wins (sofort wertvoll, kleiner Aufwand)

### QW1 — Dashboard-„Heute"-Widget

**Warum:** Heute landet der User im Dashboard und sieht Stammdaten-Counts. Er
müsste sofort sehen, was *ihn heute betrifft*.

**Scope:**
- Neue Sektion oben auf `pages/⚡dashboard.blade.php`: Liste der „heute zu
  erledigenden" Punkte des aktuellen Mandanten.
- Datenquellen (alle company-scoped):
  - `HandbookTest` mit `next_due_at <= today` (oder in 14 Tagen fällig)
  - `EmergencyResource` mit `next_check_at <= today + 14`
  - `ScenarioRun` ohne `ended_at` (= aktive Lage)
  - `IncidentReport` ohne abgeschlossene Pflicht-Meldungen (`obligations`
    mit `reported_at IS NULL`)
  - `HandbookVersion` mit `approved_at IS NULL` (Freigabe offen)
  - Optional: Mitarbeiter ohne aktive Krisenrollen-Vertretung
- Eine `App\Support\DashboardActions`-Klasse als Single-Source-of-Truth, gibt
  ein Array `[ ['type'=>'test', 'label'=>'…', 'due_at'=>…, 'route'=>…], …]`
  zurück, sortiert nach Dringlichkeit (überfällig > heute > bald).
- UI: kompakte Karten-Liste mit farblichen Dringlichkeits-Badges (rose/amber/zinc),
  Klick führt direkt zum jeweiligen Detail.

**Affected Files:**
- `resources/views/pages/⚡dashboard.blade.php` (Section + Computed)
- `app/Support/DashboardActions.php` (neu)

**Tests:**
- `tests/Feature/DashboardActionsTest.php`: Items werden korrekt erfasst,
  Sortierung stimmt, Mandanten-Isolation greift.

---

### QW2 — Reminder-Cron für anstehende Tests/Verträge

**Warum:** `next_due_at` ist da, aber niemand wird gemailt. Plan und Realität
driften auseinander.

**Scope:**
- Neuer Artisan-Command `app:send-due-reminders` (täglich 07:00 via
  `routes/console.php`).
- Iteriert alle Companies und versendet pro Mandant:
  - eine Mail pro `HandbookTest`, der in 14 Tagen fällig wird (an
    `responsible.email`)
  - eine Mail pro `EmergencyResource`, der in 14 Tagen geprüft werden muss
    (an die im Mandanten hinterlegten Krisenrollen-Verantwortlichen, falls
    keine Person direkt zugewiesen)
- Idempotent: speichert `last_reminder_sent_at` auf den jeweiligen Models, um
  Doppel-Versand zu verhindern (Migration ergänzt das Feld).
- Mailable `App\Mail\TestDueReminder` und `ResourceDueReminder` mit
  Markdown-Templates (Inhalt: was, wann, Link, Testanleitung).

**Affected Files:**
- `app/Console/Commands/SendDueReminders.php` (neu)
- `app/Mail/TestDueReminder.php` + `ResourceDueReminder.php` (neu)
- `resources/views/emails/test-due-reminder.blade.php` + `resource-…` (neu)
- `database/migrations/…_add_last_reminder_sent_at_to_handbook_tests…` (neu)
- `database/migrations/…_add_last_reminder_sent_at_to_emergency_resources…`
- `routes/console.php`

**Tests:**
- `tests/Feature/SendDueRemindersTest.php`: Mail::fake, prüft Empfänger,
  Idempotenz, Mandanten-Isolation, Stichtagslogik.

---

### QW3 — Quick-Action „Notfall jetzt!"

**Warum:** Im Ernstfall sind 4 Klicks zum richtigen Playbook 3 zu viel.

**Scope:**
- Roter Button `Notfall melden` im `app.sidebar`-Header (oben, unübersehbar).
- Klick öffnet ein `flux:modal` mit:
  - Liste aller Szenarien des Mandanten (sortiert nach Häufigkeit oder
    alphabetisch)
  - „Tabletop-Übung" oder „Echte Lage" Auswahl (`ScenarioRunMode`)
  - Optional: Titel-Override
- Bei Bestätigung: legt einen `ScenarioRun` an (Status active) und navigiert
  direkt zum Run-Detail.
- Sichtbar für jeden Team-Member (nicht nur Admin), da im Ernstfall jeder
  reagieren können muss.

**Affected Files:**
- `resources/views/layouts/app/sidebar.blade.php`
- Neue Livewire-Komponente `App\Livewire\IncidentLauncher` (oder als Volt-Modal
  in der Sidebar — entscheidet der Agent nach Code-Hygiene).
- Optional: Route `incident.launch` für den Submit-Endpoint.

**Tests:**
- `tests/Feature/IncidentLauncherTest.php`: Run wird angelegt, Redirect, Audit-
  Log-Eintrag, Mandanten-Scope.

---

### QW4 — Audit-Log-Export (CSV + PDF)

**Warum:** Wirtschaftsprüfer / Aufsichtsbehörde wollen das in 90 % der Fälle
exportierbar haben. Daten sind da, der Knopf fehlt.

**Scope:**
- Auf `pages/audit-log/⚡index.blade.php`: zwei Buttons „Als CSV" und
  „Als PDF" mit aktiven Filtern (Datumsbereich, Action-Filter).
- Neuer invokable Controller `App\Http\Controllers\AuditLogExportController`
  mit zwei Methoden (`csv`, `pdf`) oder zwei Controllern.
- CSV: streamt direkt (nicht in Memory), Trennzeichen `;` (Excel-DE-Standard),
  Header-Row.
- PDF: nutzt vorhandenes `barryvdh/laravel-dompdf`, eigenes Blade-Template
  `resources/views/audit-log-export.blade.php` mit Firmen-Header und Tabelle.
- Routen `audit-log.export.csv` und `audit-log.export.pdf` im admin-gegateten
  Team-Block (`EnsureTeamMembership:admin`).
- Mandanten-Isolation: nur Einträge des aktuellen Mandanten.

**Affected Files:**
- `app/Http/Controllers/AuditLogExportController.php` (neu)
- `resources/views/audit-log-export.blade.php` (neu, Druckansicht)
- `resources/views/pages/audit-log/⚡index.blade.php` (Buttons + Filter-State
  als Query-Params an die Routen)
- `routes/web.php`

**Tests:**
- `tests/Feature/AuditLogExportTest.php`: CSV-Header korrekt, Inhalt korrekt,
  PDF-Response Content-Type, Mandanten-Isolation, nur Admin darf.

---

### QW5 — Read-Receipt für Versionsfreigaben

**Warum:** BSI 200-4 verlangt nachweisbar, dass Mitarbeiter neue Versionen
gelesen haben. Heute existiert nur die Veröffentlichung — keine Bestätigung.

**Scope:**
- Neue Tabelle `handbook_version_acknowledgements`:
  - `id` (uuid), `handbook_version_id` (FK cascade),
    `employee_id` (FK cascade), `acknowledged_at` (timestamp), `notes` (text
    nullable), timestamps.
  - Unique `(handbook_version_id, employee_id)`.
- Modell + BelongsTo / HasMany Beziehungen auf `HandbookVersion` und
  `Employee`.
- UI auf `pages/handbook-versions/⚡index.blade.php`: pro freigegebener Version
  ein „Lesebestätigungen"-Badge (`x von y bestätigt`) und Link in Detail-Modal,
  in dem man:
  - die Liste der bestätigenden Mitarbeiter sieht
  - manuell Mitarbeiter als „bestätigt" markieren kann (Admin-Funktion)
- Optional Phase 2: Link für Mitarbeiter zum Selbst-Bestätigen (würde
  Mitarbeiter-Login voraussetzen — aktuell hat nicht jeder Mitarbeiter einen
  User-Account, deshalb erst mal Admin-Markierung).
- Auf Dashboard-Widget (siehe QW1): Hinweis, wenn aktuelle Version unter
  100 % Ack-Quote ist.

**Affected Files:**
- `app/Models/HandbookVersionAcknowledgement.php` (neu)
- `database/migrations/…_create_handbook_version_acknowledgements_table.php`
- Erweiterung `app/Models/HandbookVersion.php` (Relations + Helper
  `acknowledgementRate()`)
- `resources/views/pages/handbook-versions/⚡index.blade.php`

**Tests:**
- `tests/Feature/HandbookVersionAcknowledgementsTest.php`: Anlegen,
  Mandanten-Isolation, Quote-Berechnung, Cascade bei Mitarbeiter-Lösch.

---

## 🧱 Mittlere Lifts (eigene Sprints wert)

### Lessons-Learned-Domäne
Strukturierte After-Action-Auswertung pro `IncidentReport` / `ScenarioRun`:
Root Cause, Was lief gut/schlecht, Action Items mit Verantwortlichen +
Fälligkeit. Eigenes Modell `LessonLearned` mit Beziehungen zu Reports und
Tasks, die in Versionshistorie referenziert werden können.

### Risiko-Register
`Risk`-Modell mit Eintrittswahrscheinlichkeit (1-5), Schadenshöhe (1-5),
errechnetem Score. Beziehungen zu Systemen + Maßnahmen. Restrisiko nach
Maßnahmenumsetzung. NIS2/ISO 27001 verlangen das.

### Compliance-Dashboard
Score „NIS2-Readiness" / „BSI 200-4-Abdeckung" pro Mandant, errechnet aus
vorhandenen Daten (Pflichtrollen besetzt, kritische Systeme mit Runbook,
Tests in den letzten 12 Monaten). Visualisiert den Tool-Wert messbar.

### War-Room-View für Scenario-Runs
Echtzeit-Multi-User-Ansicht (Livewire + Reverb): mehrere Personen sehen
parallel, wer welchen Schritt erledigt hat. Macht aus dem Doku-Tool einen
operativen Krisen-Kommandostand.

### PWA + Offline-Modus
Service-Worker cached letzte freigegebene PDF + Telefonliste lokal. Bei
Stromausfall = Lebensretter.

---

## 🌱 Strategisch / Plattform

### Branchen-Templates ausbauen
`IndustryTemplates` kennt nur Handwerk. Arzt-/Anwaltskanzlei,
Steuerberater, Hotel, Pflegeheim — jede mit eigenem System-Set,
Szenario-Schwerpunkten, Meldepflichten.

### Brevo/Mailgun-Integration für Krisen-Kommunikation
`CommunicationTemplate`-Vorlagen werden vom „copy & paste"-Tool zum
echten Versand mit Audit-Spur (wer wann was bekommen hat).

### Self-Service-Trial + Stripe-Abrechnung
`laravel/cashier`, Tier-Limits über die existierende Settings-
Infrastruktur (Catalog erweitern um max-Werte).

### API + Webhooks
Monitoring (Zabbix/Prometheus/UptimeRobot) → automatischer Incident.
Umgekehrt: Webhook bei jeder Eskalation an Slack/Teams.

### Plattform-Härtung
IP-Allowlist pro Mandant, Geo-Login-Anomalie, Session-Timeout pro Mandant.
Aufbauend auf bereits vorhandener 2FA-Erzwingungs-Infrastruktur.

---

## 🎨 UX / Polish

### Inline-Edit statt Modal
Auf Listen-Seiten Felder direkt klickbar — spart 2 Klicks pro Bearbeitung.

### Bulk-Actions
„Alle Mitarbeiter im Standort Werkstatt → Rolle Werkstatt-Pool".

### Tastatur-Shortcuts
`g s` Systeme, `g e` Mitarbeiter, `n` Neu (kontextabhängig).

### „Mandant exportieren"
Komplettes ZIP mit DB-Dump + alle PDFs. DSGVO-Auskunft + „Daten gehören
dem Kunden"-Zusicherung.
