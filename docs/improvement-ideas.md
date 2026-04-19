# PlanB – Verbesserungsideen

Bestandsaufnahme aus KMU-Sicht im Vergleich zum Marketingversprechen. Priorisiert nach Impact und Passung zum MVP-Fokus „IT-Notfallmanagement und Krisenpläne".

Stand: 2026-04-19. Aktueller MVP: Firmenprofil, Ansprechpartner, Notfall-Level, Systeme + Prioritäten, Dashboard, Multi-Tenant via Team→Company.

---

## Top 4 – hoher Impact, passend zum MVP-Fokus

### 1. PDF-Export des Notfallhandbuchs

Steht in der Landingpage als Versprechen, ist aber nicht gebaut. Wenn Outlook/Server ausfallen, ist ein Papier- oder Offline-PDF das einzige, was im Ernstfall funktioniert. Ohne das kippt das Kern-USP.

**Scope:** Export einer gesamten Firma inkl. aller Module (Firmendaten, Ansprechpartner mit Rollen, Notfall-Level, Systeme gruppiert nach Kategorie und Priorität). Seitenaufbau druckoptimiert, Stand-Datum und Versionsnummer im Footer.

### 2. Szenario-Playbooks mit Ernstfall-Modus

Vorgefertigte Checklisten für die häufigen KMU-Szenarien: Ransomware, Serverausfall, Stromausfall, Datenpanne, Internet-/Telefonausfall, Ausfall Dienstleister. „Ernstfall starten" → Schritte werden abgehakt, Uhrzeit und verantwortliche Person automatisch protokolliert. Liefert der Versicherung und dem Prüfer später einen nachvollziehbaren Ablauf.

Ohne das ist die Software dokumentierend, nicht handlungsunterstützend. Das ist das Feature, das aus „wir haben alles dokumentiert" „wir können damit durch eine Krise navigieren" macht.

**Scope:**

- `scenarios` – Vorlagen (inkl. Seed für typische Fälle)
- `scenario_steps` – nummerierte Schritte mit Verantwortlichem und Dauer-Hinweis
- `scenario_runs` + `scenario_run_steps` – instanziierte Übung/Ernstfall mit Zeitstempeln und freiem Notizfeld
- „Ernstfall starten"-Button als separater Workflow

### 3. Externe Dienstleister pro System mit Notfall-Hotline

KMU haben keine IT – sie haben einen IT-Dienstleister. Aktuell kannst du Kontakte und Systeme pflegen, aber nicht sagen „für das Warenwirtschaftssystem ruft Herr X bei Dienstleister Y die Nummer 0800-…". Das ist die häufigste Frage im Ernstfall.

**Scope:**

- `service_providers` (oder Spezialisierung von `contacts`) – Firma, Hotline, Vertrags-/Kundennummer, SLA-Zeitfenster
- Pivot `system_service_provider` – mehrere Dienstleister pro System möglich, mit Rolle (Betreiber, Backup, Support)
- Auf der System-Detailansicht sichtbar zusammen mit Ansprechpartner

### 4. Meldepflichten-Assistent

DSGVO (72 h), NIS2, Versicherungsmeldungen in einem Wizard. Bei Datenschutzvorfall ist die Uhr sofort an – ein geführter Ablauf spart Stunden und verhindert echte Bußgelder.

**Scope:**

