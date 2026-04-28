## Was Lessons Learned sind

Eine **Lessons Learned** (Singular: Lesson Learned) ist eine **strukturierte Auswertung** nach einer Übung oder einem realen Vorfall. Drei Fragen werden beantwortet:

1. **Was war die Ursache?** (Root Cause)
2. **Was lief gut?**
3. **Was lief nicht gut?**

Plus konkrete **Maßnahmen mit Verantwortlichem und Fälligkeit**.

Erreichbar über die Sidebar **„Ernstfall → Lessons Learned"**.

## Eine Auswertung anlegen

Knopf **„Neue Auswertung"**. Pflichtangaben:

- **Titel** — z. B. „Tabletop Ransomware Q2/2026 — Auswertung".

Optional, aber sinnvoll:

- **Bezug** — entweder ein **Vorfall** (Incident), ein **Szenario-Lauf** (Übung) oder beides.
- **Handbuch-Version** — wenn diese Auswertung in eine konkrete Iteration des Notfallhandbuchs einfließt.
- **Ursache** (Root Cause).
- **Was lief gut**.
- **Was lief nicht gut**.

## Maßnahmen / Action-Items

Pro Auswertung beliebig viele Maßnahmen:

- **Beschreibung** — z. B. „Stellvertretungs-Telefonnummern auf Krisen-Aushang nachpflegen".
- **Verantwortlicher** — Mitarbeiter aus der Liste.
- **Fälligkeit**.
- **Status** — Offen, In Bearbeitung, Erledigt, Verworfen.

Pro Status-Klick auf das Badge **wechselt der Status** — kein zweiter Klick nötig. Das macht das Pflegen schnell.

## Finalisierung

Solange die Auswertung läuft, ist sie **„offen"**. Wenn alle Maßnahmen geklärt sind, klicken Sie **„Finalisieren"** — die Auswertung wird mit Zeitstempel als abgeschlossen markiert.

Eine finalisierte Auswertung kann immer noch **bearbeitet** werden (es ist kein Lock), aber sie zählt nicht mehr als „in Arbeit".

## Bezug zur Versionshistorie

Wenn eine Auswertung einer Handbuch-Version zugeordnet ist, sehen Sie in der Versionshistorie pro Version ein **Badge mit der Anzahl Lessons**. So ist nachweisbar: „diese Version 1.3 enthält die Erkenntnisse aus dem Tabletop von Q2".

Versicherer und Auditoren mögen das.

## Wer was sehen darf

Lessons Learned sind für **alle Team-Mitglieder** lesbar. Anlegen, bearbeiten, finalisieren dürfen alle. Löschen darf nur **Admin und Owner**.

## Feature-Schalter

Lessons Learned kann pro Mandant abgeschaltet sein (`FEATURE_LESSONS_LEARNED_ENABLED=false`). Dann ist der Sidebar-Eintrag und die Routen nicht erreichbar.

> **Praxis-Hinweis**: Schreiben Sie die Lessons **innerhalb von 14 Tagen** nach dem Ereignis. Wenn man länger wartet, sind die Details vergessen — und die Auswertung wird belanglos.
