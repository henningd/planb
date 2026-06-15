Diese Anleitung fasst **den gesamten Weg zum fertigen Notfallhandbuch** an einem Ort zusammen — von der ersten Eingabe bis zum gelebten, prüffähigen BCMS. Sie ist als durchgehende Schritt-für-Schritt-Liste gedacht, die Sie auch **ausdrucken** können (Browser: `Strg`/`Cmd` + `P`).

Der Aufbau folgt drei Phasen:

| Phase | Ziel | Aufwand |
|---|---|---|
| **1 · Grundgerüst** | Ein vollständiges, freigegebenes Notfallhandbuch | 2–6 Std., verteilt auf 1–2 Tage |
| **2 · BCMS-Ausbau** | Reife nach BSI 200-4 / NIS2 (Vorsorge, Risiken, Governance) | iterativ über Wochen |
| **3 · Leben & pflegen** | Aktuell halten, üben, verbessern | dauerhaft |

> **Tipp:** Phase 1 reicht für ein einsatzfähiges Notfallhandbuch. Phase 2 hebt den Reifegrad Richtung „Standard-BCMS" und erfüllt zusätzliche NIS2-Pflichten. Beginnen Sie nicht mit allem gleichzeitig.

## Phase 1 – Das Grundgerüst (die 9 Pflicht-Schritte)

Diese Schritte entsprechen dem **Einrichtungs-Assistenten** auf dem Dashboard (Sidebar: *Einrichtung*). Das System prüft jeden Schritt automatisch und hakt ihn ab, sobald die Voraussetzung erfüllt ist.

| # | Schritt | Wo in der App | Was zu tun ist |
|---|---|---|---|
| 1 | **Firmenprofil** | *Notfallhandbuch → Firma* | Name, Branche, Rechtsform, Mitarbeiterzahl, zuständige Aufsichtsbehörde. Grundlage für alle Vorlagen und Reports. |
| 2 | **Branchen-Template** *(optional)* | *Firma → Branchen-Template* | Typische Systeme Ihrer Branche per Klick übernehmen — spart bis zu 80 % Tipparbeit. |
| 3 | **Standorte** | *Notfallhandbuch → Standorte* | Mindestens ein Standort, idealerweise mit Hauptsitz-Markierung. Wird für Wiederanlauf und Aushänge gebraucht. |
| 4 | **Mitarbeiter** | *Notfallhandbuch → Mitarbeiter* | Mindestens drei Personen mit Kontaktdaten. Ohne sie bleiben Telefonliste und RACI leer. |
| 5 | **Pflichtrollen besetzen** | *Abteilungen / Rollen* | Notfallbeauftragte/r, IT-Leitung, Datenschutz, Kommunikation, Geschäftsführung — je eine Hauptperson und möglichst eine Vertretung. |
| 6 | **Dienstleister** | *Notfallhandbuch → Dienstleister* | Mindestens ein externer IT-Dienstleister mit Hotline und SLA-Zeitfenster. |
| 7 | **Systeme klassifizieren** | *Notfallhandbuch → Systeme* | Mindestens drei kritische Geschäfts-/IT-Systeme erfassen, je ein Notfall-Level (RTO/RPO) zuordnen. |
| 8 | **Sofortmittel** | *Notfallhandbuch → Sofortmittel* | Mindestens eine Notfall-Ressource: Notebook-Pool, USV, Schlüssel, Bargeld … |
| 9 | **Erste Handbuch-Version freigeben** | *Team & Freigaben → Versionshistorie* | Den dokumentierten Stand offiziell „freigegeben" stellen — damit beginnt die revisionssichere Versionshistorie. |

Nach Schritt 9 haben Sie ein **vollständiges, freigegebenes Notfallhandbuch** und einen ersten Compliance-Score.

### Sinnvoll ergänzen (noch Teil des Grundgerüsts)

- **Abhängigkeiten** zwischen Systemen erfassen (*Systeme → Abhängigkeiten*) — damit die Wiederanlauf-Reihenfolge stimmt.
- **Notfallbetrieb / Ausweichverfahren** je kritischem System hinterlegen (*Notfallhandbuch → Notfallbetrieb*).
- **Szenarien** aus den Vorlagen übernehmen (*Ernstfall → Szenarien*) und auf Ihre Lage anpassen.

## Phase 2 – BCMS-Ausbau nach BSI 200-4 / NIS2

Diese Schritte machen aus dem Grundgerüst ein belastbares Managementsystem. Sie finden die Module in der Sidebar-Gruppe **„BCMS & Governance"** sowie unter *Prävention* und *Risiken*.

### Schritt A – Geschäftsprozesse & Business-Impact-Analyse (BIA)

*BCMS & Governance → Geschäftsprozesse / BIA*

Erfassen Sie Ihre **Geschäftsprozesse** (z. B. Auftragsannahme, Lohnabrechnung, Produktion) mit Kritikalität, maximal tolerierbarer Ausfallzeit (MTPD), RTO/RPO und den dafür benötigten Systemen. So begründen Sie die System-Prioritäten **vom Geschäft her** — das methodische Herzstück nach BSI 200-4.

