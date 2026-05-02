## Wozu der Ausfallrechner gut ist

Wenn beim Verkaufsgespräch oder im Audit gefragt wird „**Was kostet es eigentlich, wenn das ERP einen Tag steht?**", möchten Sie keine Bauchschätzung abgeben. Der Ausfallrechner zieht die in jedem System gepflegten **„Ausfallkosten pro Stunde"** zusammen und multipliziert sie mit einer angenommenen Ausfalldauer — als belastbare Grundlage für Investitions-Entscheidungen, Risiko-Bewertungen und Versicherungs-Gespräche.

Erreichbar über die Sidebar **„Notfallhandbuch → Ausfallrechner"**.

## So benutzen Sie ihn

1. **Systeme auswählen**: Auf der linken Seite hakt der Bediener die Systeme an, die in dem durchgespielten Szenario ausfallen. Die Stundenkosten je System stehen direkt daneben (z. B. „2.000 €/h").
2. **Filter setzen** (optional): Standardmäßig zeigt die Liste nur Systeme, an denen ein Stundenkosten-Wert hinterlegt ist. Über die Checkbox „Nur Systeme mit hinterlegten Stundenkosten anzeigen" lässt sich die Liste auch auf alle Systeme erweitern — z. B. um zu erkennen, wo noch Kostenwerte fehlen.
3. **Ausfalldauer eingeben**: Stundenwert direkt eintippen oder einen der Schnellauswahl-Buttons drücken (1 h, 4 h, 8 h Arbeitstag, 24 h, 72 h, 168 h Woche). Bruchteile sind erlaubt — z. B. 0,5 für 30 Minuten.
4. **Ergebnis ablesen**: Rechts oben erscheint der geschätzte **Gesamtschaden** (Stundenrate × Dauer), darunter die Aufschlüsselung pro System.

## Schnell-Aktionen

- **„Alle mit Kosten"**: Markiert alle Systeme im Mandanten, an denen ein Stundenwert > 0 hinterlegt ist — gut für „Was wäre, wenn ALLES gleichzeitig steht?"-Szenarien.
- **„Alle sichtbaren"**: Wählt nur die in der aktuellen Filter-Ansicht sichtbaren Systeme aus.
- **„Auswahl löschen"**: Setzt die Auswahl zurück.

## Was die Berechnung *nicht* abbildet

Es ist eine **grobe Schätzung**, kein Versicherungs-Gutachten. Folgende Faktoren bleiben bewusst außen vor und sind im Einzelfall manuell zu ergänzen:

- **Tageszeit / Wochentag**: Ein Webshop-Ausfall am Black-Friday-Samstag ist deutlich teurer als ein Sonntag-Vormittag.
- **Saisonalität**: Hotellerie, Steuerberatung (Jahresabschluss-Saison), Einzelhandel (Q4) haben starke Schwankungen.
- **Vertragsstrafen / SLA-Pönalen** gegenüber Kunden.
- **Folgekosten**: Ruf-Schaden, Mehrarbeit zum Aufholen, Überstunden, Sonderschichten.
- **Behörden- oder Meldekosten** bei DSGVO- oder NIS2-Vorfällen.

Die Stundenkosten je System sollten daher als **gewichteter Durchschnittswert** über das Jahr gepflegt werden.

## Wo die Stundenkosten gepflegt werden

Pro System unter **„Notfallhandbuch → Systeme → System öffnen → Bearbeiten"** im Feld **„Ausfallkosten pro Stunde"**. Wert in Euro, ohne Komma. NULL oder 0 bedeutet: das System bleibt in der Rechnung mit 0 € drin (z. B. Brandmeldeanlage, Alarmanlage — diese verursachen keinen direkten Umsatzverlust, sind aber für andere Zwecke kritisch).

> **Tipp**: Ein realistischer Wert lässt sich aus dem **Tagesumsatz** des Unternehmens herleiten. Beispiel: 1 Mio. € Jahresumsatz ≈ 4.000 € pro Werktag ≈ 500 €/h. Der Wert wird dann pro System gewichtet, je nachdem wie zentral es für den Umsatz ist (Webshop 100 %, Telefonanlage vielleicht 30 %).

## Wozu das in der Praxis nützt

- **Investitions-Entscheidung**: „Lohnt sich ein zweiter Internet-Anschluss für 80 €/Monat?" Wenn der einzelne Ausfalltag 5.000 € kostet und alle 3 Jahre einer auftritt, lautet die Antwort meist: ja.
- **Versicherungs-Gespräch**: Vermittler fragen oft nach der **„maximal möglichen Schadenshöhe"** für die Cyberversicherung. Der Ausfallrechner liefert die Antwort sauber dokumentiert.
- **Audit / Wirtschaftsprüfer**: Zeigt, dass die Geschäftsführung sich mit Schadenshöhen auseinandergesetzt hat — ein Pflicht-Element bei BSI 200-4 BIA und ISO 22301.
- **Übung im Krisenstab**: Mit dem Rechner lässt sich live ausrechnen, wie hoch der Druck steigt, je länger ein Wiederanlauf dauert.

## Wer was sehen darf

Der Ausfallrechner ist für **alle Team-Mitglieder** zugänglich. Es werden keine Werte gespeichert — die Auswahl ist eine reine Live-Berechnung in der aktuellen Sitzung.
