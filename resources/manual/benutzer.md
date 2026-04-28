## App-Benutzer und Mitarbeiter

Wichtige Unterscheidung:

- **App-Benutzer** sind Personen mit Login (E-Mail + Passwort), die in der App arbeiten.
- **Mitarbeiter** sind alle Personen Ihres Unternehmens — viele davon haben keinen App-Login.

Beide werden separat gepflegt. Im Idealfall sind App-Benutzer eine Teilmenge der Mitarbeiter (Geschäftsführung, Notfallbeauftragte, IT-Lead).

Erreichbar über die Sidebar **„Team & Freigaben → App-Benutzer & Einladungen"**.

## Einen Benutzer einladen

Knopf **„Einladen"**. Pflichtangaben:

- **E-Mail** des Eingeladenen.
- **Rolle** im Team — Owner, Admin, Member.

Nach Klick auf **„Einladung senden"** bekommt die Person eine E-Mail mit einem Annahme-Link. Sobald sie klickt und sich registriert, ist sie Teil Ihres Teams.

## Die drei Rollen

| Rolle | Rechte |
|---|---|
| **Owner** | Alles. Kann das Team auflösen. Genau ein Owner pro Team Pflicht. |
| **Admin** | Alles außer Team-Auflösung. Kann andere einladen, Versicherungen sehen, Audit-Log lesen, Branding ändern. |
| **Member** | Stammdaten, Szenarien, Aufgaben-Inbox, Lessons Learned. Sieht keine Versicherungen, kein Audit-Log, kein Branding. |

## Rolle ändern

Auf der Benutzer-Liste pro Person ein Dropdown mit den drei Rollen. Ein Klick reicht.

## Benutzer entfernen

Pro Benutzer ein **„Entfernen"**-Knopf. Nach Bestätigung verliert die Person den Zugang zum Team. Ihre Daten in der App (Mitarbeiter-Eintrag, Audit-Spur) bleiben erhalten — nur der Login funktioniert nicht mehr für dieses Team.

## Owner-Übergabe

Wenn der bisherige Owner das Team verlassen will: erst einen Admin zum **neuen Owner** befördern, dann sich selbst als alten Owner entfernen.

## E-Mail-Voraussetzung

Damit Einladungs-Mails funktionieren, muss die Plattform **E-Mail-Versand konfiguriert** haben — entweder über SMTP zu Strato/Brevo/Mailgun oder einen anderen Provider. Wenn nicht: Plattform-Betreiber kontaktieren.

## Wer einladen darf

Nur **Admin und Owner** dürfen Einladungen verschicken.

> **Praxis-Hinweis**: Geben Sie nicht jedem Mitarbeiter Admin-Rechte. Member reicht für 80 % der Personen. Admin nur für die zwei oder drei Personen, die das Notfallhandbuch aktiv pflegen.
