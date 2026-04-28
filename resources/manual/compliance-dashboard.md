## Worum es geht

Das Compliance-Dashboard berechnet einen **Reifegrad-Score** zwischen 0 und 100, der zeigt, wie gut Ihr Notfallmanagement aufgestellt ist. Der Score basiert auf elf Pflicht-Checks, orientiert an BSI 200-4 (Notfallmanagement) und NIS2 (kritische Infrastruktur).

Erreichbar über die Sidebar **„Platform → Compliance"** (Admin only).

## Die Reifegrad-Stufen

| Score | Label | Bedeutung |
|---|---|---|
| 90–100 | **Hervorragend** | Vollständig vorbereitet, geprüft, gepflegt. |
| 75–89 | **Gut** | Solide Grundlage, einzelne Bereiche zum Optimieren. |
| 50–74 | **Ausbaufähig** | Die wichtigsten Säulen stehen, größere Lücken vorhanden. |
| 25–49 | **Kritisch** | Kernfunktionen fehlen oder sind veraltet. |
| 0–24 | **Nicht vorbereitet** | Akute Lücken, Audit oder Krise wäre problematisch. |

## Die elf Checks

Aufgeteilt auf vier Kategorien:

**Organisation**
1. Pflichtrollen besetzt (Hauptpersonen).
2. Pflichtrollen mit Stellvertretung.
3. Standorte erfasst.

**Systeme & Abhängigkeiten**
4. Systeme klassifiziert (Notfall-Level).
5. Systeme mit Eigentümer und Operator.
6. System-Aufgaben mit RACI.
7. Kritische Risiken behandelt (Score ≥ 15 nicht in „identifiziert"/„bewertet").
8. Risiko-Reviews aktuell.

**Tests & Übungen**
9. Notfall-Tests durchgeführt.
10. Szenario-Übungen in den letzten 12 Monaten.

**Dokumentation & Vorlagen**
11. Notfall-Ressourcen aktuell.
*(plus weitere Checks zu Vorlagen, Versicherungen)*

Pro Check ein **Gewicht** (z. B. Pflichtrollen = 10, Sofortmittel = 4). Der Gesamtscore ist der gewichtete Durchschnitt über alle gewerteten Checks.

## Top-Aktionen

Aus den nicht erfüllten Checks errechnet das Dashboard die **drei wirksamsten nächsten Schritte** — sortiert nach „potenzieller Score-Gewinn = Gewicht × verlorene Punkte". So sehen Sie immer, wo Aufwand sich am meisten lohnt.

Pro Aktion ein direkter Klick-Pfad zur richtigen Detail-Seite.

## 30-Tage-Trend

Tägliche Snapshots werden automatisch durch einen Hintergrund-Job angelegt. Das Dashboard zeigt den Verlauf der letzten 30 Tage als Mini-Chart. Plötzliche Einbrüche (z. B. weil eine Pflichtrolle frei wurde, ein Test überfällig ist) werden so sichtbar.

Neben dem aktuellen Score sind die 7- und 30-Tage-Deltas mit **Trend-Pfeil und Vorzeichen** angegeben (↗ +5 Pkt., ↘ −2 Pkt., — ±0 Pkt.) — die Richtung ist damit unabhängig von Farbsehen und Graustufen-Druck eindeutig erkennbar.

## Bezug zu Standards

- **BSI 200-4** (Notfallmanagement): Pflichtrollen, Wiederanlauf, Tests sind die Säulen.
- **NIS2** (Network and Information Security): Asset-Inventar, Krisenorganisation, Vorfall-Meldung.
- **ISO 27001**: Risiko-Register füllt diese Säule.

Das Dashboard ist keine Zertifizierung, sondern eine **Selbst-Auswertung**. Wenn Sie eine echte ISO-27001-Zertifizierung anstreben, brauchen Sie zusätzlich einen externen Auditor.

## Wer was sehen darf

Compliance-Dashboard ist nur für **Admin und Owner** sichtbar.

## Feature-Schalter

Compliance-Dashboard kann pro Mandant abgeschaltet sein (`FEATURE_COMPLIANCE_ENABLED=false`).

> **Praxis-Hinweis**: Ziel ist nicht 100 % über Nacht. Realistisch: in den ersten zwei Monaten von 30 auf 60 hochfahren, dann pro Quartal 5 Punkte zulegen.
