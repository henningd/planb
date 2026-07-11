## Was Schulungen sind

Der Bereich **„Schulungen"** ist Ihr Nachweisregister für alle **Schulungs- und Awareness-Maßnahmen**: wer wurde wann zu welchem Thema geschult, und wann ist die nächste Auffrischung fällig. Er dokumentiert damit, dass Ihre Organisation ihre Leute nicht nur mit einem Handbuch ausstattet, sondern sie auch tatsächlich befähigt.

Erreichbar über die Sidebar **„BCMS & Governance → Schulungen"**.

## Warum das wichtig ist

Ein Notfallhandbuch ist nur so gut wie die Menschen, die im Ernstfall danach handeln. **Prüfer, Versicherer und BSI-Standard 200-4** verlangen deshalb den dokumentierten Nachweis, dass Awareness- und Notfallschulungen geplant, durchgeführt und regelmäßig wiederholt werden. Auch **NIS2 (Art. 21)** fordert Cyberhygiene-Schulungen sowie eine verpflichtende Schulung der Leitungsebene. Ohne belegbare Nachweise ist im Audit oder Schadensfall schwer zu argumentieren, dass die Organisation vorbereitet war.

## Einen Nachweis anlegen

Knopf **„Neuer Nachweis"**. Pflichtangaben:

- **Geschulte Person** — wer teilgenommen hat (aus der Liste, siehe [Mitarbeiter](mitarbeiter)).
- **Verantwortlich (Organisator)** — optional: wer die Schulung organisiert/durchgeführt hat. Erscheint als Nachweis im Audit-Bericht und Handbuch-PDF.

**Abgeleitete Maßnahmen:** Ergibt eine Schulung Handlungsbedarf (z. B. „Passwort-Richtlinie überarbeiten"), legen Sie dazu einen [Offenen Punkt / Klärpunkt](offene-punkte) an und verknüpfen ihn im Feld **„Aus Schulung entstanden"** mit dieser Schulung. Im Audit-Bericht erscheinen diese Maßnahmen dann direkt bei der Schulung — so ist belegt, dass aus Schulungen auch etwas folgt.
- **Thema** — z. B. „Phishing-Awareness" oder „Evakuierung Standort Nord".
- **Typ** — BCM-/Notfallschulung, IT-Sicherheit, Leitungsschulung oder Datenschutz.

Optional:

- **Absolviert am** — das Abschlussdatum. Bleibt es leer, gilt die Schulung als **geplant** (noch nicht durchgeführt).
- **Wiederholung** — Intervall der Auffrischung (Monatlich, Quartalsweise, Halbjährlich, Jährlich, Alle 2 Jahre). Leer = einmalige Schulung.
- **Nächste Fälligkeit** — wird bei gesetztem Intervall automatisch aus Abschlussdatum (sonst ab heute) plus Intervall errechnet, lässt sich aber überschreiben.
- **Notizen** — Raum für den Nachweis: Teilnahmebestätigung, Zertifikatsnummer, Trainer, Inhalt.

## Geplant vs. durchgeführt

Der Kern der Seite ist die Unterscheidung zwischen **geplanten** und **durchgeführten** Schulungen:

- Ohne **Absolviert am** ist der Eintrag eine geplante Maßnahme — ein Vorhaben, das noch offen ist.
- Mit Abschlussdatum ist er ein echter Nachweis.

Über das Kontextmenü oder den Knopf **„Als absolviert markieren"** setzen Sie das heutige Abschlussdatum in einem Klick. Bei wiederkehrenden Schulungen wird dabei automatisch die **nächste Fälligkeit** aus dem Intervall neu berechnet — der Zyklus läuft also von selbst weiter.

## Wiederholung und Fälligkeit

Ist ein Intervall hinterlegt, führt PlanB die Schulung als **wiederkehrend** und errechnet die nächste Fälligkeit. Liegt dieses Datum in der Vergangenheit, wird der Eintrag als **überfällig** markiert (rotes Badge, rot hervorgehobenes Datum). So sehen Sie auf einen Blick, welche Auffrischung ansteht oder schon verpasst ist.

Mit den Filtern oben lässt sich die Liste nach **Typ** und nach **Mitarbeiter** einschränken — praktisch, um pro Person alle Nachweise zu sammeln oder alle überfälligen Leitungsschulungen zu finden.

## Nachweis im Audit-Bericht und im Handbuch-PDF

Alle Einträge erscheinen als Nachweis in den beiden zentralen Exporten:

- Im **Audit-Bericht** unter „Schulungen und Awareness" — mit Thema, geschulter Person (und Verantwortlichem), Status (geplant / durchgeführt am), nächster Fälligkeit inkl. Überfällig-Kennzeichnung und dem Feld **Nachweis / Notiz**. Genau die Prüfpunkte, die ein Auditor sehen will.
- Im **Handbuch-PDF** unter **„13.4 Schulungs- und Awareness-Nachweise"** — als Tabelle mit Thema, Person, Durchführungsdatum und nächster Fälligkeit.

Was Sie hier pflegen, wird also ohne Zusatzaufwand zum prüffesten Beleg. Ein ordentlicher Notiz-Eintrag (Zertifikat, Trainer, Inhalt) zahlt sich im Audit unmittelbar aus.

## Bezug zu anderen Bereichen

Schulungen greifen mit den übrigen Übungs- und Nachweisbereichen ineinander: Der [Testplan](testplan) hält die wiederkehrenden Notfall-Tests fest, die [Übungsberichte](uebungsberichte) dokumentieren einzelne Übungsläufe, und aus deren Auswertung entstehen [Lessons Learned](lessons-learned). Eine durchgeführte Awareness-Übung kann also gleichzeitig hier als Schulungsnachweis hinterlegt werden.

> **Praxis-Hinweis**: Legen Sie pflichtige Auffrischungen (z. B. jährliche Phishing-Awareness) direkt mit Intervall als geplanten Eintrag an. Dann taucht die Fälligkeit von selbst auf und Sie markieren sie nach der Schulung einfach als absolviert — statt jedes Jahr daran zu denken, überhaupt einen Nachweis zu erstellen.