- Auswahl des Vorfalltyps → dynamische Fristenliste
- Countdown-Anzeige pro Frist (z. B. „noch 63:42 h")
- Vorbereitete Meldeformulare/Anschreiben als Download
- Aufzeichnung, wer wann was gemeldet hat (Audit-relevant)

---

## Wichtig, aber zweite Welle

### 5. Review-Erinnerungen

Alle 6 Monate Mail an den Hauptansprechpartner: „Bitte bestätigen Sie, dass alle Einträge noch aktuell sind." Das größte BCM-Problem ist nicht das Anlegen, sondern das Veralten – ohne aktiven Review-Zyklus verwaist jedes Handbuch binnen eines Jahres.

**Scope:** `reviews`-Tabelle, Scheduler-Job, Dashboard-Badge „nächster Review in X Tagen", One-Click-Bestätigung oder Anpassung.

### 6. Wiederanlauf-Reihenfolge mit Abhängigkeiten

„System A braucht Datenbank B, die braucht Storage C." Heute gibt es nur Priorität ohne Kette. Die Reihenfolge im Wiederanlauf ist aber zentraler Teil des Notfallplans.

**Scope:** Selbstreferenzielle Relation auf `systems` (`depends_on_id` oder Many-to-Many), RTO (Recovery Time Objective) pro System, optional RPO (Recovery Point Objective), visualisierte Reihenfolge auf einer separaten Wiederanlauf-Seite.

### 7. Kommunikations-Templates

Vorformulierte Texte für Mitarbeiter, Kunden, Presse, Behörden. Inklusive Alternativkanälen, wenn E-Mail ausgefallen ist – z. B. „SMS an alle Mitarbeiter", „Aushang am Eingang", „Anruf beim IT-Dienstleister".

**Scope:** `communication_templates` pro Szenario, Platzhalter für Firma/Vorfall/Zeitpunkt, Export als druckbares Blatt oder Copy-Paste-Block.

### 8. Branchen-Templates beim Onboarding

Kanzlei/Handel/Produktion/Dienstleister haben unterschiedliche Prioritäten und Szenarien. Beim Onboarding die Branche aus der `Industry`-Enum abfragen → sinnvolle Defaults statt leerer Formulare.

**Scope:** Vordefinierte System-Kategorien, typische Ansprechpartner-Rollen, szenariospezifische Checklistenvorlagen pro Branche.

### 9. Audit-Log + Änderungshistorie

Versicherer und Prüfer fragen „wer hat wann was geändert?". Heute gibt es keine Antwort.

**Scope:** Spatie/laravel-activitylog-artig, aber sauber ins Mandantenmodell integriert. Auf jeder Detail-Ansicht eine „Letzte Änderungen"-Box.

---

## Nice-to-have / strategisch differenzierend

### 10. Versicherungs-Integration

Police-Nummer, Hotline, Meldezeitfenster, Ansprechpartner beim Versicherer. Passt direkt zu deiner geplanten White-Label-Zielgruppe „Cyberversicherung".

### 11. Rollen-basierte Sichtbarkeit

Externer Dienstleister sieht nur „seinen" Teil des Handbuchs. Mitarbeiter sehen die Notfallkontakte und das, was sie selbst tun sollen – nicht die Cyberversicherungspolice.

### 12. Read-only-Freigabelink mit Ablauf

Versicherung, Auditor oder IT-Berater kann ohne Login reinschauen. Token-basiert, auf X Tage befristet, optional nur bestimmte Sektionen.

### 13. PWA / Offline-Zugriff

Handbuch im Browser offline verfügbar machen. Wirkliches Alleinstellungsmerkmal: die App funktioniert, wenn Server und Netzwerk down sind. Ergänzt den PDF-Export.

### 14. QR-Code am Serverraum

Physischer Aushang → direkter Link zum Wiederanlaufplan für genau dieses System. Niedrigschwellig, aber im Ernstfall Gold wert.

### 15. Übungsmodus

Simulierter Ernstfall als Trockenübung, klar von echten Vorfällen getrennt. Protokoll wird als „Übung" markiert. Ermöglicht Nachweise gegenüber Versicherungen („Wir haben halbjährlich geübt").

---

## Empfehlung für den nächsten Sprint

**#2 Szenario-Playbooks mit Ernstfall-Modus** zuerst, danach **#1 PDF-Export**.

Begründung: #2 ist das eigentlich differenzierende BCM-Feature. #1 muss aus Versprechens-Gründen bald nachkommen, ist aber inhaltlich unspektakulärer als #2.

Danach Welle 2 (#3, #4), dann Pflegezyklus (#5).
