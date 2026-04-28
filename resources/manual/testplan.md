## Was der Testplan ist

Der Testplan listet alle **wiederkehrenden Notfall-Tests** auf, die regelmäßig durchgeführt werden müssen — Backup-Restore, USV-Lasttest, Phishing-Awareness, Generator-Probelauf usw.

Erreichbar über die Sidebar **„Ernstfall → Testplan"** (Admin only).

## Einen Test anlegen

Knopf **„Neuer Test"**. Pflichtangaben:

- **Bezeichnung** — z. B. „USV-Lasttest Server-Schrank".
- **Test-Typ** — Backup-Restore, Wiederanlauf, Phishing, USV/Strom, Tabletop, Kommunikations-Übung.
- **Intervall** — Monatlich, Quartalsweise, Halbjährlich, Jährlich.
- **Verantwortlich** — eine Rolle oder eine Person.

Optional:

- **Dauer** (in Stunden) — als Planungs-Hilfe.
- **Beschreibung** — was genau wird gemacht?
- **Letztes Durchführungsdatum** — wenn schon mal gelaufen.
- **Nächster Termin** — wird aus Intervall + letztem Datum errechnet, kann aber überschrieben werden.

## Test als durchgeführt markieren

Pro Test gibt es einen **„Durchgeführt"-Knopf**. Sie tragen ein:

- **Wer hat getestet**.
- **Wann**.
- **Erfolgreich** (Ja/Nein).
- **Notiz / Auffälligkeiten**.

Speichern → der nächste Termin wird aus dem Intervall errechnet, der Test verschwindet von der „Heute"-Liste auf dem Dashboard.

## Bezug zum Compliance-Score

Im Compliance-Dashboard ist der Check **„Notfall-Tests durchgeführt"** mit Gewicht 10 hinterlegt. Er prüft:

- Mindestens ein Test geplant.
- Keine Tests sind überfällig.
- Mindestens ein Test in den letzten 12 Monaten erfolgreich durchgeführt.

## Erinnerungen

Wenn der nächste Test in 14 Tagen oder weniger fällig ist, erscheint er auf dem Dashboard. Der Verantwortliche bekommt zusätzlich eine **Reminder-E-Mail** (sofern E-Mail-Versand konfiguriert ist).

## Wer was sehen darf

Testplan ist nur für **Admin und Owner** sichtbar.

> **Praxis-Hinweis**: Sechs Tests pro Jahr verteilt sind sinnvoll — ein Test alle zwei Monate. Mehr ist Overkill, weniger ist im Audit dünn.
