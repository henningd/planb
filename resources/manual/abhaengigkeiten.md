## Worum es geht

Systeme hängen voneinander ab. Die Warenwirtschaft braucht den Server, der Server braucht Strom und Internet, das Internet braucht den Router. Wenn das nicht aufgeschrieben ist, wird im Ernstfall in falscher Reihenfolge wieder hochgefahren — Warenwirtschaft, dann merkt man, der Server ist down, dann merkt man, der Strom fehlt.

Die Abhängigkeits-Visualisierung macht diese Kette sichtbar. Erreichbar über die Sidebar **„Notfallhandbuch → Abhängigkeiten"**.

## Die Visualisierung

Auf der Seite sehen Sie alle Systeme als **Knoten** in einem Graphen, verbunden durch **Pfeile** in Richtung der Abhängigkeit. Pfeil von „Warenwirtschaft" nach „Server" bedeutet: Warenwirtschaft braucht Server.

Sie können:

- **Mit dem Mausrad zoomen** — schnell rein/raus.
- **Mit Klick + Ziehen** den Graphen verschieben.
- **Auf einen Knoten klicken** — direkter Sprung zur System-Detailseite.
- Mit den **Plus/Minus-Buttons** rechts oben den Zoom feinjustieren.
- Mit **„1:1"** auf 100 % Zoom zurücksetzen.

## Abhängigkeiten pflegen

Abhängigkeiten werden **direkt am System** gepflegt, nicht in dieser Visualisierung — die Visualisierung zeigt nur, was schon erfasst ist. Auf der System-Edit-Seite gibt es den Tab **„Abhängigkeiten"**: dort wählen Sie die Systeme aus, von denen das aktuelle System abhängt.

## Tipps für saubere Abhängigkeiten

- **Nicht jede Abhängigkeit erfassen**. Wenn alles vom Strom abhängt, ist das eine triviale Abhängigkeit, die den Graphen unleserlich macht. Pflegen Sie nur die Abhängigkeiten, die im Wiederanlauf wirklich eine Reihenfolge erzwingen.
- **Vom Detail zum Allgemeinen**. Erst die Anwendungen erfassen (Warenwirtschaft, E-Mail), dann die Infrastruktur darunter (Server, Internet) — die Visualisierung wird sauberer.
- **Keine Zyklen**. „A hängt von B, B hängt von A" ist immer ein Indiz für eine unklare Architektur, die Sie sowieso lieber sauber durchdenken sollten.

## Feature-Schalter

Die Abhängigkeits-Visualisierung kann pro Plattform abgeschaltet sein (`FEATURE_DEPENDENCIES_ENABLED=false`). In dem Fall sind die Abhängigkeiten zwar weiter pflegbar, aber die Visualisierung ist nicht erreichbar.
