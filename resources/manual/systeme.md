## Was ein „System" ist

In dieser Plattform ist ein **System** alles, von dem Ihr Geschäftsbetrieb abhängt. Das umfasst klassische IT (E-Mail, Server, Internet) genauso wie Geschäfts-Anwendungen (Warenwirtschaft, Buchhaltung, Kassensystem) und in manchen Branchen auch physische Systeme (Heizungssteuerung, Kühlkette, Maschinen).

Erreichbar über die Sidebar **„Notfallhandbuch → Systeme"**.

## Ein System anlegen

Knopf **„Neues System"**. Pflichtangaben:

- **Name** — kurz und eindeutig, z. B. „Warenwirtschaft" oder „Büro-Server".
- **Kategorie** — Geschäftsbetrieb, Technische Infrastruktur, Unterstützend.
- **Notfall-Level** — siehe das Kapitel „Notfall-Level". Pflicht für Onboarding.

Empfohlen:

- **Beschreibung** — was macht das System konkret?
- **Notfall-Workaround** (`fallback_process`) — was tun, wenn das System ausfällt? z. B. „Warenausgabe handschriftlich auf Lieferschein, später nachbuchen".
- **Runbook-Verweis** — Link oder Hinweis auf die technische Wiederanlauf-Anleitung.
- **RTO** (Recovery Time Objective) — wie lange darf der Ausfall maximal dauern?
- **RPO** (Recovery Point Objective) — wie alt darf das Backup höchstens sein, das wir nutzen?
- **Ausfallkosten pro Stunde** — geschätzter Umsatz-/Produktivitätsverlust. Hilft bei Priorisierung.

## Eigentümer und Operator

Pro System ordnen Sie zwei Rollen-Typen zu:

- **Eigentümer (Owner)** — fachlich verantwortlich, trifft Entscheidungen über das System.
- **Operator** — technisch verantwortlich, führt Wartung und Wiederanlauf durch.

Beide Rollen können **Personen** oder **Rollen** sein — Personen sind eindeutiger, Rollen sind flexibler. Pro Slot Hauptperson und beliebig viele Stellvertretungen.

## Dienstleister verknüpfen

Im Tab **„Dienstleister"** ordnen Sie externen Dienstleistern eine **Eigentumsrolle** zu (Betreiber, Backup, Support). Pro System mehrere Dienstleister möglich, jeder mit eigener Rolle.

## Abhängigkeiten

Im Tab **„Abhängigkeiten"** legen Sie fest, von welchen anderen Systemen dieses System abhängt — z. B. Warenwirtschaft hängt von Server + Internet. Diese Verknüpfungen werden im Recovery-Gantt automatisch zur Wiederanlauf-Reihenfolge.

## System-Aufgaben

Im Tab **„Aufgaben"** erfassen Sie wiederkehrende Pflege- und Wartungsaufgaben — z. B. „Backup-Restore-Test halbjährlich", „Sicherheits-Updates monatlich". Pro Aufgabe RACI-Zuordnung und Fälligkeitsdatum. Diese Aufgaben tauchen zentral in der **Aufgaben-Inbox** auf.

## Klassifizierung

Jedes System bekommt einen **Notfall-Level** (kritisch, hoch, mittel, niedrig). Die Liste ist filterbar nach Notfall-Level — so sehen Sie auf einen Blick, welche Systeme im Ernstfall die höchste Aufmerksamkeit brauchen.

## Mindestens drei klassifiziert

Die Onboarding-Prüfung verlangt **mindestens drei Systeme** mit Notfall-Level. Dass ein System ohne Klassifizierung erfasst ist, hilft niemandem — die Frage „wie wichtig?" muss vorher geklärt sein.

## Monitoring-Mapping

Im Bereich **„Monitoring-Hostnamen"** pflegen Sie Hostnamen oder Labels, unter denen das System in Zabbix oder Prometheus auftaucht. Wenn ein Alarm reinkommt, wird er automatisch dem richtigen System zugeordnet.

## QR-Code-Aushang

Über das Kontextmenü auf der System-Liste oder den Knopf auf der Detail-Seite erzeugen Sie einen **gedruckten Notfall-Aushang** mit QR-Code. Der QR-Code führt im Ernstfall direkt zur System-Detailseite — perfekt fürs Schwarze Brett am Server-Schrank.

## Wer was sehen darf

Systeme sind für **alle Team-Mitglieder lesbar**. Bearbeiten dürfen sie **Admin und Owner**.

> **Tipp**: Übertreiben Sie nicht. Eine kleine Firma hat 10–15 Systeme, ein größerer Mittelständler vielleicht 30. Ein Beleuchtungs-Schalter ist kein „System".
