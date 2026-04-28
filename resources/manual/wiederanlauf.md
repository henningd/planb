## Was hier passiert

Die Wiederanlauf-Seite ist eine **kompakte Tabelle aller kritischen Systeme** in der Reihenfolge, in der sie nach einem Total-Ausfall wieder hochgefahren werden sollen. Anders als der Recovery-Gantt (graphische Darstellung) ist das hier eine reine Liste — drucktauglich, schnell zu lesen, im Ernstfall.

Erreichbar über die Sidebar **„Ernstfall → Wiederanlauf"**.

## Was die Liste zeigt

Pro System:

- **Reihenfolge-Nummer** — von 1 bis N.
- **System-Name** — Klick führt zur Detail-Seite mit Hotlines.
- **Verantwortliche/r** (Rolle oder Person) — wer macht das?
- **Dienstleister** — falls extern, mit Hotline.
- **RTO** — wie lange darf maximal gebraucht werden.
- **Status** (während eines aktiven Laufs): noch nicht / läuft / fertig.

## Wie die Reihenfolge entsteht

Aus zwei Datenquellen:

1. **Notfall-Level** — kritische Systeme zuerst, dann hohe, dann mittlere.
2. **Abhängigkeiten** — wenn System A von B abhängt, läuft B vorher.

Das System errechnet daraus eine zyklenfreie Topologie und gibt die Liste in dieser Reihenfolge zurück. Wenn keine Abhängigkeiten gepflegt sind, wird rein nach Notfall-Level sortiert.

## Auf einer Krisen-Übung verwenden

Drucken Sie diese Liste aus und legen Sie sie auf den Tisch. Während des Tabletop arbeiten Sie sie ab — wer ruft wen an, wer macht was, wann ist das System wieder da. Die Lücken in der Liste (fehlende Hotlines, fehlende Verantwortliche) werden so sofort sichtbar.

## Im Ernstfall

Wenn ein Total-Ausfall passiert, ist diese Liste der **erste Anker**. Idealerweise liegt sie ausgedruckt am Schwarzen Brett im Server-Raum — dann brauchen Sie keine App, kein Internet, keinen Strom, um sie zu lesen.

## Wer was sehen darf

Wiederanlauf ist für **alle Team-Mitglieder** lesbar.

> **Praxis-Hinweis**: Drucken Sie die Wiederanlauf-Liste **monatlich** aus und legen Sie sie an drei Orten ab: Hauptsitz, Heim-Büro Geschäftsführung, externes Schließfach. So überlebt sie auch ein Hochwasser.
