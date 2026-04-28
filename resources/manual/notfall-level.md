## Wozu Notfall-Level

Nicht jedes System ist gleich wichtig. Ein Cloud-Speicher ist im Tagesgeschäft hilfreich, aber wenn er einen Tag down ist, bricht nicht das Geschäft zusammen. Die Warenwirtschaft ist anders — wenn die einen Tag down ist, kann der Verkauf nicht laufen.

Notfall-Level sind die **Klassifizierungs-Stufen**, mit denen Sie diese Unterschiede sichtbar machen. Pro System genau ein Level.

Erreichbar über die Sidebar **„Notfallhandbuch → Notfall-Level"**.

## Standard-Level

Bei Erst-Einrichtung gibt es vier vorgeschlagene Level (Sie können sie ändern):

| Level | Beschreibung | Typische RTO |
|---|---|---|
| **Kritisch** | Geschäftsbetrieb steht binnen Stunden | 0–4 Stunden |
| **Hoch** | Spürbarer Geschäftsverlust ab einem Tag | 4–24 Stunden |
| **Mittel** | Erträglich für ein paar Tage | 1–7 Tage |
| **Niedrig** | Erst nach Wochen problematisch | > 7 Tage |

Diese Beschreibungen sind nur Empfehlungen — Sie können eigene Level anlegen, umbenennen, oder mehr Stufen einführen.

## Eigene Level anlegen

Knopf **„Neues Level"**. Pflichtangaben:

- **Name** — kurz und sprechend.
- **Reihenfolge** — kleinste Zahl = wichtigster Level.

Empfohlen:

- **Beschreibung** — was bedeutet das Level konkret?
- **Farbe** — fürs Sichtbarmachen in der UI (rot, orange, gelb, grün, …).

## Wo das Level wirkt

- **System-Liste**: ein Filter nach Notfall-Level zeigt nur die kritischen Systeme.
- **Recovery-Gantt**: Reihenfolge nach Level (kritische zuerst).
- **PDF-Handbuch**: Systeme werden nach Level gruppiert dargestellt.
- **Dashboard**: nur kritische Systeme werden in den Schnellzugriff aufgenommen.

## Wer was sehen darf

Notfall-Level sind für **alle Team-Mitglieder** lesbar. Anlegen, ändern, löschen darf nur **Admin und Owner**. Wenn Sie ein Level löschen, das Systemen zugeordnet ist, werden diese auf „kein Level" gesetzt — keine Daten gehen verloren, aber die Klassifizierung muss neu vergeben werden.

> **Praxis-Hinweis**: Bleiben Sie bei den vier Standard-Levels, wenn möglich. Mehr als sechs Stufen verwirren mehr als sie helfen.
