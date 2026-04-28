## Was Sie auf dem Dashboard sehen

Das Dashboard ist die zentrale Übersichtsseite, die Sie nach jedem Login zuerst sehen. Es ist bewusst nicht überladen — Sie sollen sofort erkennen können:

1. **Wo der Mandant gerade steht** (Reifegrad).
2. **Was heute zu tun ist** (überfällige oder nahe Fälligkeiten).
3. **Welche Notlage gerade läuft** (falls eine aktiv ist).

## Live-Banner bei aktivem Notfall

Wenn ein Szenario-Lauf gerade als **„echte Lage"** läuft (nicht als Tabletop-Übung), erscheint oben über allen Seiten ein **rotes Banner**: „Aktiver Notfall: [Titel] — Zum Krisen-Cockpit". Ein Klick führt direkt ins Cockpit. Das Banner verschwindet, sobald der Lauf beendet oder abgebrochen wird.

## Onboarding-Kachel (anfangs)

Solange der Einrichtungs-Wizard noch nicht abgeschlossen ist, sehen Sie oben eine **sky-blaue Kachel** mit Fortschrittsbalken: „Einrichtung läuft — Schritt N von 9 — XX %". Ein Klick auf **„Fortsetzen"** bringt Sie zurück zum Wizard. Wenn die Einrichtung fertig ist, verschwindet die Kachel.

## „Was muss ich heute tun?"

Direkt unterhalb der Onboarding-Kachel listet das Dashboard alles auf, das jetzt akut Aufmerksamkeit braucht:

- **Notfall-Tests**, die in 14 Tagen oder weniger fällig sind oder schon überfällig.
- **Sofortmittel** (USV, Notebook), deren Prüf-Termin nahe ist.
- **Aktive Szenario-Läufe** ohne `ended_at` (= laufende Lagen).
- **Vorfälle**, bei denen Pflicht-Meldungen noch ausstehen (DSGVO-72h-Frist, Versicherungs-Meldung).
- **Handbuch-Versionen ohne Freigabe** (Approval offen).

Jeder Punkt hat einen **Klick-Pfad** direkt zur richtigen Detail-Seite — Sie müssen nicht erst durch die Sidebar suchen.

## Stammdaten-Counts

Eine Reihe kleiner Kacheln zeigt jeweils:

- **Mitarbeiter** — wie viele sind erfasst, wer ist Hauptansprechpartner.
- **Notfall-Level** — wie viele Klassifizierungen.
- **Krisenrollen** — die fünf Pflichtrollen mit aktuellem Besetzungsstand.

Klick führt jeweils zur Detail-Liste.

## Review-Erinnerung

Wenn der letzte Review des Notfallhandbuchs länger zurückliegt als der konfigurierte Review-Zyklus (Standard: 6 Monate), bekommen Sie eine **Erinnerung mit „Bestätigen"-Knopf**. Ein Klick markiert den heutigen Tag als Review-Datum und stellt die nächste Erinnerung um sechs Monate nach hinten.

## Compliance-Trend

Wenn das Compliance-Dashboard aktiv ist (Feature-Flag) und mindestens eine Woche Snapshots vorliegen, sehen Sie auf dem Dashboard einen **Mini-Chart** der letzten 30 Tage. So sehen Sie auf einen Blick, ob Sie sich verbessern oder verschlechtern.

## Wer was sieht

- **Member**: Stammdaten-Counts, Tagesliste, Live-Banner.
- **Admin**: zusätzlich Onboarding-Kachel, Compliance-Trend, Review-Erinnerung.
- **Owner**: identisch zu Admin, plus Berechtigung, das Team aufzulösen.