### Schritt B – Risiken bewerten

*Risiken (Risiko-Register)*

Tragen Sie die relevanten Risiken ein, bewerten Sie Eintrittswahrscheinlichkeit und Auswirkung, und hinterlegen Sie **Maßnahmen** mit Verantwortlichen. So weisen Sie das Restrisiko nach.

### Schritt C – Präventivmaßnahmen festlegen

*Prävention* (auch als Karteikarte je System)

Definieren Sie **vorbeugende Kontrollen**, die einen Ausfall verhindern: Backup-Rückspieltests, Patch-Management, Monitoring, Redundanz, Wartung. Wiederkehrende Maßnahmen erhalten ein Intervall, tauchen fällig in der Aufgaben-Inbox auf und lösen Erinnerungen aus. Über *„Vorschläge übernehmen"* füllen Sie eine Systemkarte mit einem Klick.

### Schritt D – Lieferketten-Risiko bewerten

*BCMS & Governance → Lieferketten-Risiko*

Bewerten Sie kritische Dienstleister: Kritikalität, Sicherheits-Status, Wiederholungsturnus, Ausweich-Anbieter. Erfüllt die NIS2-Anforderung an die Lieferkettensicherheit.

### Schritt E – Schulungen nachweisen

*BCMS & Governance → Schulungen*

Dokumentieren Sie, **wer wann** zu BCM/Security geschult wurde — inklusive der NIS2-pflichtigen Leitungsschulung — mit Fälligkeitszyklus.

### Schritt F – Governance verankern

- **BCM-Leitlinie** (*BCMS & Governance → BCM-Leitlinie*): Geltungsbereich und Grundsätze festhalten und **durch die Leitung freigeben** — der wichtigste Entlastungsnachweis nach NIS2.
- **Management-Review** (*BCMS & Governance → Management-Review*): regelmäßige Leitungsbewertung mit Kennzahlen, offenen Maßnahmen und Beschlüssen dokumentieren.

### Schritt G – Reifegrad bestimmen

*BCMS & Governance → Reifegrad (BSI 200-4)*

Beantworten Sie den kurzen Selbsteinschätzungs-Fragebogen. Sie erhalten Ihre Stufe (**Reaktiv → Aufbau → Standard**) und eine **Lückenliste** mit den nächsten sinnvollen Schritten.

## Phase 3 – Leben & aktuell halten

Ein Notfallhandbuch ist nie „fertig". So bleibt es wirksam:

| Rhythmus | Was tun | Wo |
|---|---|---|
| **laufend** | Fällige Präventivmaßnahmen & Tests abarbeiten | *Aufgaben-Inbox* |
| **vierteljährlich** | Backup-Rückspieltest, Erreichbarkeiten prüfen | *Prävention*, *Sofortmittel* |
| **halbjährlich** | Review von Systemen, Rollen, Dienstleistern | *Systeme*, *Rollen*, *Dienstleister* |
| **jährlich** | Notfallübung durchspielen, Lessons Learned ziehen | *Testplan*, *Protokolle & Übungen*, *Lessons Learned* |
| **anlassbezogen** | Nach jeder relevanten Änderung neue **Handbuch-Version freigeben** | *Versionshistorie* |

Nach jeder größeren Änderung erzeugen Sie eine neue **Handbuch-Version** (revisionssicheres PDF mit SHA-256-Hash) und holen ggf. **Lesebestätigungen** ein.

## Reihenfolge auf einen Blick

1. Firmenprofil → 2. (Branchen-Template) → 3. Standorte → 4. Mitarbeiter → 5. Pflichtrollen → 6. Dienstleister → 7. Systeme → 8. Sofortmittel → 9. **Handbuch freigeben**
2. Dann: Geschäftsprozesse/BIA → Risiken → Präventivmaßnahmen → Lieferketten → Schulungen → Leitlinie & Management-Review → Reifegrad messen
3. Danach: üben, prüfen, aktualisieren, neue Versionen freigeben

## Häufige Stolpersteine

- **Zu groß starten.** Erst das Grundgerüst (Phase 1), dann der Ausbau. Ein einsatzfähiges Handbuch schlägt ein perfektes, das nie fertig wird.
- **Rollen ohne Vertretung.** Jede Pflichtrolle braucht eine benannte Stellvertretung — sonst fällt im Ernstfall mit einer Person die ganze Funktion aus.
- **Pläne nur digital auf betroffenen Systemen.** Sorgen Sie für einen aktuellen **PDF-Export** und die **Notfallkarte** außerhalb der eigenen IT.
- **Nie getestet.** Ein Backup oder Wiederanlaufplan, der nie zurückgespielt wurde, ist nur eine Hoffnung. Mindestens einmal jährlich üben.

> **Drucken/Weitergeben:** Diese Seite lässt sich direkt über die Druckfunktion des Browsers als PDF speichern — praktisch als Einstiegs-Leitfaden für neue Notfallbeauftragte.
