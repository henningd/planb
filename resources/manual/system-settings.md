## Worum es geht

Die System-Einstellungen sind ein einziger Bereich, in dem alle **Mandanten-spezifischen Defaults** gepflegt werden — vom PDF-Format über Backup-Aufbewahrung bis zu Webhook-URLs.

Erreichbar über die Sidebar **„Einstellungen → System"** (Admin only).

## Aufbau der Seite

Pro Einstellung sehen Sie eine Karte:

- **Bezeichnung** und Beschreibung.
- **Plattform-Default** — was die Plattform sagt.
- **Eigener Wert** Häkchen — wenn aktiv, gilt Ihr Wert statt des Plattform-Defaults.
- **Eingabe-Feld** für Ihren Wert (nur sichtbar wenn Häkchen aktiv).

## Wichtigste Einstellungen

### Auto-PDF bei neuer Version

Wenn aktiv, wird automatisch ein PDF erzeugt, sobald eine Handbuch-Version freigegeben wird. Default: aus. Empfohlen: ein, sobald Ihr Handbuch stabil ist.

### Live-Inzident-Modus

Aktiviert das Krisen-Cockpit und das rote Banner bei aktiven Szenario-Läufen. Default: ein.

### 2FA-Pflicht für Team-Admins

Erzwingt 2FA für alle Admin-Konten. Default: aus. Empfohlen: ein, wenn Sie regulierte Daten verarbeiten.

### Default-Laufzeit Freigabelinks

Standard-Gültigkeit beim Anlegen eines neuen Freigabelinks. Default: 30 Tage.

### Audit-Log Aufbewahrung

Wie lange Einträge im Audit-Log behalten werden. 0 = unbegrenzt. Empfohlen: 0 für Audit-Sicherheit oder 730 Tage für Datensparsamkeit.

### PDF-Papierformat

A4 (Default in Deutschland) oder US Letter.

### SHA-256 im PDF-Footer

Zeigt den Datei-Hash unten als Revisionsanker an. Default: ein.

### Slack-Webhook-URL

Incoming-Webhook-URL eines Slack-Channels für Krisen-Mitteilungen. Default: leer.

### Microsoft-Teams-Webhook-URL

Wie Slack, aber für Teams.

## Daten-Export und -Import

Unter den Einstellungen gibt es zwei Bereiche:

- **Daten-Export**: Backup als JSON oder vollständiges Archiv (ZIP). Siehe Kapitel „Mandanten-Archiv".
- **Daten-Import**: Hochladen einer JSON-Backup-Datei und Einlesen. Bestehende Bereiche werden überschrieben — Vorsicht!

## Wer das tun darf

Nur **Admin und Owner**.

## Plattform-weite Einstellungen

Plattform-Betreiber (Super-Admin) haben einen separaten Bereich `/admin/settings/system` für plattformweite Defaults — Plattform-Name, Footer-Text, Impressum, Datenschutzerklärung. Das ist nicht Teil dieses Handbuchs.

> **Praxis-Hinweis**: Ändern Sie nicht alle Einstellungen auf einmal. Aktivieren Sie eine, beobachten Sie eine Woche, dann die nächste — sonst wissen Sie nicht, welche Einstellung welche Wirkung hatte.
