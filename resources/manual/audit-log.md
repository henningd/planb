## Worum es geht

Das Audit-Log zeichnet **jede Änderung an audit-relevanten Daten** auf — wer, wann, was, mit Vorher-/Nachher-Werten. Sie können den Log filtern und als CSV oder PDF exportieren.

Erreichbar über die Sidebar **„Team & Freigaben → Aktivitäten"** (Admin only).

## Was geloggt wird

- **Änderungen an Stammdaten**: Mitarbeiter, Systeme, Standorte, Rollen, Dienstleister, Versicherungen.
- **Änderungen am Risiko-Register**: Neue Risiken, Status-Änderungen, Maßnahmen.
- **Änderungen an Lessons Learned und Action-Items**.
- **Versand von Krisen-Kommunikation**: Empfänger-Liste, Kanal, Erfolg/Fehler.
- **Versionsfreigaben** und Lesebestätigungen.
- **API-Tokens**: Erstellung, Widerruf, Verwendung.
- **Branding-Änderungen**.

Pro Eintrag:

- **Zeitstempel** (Sekunden-genau).
- **Benutzer** (oder „System" bei automatischen Vorgängen).
- **Objekt-Typ** und **Objekt-ID**.
- **Aktion** (created / updated / deleted / sent / approved).
- **Änderungen** als JSON-Diff (was war vorher, was ist jetzt).

## Filter

- **Datumsbereich**.
- **Aktion** (created, updated, deleted, sent).
- **Benutzer** (welcher Mitarbeiter).
- **Objekt-Typ** (z. B. nur Mitarbeiter-Änderungen).

## Export

Zwei Knöpfe oben rechts:

- **„Als CSV"** — direkter Download, mit allen Filtern angewandt. Trennzeichen `;` (Excel-DE-Standard).
- **„Als PDF"** — mit Firmen-Header und Tabellen-Layout, druckbar.

Beide Exporte respektieren die aktuellen Filter — wenn Sie nur „letzte 7 Tage" gefiltert haben, ist das auch nur im Export.

## Aufbewahrung

Pro Mandant einstellbar (System-Settings → Audit-Log Aufbewahrung):

- **0 Tage** (Default) → unbegrenzt aufbewahren.
- **365 Tage** → automatische Bereinigung älterer Einträge.

Tägliches Cleanup läuft im Hintergrund. Vorsicht: Einmal gelöschte Einträge sind weg.

## Manipulationssicherheit

Das Audit-Log selbst ist nur lesbar — niemand kann Einträge bearbeiten. Löschen geht nur über die Aufbewahrungs-Bereinigung. Ein Plattform-Betreiber mit DB-Zugriff könnte theoretisch eingreifen — falls Sie höhere Anforderungen haben (Justiz-relevante Spuren), brauchen Sie zusätzlich Off-Site-Logging.

## Wer was sehen darf

Audit-Log ist nur für **Admin und Owner** sichtbar.

> **Praxis-Hinweis**: Filter lassen sich als URL-Bookmarks teilen — `…/audit-log?from=2026-01-01&action=deleted`. So kann der Wirtschaftsprüfer einen Direktlink bekommen statt einer Anleitung.
