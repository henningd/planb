## Was der Zeitplan zeigt

Der Recovery-Zeitplan ist ein **Gantt-Diagramm**, das die Wiederanlauf-Reihenfolge aller Systeme nach einem hypothetischen Total-Ausfall zeigt. Aus den **Abhängigkeiten** und **RTO**-Werten errechnet das System die optimale Reihenfolge.

Erreichbar über die Sidebar **„Notfallhandbuch → Recovery-Zeitplan"**.

## So lesen Sie das Diagramm

- **Y-Achse**: alle Systeme, sortiert nach Wiederanlauf-Reihenfolge.
- **X-Achse**: Zeit ab Stunde 0 (Beginn des Wiederanlaufs).
- **Balkenlänge**: das RTO des Systems (wie lange darf der Ausfall maximal dauern).
- **Farbe + Symbol**: Notfall-Level wird sowohl über die Balken-Farbe (rot = kritisch, orange = hoch, blau = mittel, grün = niedrig) als auch über ein Heroicon links neben jeder Zeile signalisiert (Schild mit Ausrufezeichen / Warn-Dreieck / Schild mit Häkchen / Häkchen-Kreis). So bleibt die Stufe auch bei Rot-Grün-Schwäche oder im Graustufen-Druck eindeutig erkennbar.

## Skalen-Modus: Logarithmisch oder Linear

Oben rechts in der Zeitleiste gibt es einen Toggle-Schalter zwischen **Logarithmisch** (Standard) und **Linear**:

- **Logarithmisch**: Kurze Wiederanlauf-Zeiten (z. B. 15 Minuten Stromversorgung) und lange (z. B. 72 Stunden GPS-Ortung) liegen optisch ähnlich weit auseinander. Damit sind auch die kritischsten Sofort-Recovery-Systeme klar erkennbar — sie würden auf einer linearen 72-h-Skala in einem winzigen Strich verschwinden. Die Tick-Beschriftungen sind die vertrauten Zeit-Sprünge: 15 min, 30 min, 1 h, 2 h, 4 h, 8 h, 24 h, 48 h, 72 h.
- **Linear**: Die Balken-Längen sind exakt proportional zur Dauer — ein 8-h-System ist doppelt so breit wie ein 4-h-System. Wenn Sie genaue Längen-Vergleiche brauchen (z. B. „wie viel länger dauert Wiederanlauf X gegenüber Y?"), wechseln Sie hierhin.

In beiden Modi haben Balken eine Mindest-Breite von 28 Pixel, sodass selbst sehr kurze Wiederanlauf-Zeiten klick- und sichtbar bleiben. Wenn die echte proportionale Breite kleiner als das Minimum ist, zeigt sich der **Rest des Balkens als diagonal schraffierte graue Fülle** — so erkennen Sie auf einen Blick, welcher Teil des Balkens die echte Dauer abbildet und welcher nur Sichtbarkeits-Filler ist.

## Info-Symbol pro Balken

Am rechten Ende jedes Balkens sitzt ein kleines runde **Info-Symbol**. Beim Hover oder Klick darauf öffnet sich eine Detail-Karte mit:

- **System-Name**
- **Start** (Wiederanlauf-Beginn nach Vorfall)
- **Ende** (System wieder verfügbar)
- **RTO** (gesetzte Wiederanlauf-Zeit, * markiert wenn als 60-min-Default angenommen)
- **Stufe** (Notfall-Level)

Klick lässt die Karte offen, ein Klick außerhalb schließt sie wieder.

Systeme, die voneinander abhängen, werden so angeordnet, dass die Abhängigkeit zuerst läuft.

## Wozu das gut ist

- **Im Ernstfall**: Sie sehen in welcher Reihenfolge der Krisenstab arbeitet.
- **Beim Audit**: Sie können dem Wirtschaftsprüfer zeigen, dass Sie eine durchdachte Wiederanlauf-Strategie haben.
- **In der Übung**: Sie können den Zeitplan als Vorlage für eine Tabletop-Übung nutzen.

## Daten-Voraussetzungen

Damit der Zeitplan sinnvoll aussieht, brauchen Sie:

- Mindestens 5–10 Systeme erfasst.
- **RTO-Werte** an den meisten Systemen.
- **Abhängigkeiten** zwischen den Systemen gepflegt.

Wenn die Abhängigkeiten leer sind, sortiert der Zeitplan rein nach Notfall-Level.

## Wer was sehen darf

Der Recovery-Zeitplan ist für **alle Team-Mitglieder** lesbar.

> **Praxis-Hinweis**: Drucken Sie den Zeitplan aus, wenn Sie eine **Tabletop-Übung** machen — er ist die ideale Vorlage, um den Wiederanlauf in der Reihenfolge durchzugehen.
