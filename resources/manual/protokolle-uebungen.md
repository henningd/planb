## Was Szenario-Läufe sind

Ein **Szenario-Lauf** ist die konkrete Durchführung eines Szenarios — entweder als geplante Übung oder als echter Notfall. Der Lauf hat einen Anfang, eine Schritt-Liste zum Abhaken und ein Ende.

Erreichbar über die Sidebar **„Ernstfall → Protokolle und Übungen"**.

## Lauf starten

Auf der Seite Knopf **„Lauf starten"** (oder direkt aus der Szenario-Detailseite). Auswahl:

- **Tabletop-Übung** — kein rotes Banner, keine Eskalation, kein automatischer Vorfall.
- **Echte Lage** — rotes Banner für alle Team-Mitglieder, Krisen-Cockpit aktivierbar, Vorfall verknüpfbar.

Optional ein **freier Titel** (z. B. „Tabletop Q2/2026 — Ransomware-Übung").

## Detail-Ansicht eines Laufs

Pro Schritt gibt es eine **Checkbox**. Wenn Sie sie anklicken, wird der Schritt mit Zeitstempel und Bearbeiter-Name als „erledigt" markiert. Pro Schritt ist auch eine **Notiz** möglich — z. B. „Hat 12 Min gebraucht weil Hotline besetzt war".

## War-Room: Live-Updates

Wenn mehrere Personen gleichzeitig auf der Detail-Seite sind, sehen sie sich gegenseitig in einer **Anwesenheits-Liste** oben. Jeder Klick auf eine Checkbox wird **live an alle anderen** gesendet — das ist der War-Room-Effekt.

Die Technik dahinter ist Laravel Reverb (WebSocket). Damit das funktioniert, muss der Reverb-Server laufen (`php artisan reverb:start`). Falls nicht: die Seite funktioniert weiter, aber ohne Live-Updates — Sie müssen manuell neu laden.

## Lauf beenden

Knopf **„Lauf beenden"** oben rechts. Sie können dabei eine **Auswertung** schreiben (1–2 Sätze) — z. B. „Lauf war erfolgreich, alle Schritte wie geplant. Notiz: Hotline IT-Dienstleister muss aktualisiert werden."

Nach Beenden:

- Das rote Banner verschwindet (falls echte Lage war).
- Der Lauf landet in der **Protokoll-Liste** unten.
- Sie können danach eine **Lessons Learned** verknüpfen (siehe Kapitel).

## Lauf abbrechen

Falls die Lage doch nicht so schlimm war oder die Übung beendet werden soll, ohne sie als „abgeschlossen" zu zählen: Knopf **„Lauf abbrechen"**. Im Audit-Log wird der Abbruch dokumentiert.

## Wer was sehen darf

Läufe sind für **alle Team-Mitglieder** sichtbar — der Krisenstab muss Zugang haben. Auswertungen schreiben können alle. Löschen darf nur **Admin und Owner**.

## Lauf-Statistiken

Auf der Übersichtsseite sehen Sie Zähler:

- **Durchgeführte Übungen** in den letzten 12 Monaten.
- **Echte Lagen** in den letzten 12 Monaten.
- **Ausstehende Auswertungen** (beendet, aber keine Lessons Learned).

Versicherer fragen oft nach diesen Zahlen — eine durchgeführte Übung pro Halbjahr ist ein guter Richtwert.

> **Praxis-Hinweis**: Eine **echte Lage** ist im System ein scharfes Werkzeug. Verwenden Sie sie nur, wenn der Krisenstab tatsächlich aktiv arbeitet — sonst entsteht Alarmmüdigkeit. Tests bitte als „Tabletop-Übung" markieren.
