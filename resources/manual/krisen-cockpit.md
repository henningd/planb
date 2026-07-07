## Was das Cockpit ist

Das Krisen-Cockpit ist ein **reduziertes Live-Dashboard** für den Ernstfall. Wenn ein Notfall eintritt, brauchen Sie nicht alle Funktionen der Plattform — Sie brauchen drei Dinge:

1. **Wer entscheidet** und ist erreichbar.
2. **Welche Schritte** stehen aus.
3. **Welche Pflichten** laufen gerade.

Genau das zeigt das Cockpit auf einer Seite. Erreichbar über die Sidebar **„Ernstfall → Krisen-Cockpit"**, oder durch Klick auf das **rote Notfall-Banner** oben am Bildschirm.

## Die fünf Sektionen

1. **Lage-Header** — Titel des aktuellen Szenario-Laufs, Modus (Tabletop / echte Lage), Start-Zeit, Live-Counter („läuft seit 2h 14m").
2. **Krisenstab** — die fünf Pflichtrollen mit Hauptperson und Stellvertretung. Telefonnummern direkt anklickbar (`tel:`).
3. **Wiederanlauf-Reihenfolge** — die kritischen Systeme in Wiederanlauf-Reihenfolge mit Hotline-Kontakten.
4. **Schritte** — der laufende Szenario-Lauf mit allen Schritten zum Abhaken.
5. **Kommunikation** — die hinterlegten Vorlagen, direkt aus dem Cockpit versendbar (siehe unten).
6. **Meldepflichten** — wenn ein Vorfall existiert, die offenen Pflichtmeldungen mit Countdown.

## Kommunikation: Vorlagen direkt senden

In der Sektion **„Kommunikation"** liegen die [Kommunikations-Vorlagen](/handbuch/kommunikations-vorlagen) als Karten — mit Badge für Zielgruppe und Kanal und einer kurzen Textvorschau. An jeder Karte gibt es:

- **„Vorlage öffnen"** — zeigt den vollständigen, mit Platzhaltern gefüllten Text zum Vorlesen oder Kopieren.
- **„Per SMS senden"** bzw. **„Per E-Mail senden"** (je nach Kanal) — öffnet den Versand-Dialog **direkt im Cockpit**: Empfänger auswählen, **„Senden vorbereiten"**, dann der rote Bestätigungsklick. So lösen Sie die Alarmierung im Ernstfall ohne Seitenwechsel aus.

Empfänger sind die Mitarbeiter mit hinterlegter Mobilnummer (SMS) bzw. E-Mail-Adresse — Details im Kapitel [Kommunikations-Vorlagen](/handbuch/kommunikations-vorlagen).

## Wer hat ausgelöst?

Der Lage-Header zeigt immer den Auslöser: bei manueller Auslösung den **Namen** der Person (aus Web oder App), bei automatischer Auslösung durch das Monitoring das Badge **„Automatisch · IT-Monitoring"** mit dem auslösenden Host. Das ändert die erste Reaktion: Bei einem automatischen Alarm zuerst den System-Status prüfen, statt nach einem menschlichen Auslöser zu suchen.

## Mehrere Notfälle gleichzeitig

Laufen mehrere Abläufe parallel (z. B. eine Übung und ein echter Alarm, oder Vorfälle an zwei Standorten), zeigt das Cockpit oben einen **Umschalter**: eine Leiste mit allen aktiven Lagen — jeweils mit Szenario-Name, Startzeit und ÜBUNG-Kennzeichnung. Ein Klick wechselt das komplette Lagebild (Checkliste, Live-Updates, Meldepflichten, Beenden-Knopf) auf den gewählten Ablauf.

- Standardmäßig ist der **zuletzt gestartete** Notfall ausgewählt; startet während der Arbeit ein weiterer, bleibt Ihre Auswahl bestehen.
- Der **Beenden-Knopf** wirkt immer nur auf den gerade ausgewählten Ablauf; danach springt das Cockpit automatisch zum nächsten aktiven.
- Die Notfall-App zeigt parallel laufende Notfälle ohnehin alle als Liste auf der Startseite.

## Im Hintergrund: Live-Updates

Wenn mehrere Personen gleichzeitig auf dem Cockpit oder dem Szenario-Lauf-Detail sind, sehen alle in Echtzeit, wer welchen Schritt erledigt hat. Das ist der **War-Room** (siehe Kapitel „Protokolle und Übungen").

## Ausfallkosten-Rechner

In der Wiederanlauf-Sektion sehen Sie pro System die geschätzten **Ausfallkosten pro Stunde**. Im Cockpit wird daraus pro Lage live aufaddiert: „Bisher entstandener Schaden: 3.420 €". Hilft, die richtigen Eskalations-Entscheidungen zu treffen.

## Wann sehe ich das Cockpit?

Das Cockpit ist nur sinnvoll, wenn ein **aktiver Szenario-Lauf** läuft. Wenn keiner aktiv ist, sehen Sie eine leere Seite mit dem Knopf „Notfall melden" — der startet einen neuen Szenario-Lauf.

## Schließen oder beenden

Wenn die Lage vorbei ist, gehen Sie auf den Szenario-Lauf-Detail (Sidebar → Protokolle und Übungen) und klicken **„Lauf beenden"**. Damit ist die Lage geschlossen, das Banner verschwindet, das Cockpit ist wieder leer.

## Feature-Schalter

Das Cockpit kann pro Mandant abgeschaltet sein (`FEATURE_INCIDENT_MODE_ENABLED=false`). Dann gibt es weder Banner noch Sidebar-Eintrag.

> **Praxis-Hinweis**: Üben Sie das Cockpit mindestens **einmal pro Jahr** mit einer kompletten Tabletop-Übung. Bei der echten Lage muss jeder im Krisenstab das Cockpit kennen — sonst geht Zeit verloren.
