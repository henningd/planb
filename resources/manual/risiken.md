## Was das Risiko-Register ist

Im Risiko-Register sammeln Sie **Bedrohungen**, die für Ihr Unternehmen relevant sind, und behandeln sie strukturiert. Pro Risiko: Eintrittswahrscheinlichkeit (1–5), Schadenshöhe (1–5), Score = Produkt. Maßnahmen mit Status. Restrisiko nach Behandlung. Eigentümer. Review-Termin.

Erreichbar über die Sidebar **„Compliance → Risiken"** (Admin only).

## Die Heatmap

Oben auf der Seite sehen Sie eine **5×5-Matrix**:

- **Y-Achse**: Eintrittswahrscheinlichkeit (1 unten = unwahrscheinlich, 5 oben = sehr wahrscheinlich).
- **X-Achse**: Schaden (1 links = vernachlässigbar, 5 rechts = existenzbedrohend).
- **Farbe der Zellen**: Severity-Level (rot/orange/gelb/zinc).
- **Zahl in der Zelle**: Anzahl Risiken in dieser Konstellation.

Klick auf eine Zelle filtert die Liste darunter — z. B. nur die Risiken mit Score ≥ 15 (oben rechts).

## Ein Risiko anlegen

Knopf **„Neues Risiko"**. Pflichtangaben:

- **Titel** — kurz und sprechend, z. B. „Ransomware-Befall des Datei-Servers".
- **Kategorie** — Technisch, Organisatorisch, Operativ, Rechtlich/Compliance, Dritte/Lieferkette.
- **Eintrittswahrscheinlichkeit** (1–5).
- **Schaden** (1–5).

Optional, aber wertvoll:

- **Beschreibung** — Szenario, Auslöser, betroffene Systeme.
- **Status** — Identifiziert, Bewertet, Behandelt, Akzeptiert, Übertragen, Geschlossen.
- **Strategie** — Reduzieren, Akzeptieren, Übertragen (Versicherung), Vermeiden.
- **Eigentümer** — App-Benutzer aus dem Team.
- **Review-Fälligkeit** — wann soll das Risiko neu bewertet werden?
- **Verknüpfte Systeme** — welche Systeme sind betroffen?

## Maßnahmen pflegen

Auf der Risiko-Detail-Seite legen Sie Maßnahmen an. Pro Maßnahme:

- **Titel** und **Beschreibung**.
- **Verantwortlicher** (Mitarbeiter).
- **Zieldatum**.
- **Status** — Geplant, In Umsetzung, Umgesetzt, Verifiziert.

Status-Klick auf das Badge wechselt zur nächsten Stufe. Bei Umsetzung wird automatisch das `implemented_at`-Datum gesetzt.

## Restrisiko

Wenn Maßnahmen wirken, sinkt das Risiko. Sie tragen die **Restwahrscheinlichkeit** und den **Restschaden** ein, sobald die Maßnahmen umgesetzt sind. So ist nachweisbar: „Wir hatten Score 20, mit der Maßnahmen-Wirkung sind wir auf Score 4 runter".

## Maßnahmen in die Aufgaben-Inbox überführen

Pro Maßnahme gibt es einen **„Zur Inbox"-Knopf**. Klick legt eine System-Aufgabe an, die in der Aufgaben-Inbox auftaucht. Voraussetzung: Das Risiko ist mit mindestens einem System verknüpft. Sehr praktisch, weil Maßnahmen so im Tagesgeschäft erinnert werden.

## Compliance-Bezug

Zwei Compliance-Checks beziehen sich aufs Risiko-Register:

- **Kritische Risiken behandelt**: Risiken mit Score ≥ 15 müssen behandelt sein, nicht im Status „identifiziert" oder „bewertet" verharren.
- **Reviews aktuell**: Kein Risiko darf einen überfälligen Review-Termin haben.

Beide tragen zum Reifegrad-Score bei.

## Wer was sehen darf

Risiken sind nur für **Admin und Owner** sichtbar — sie enthalten oft sensible Bewertungen.

## Feature-Schalter

Risiko-Register kann pro Mandant abgeschaltet sein (`FEATURE_RISK_REGISTER_ENABLED=false`).

> **Praxis-Hinweis**: Erfassen Sie 5–10 Risiken am Anfang, nicht 50. Lieber wenige gut behandelte Risiken als viele liegen gelassene.
