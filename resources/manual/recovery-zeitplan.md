## Was der Zeitplan zeigt

Der Recovery-Zeitplan ist ein **Gantt-Diagramm**, das die Wiederanlauf-Reihenfolge aller Systeme nach einem hypothetischen Total-Ausfall zeigt. Aus den **Abhängigkeiten** und **RTO**-Werten errechnet das System die optimale Reihenfolge.

Erreichbar über die Sidebar **„Notfallhandbuch → Recovery-Zeitplan"**.

## So lesen Sie das Diagramm

- **Y-Achse**: alle Systeme, sortiert nach Wiederanlauf-Reihenfolge.
- **X-Achse**: Zeit ab Stunde 0 (Beginn des Wiederanlaufs).
- **Balkenlänge**: das RTO des Systems (wie lange darf der Ausfall maximal dauern).
- **Farbe + Symbol**: Notfall-Level wird sowohl über die Balken-Farbe (rot = kritisch, orange = hoch, blau = mittel, grün = niedrig) als auch über ein Heroicon links neben jeder Zeile signalisiert (Schild mit Ausrufezeichen / Warn-Dreieck / Schild mit Häkchen / Häkchen-Kreis). So bleibt die Stufe auch bei Rot-Grün-Schwäche oder im Graustufen-Druck eindeutig erkennbar.

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
