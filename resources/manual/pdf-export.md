## Wofür der PDF-Export

Im Ernstfall sind oft die App, die Cloud, das Internet nicht erreichbar — gerade dann brauchen Sie das Handbuch. Das PDF ist die **offline-taugliche Variante**: gedruckt im Schrank, auf dem Laptop, am USB-Stick.

## Drei Export-Typen

Über den Knopf **„Exportieren"** auf der Seite [Handbuch-Versionen](handbuch-erstellen) erzeugen Sie das PDF live in drei fachlichen Zuschnitten — je nachdem, für wen es gedacht ist. Diese Exporte sind eine tagesaktuelle Momentaufnahme und werden nicht revisionssicher abgelegt.

**Typ 1 — Ernstfall-Handbuch.** Die schlanke, rein operative Fassung für den Papierordner im Server-Raum und den Krisenstab. Sie enthält nur, was im Ernstfall zählt (Krisenstab, Kontakte, Systeme, Szenarien, Wiederanlauf, Kommunikations-Vorlagen, Sofortmittel) — **ohne Governance- und Audit-Kapitel**. Kein Ballast, den man in der Lage nicht liest.

**Typ 2 — Audit-Bericht.** Der Nachweis-Teil für Geschäftsführung, Prüfung und Auditor: **Reifegrad, Risiken, Maßnahmen, Aufgaben, Tests, Schulungen, Lessons Learned, Management Review, BIA (Business Impact Analyse) und Versicherungsprüfung**. Genau die Kapitel, die im Ernstfall-Handbuch bewusst fehlen. Dieser Export ist nur verfügbar, wenn das BIA-/Audit-Modul aktiv ist.

**Typ 3 — Vollständiger Export.** Handbuch und Audit-Bericht in **einem** Dokument, mit Seitenumbruch dazwischen. Gedacht für Archiv und Übergabe (z. B. an Nachfolger, Prüfer oder zur Ablage in der Compliance-Akte), wenn wirklich alles in einer Datei liegen soll.

Davon unberührt bleibt das **revisionssichere, versionierte PDF**: Es wird weiterhin einmalig pro freigegebener Version erzeugt, mit SHA-256-Hash signiert und dauerhaft abgelegt (siehe unten). Die drei Export-Typen ersetzen es nicht, sondern ergänzen es um zweckgebundene, jederzeit neu erzeugbare Fassungen.

## Wann ein PDF erzeugt wird

- Bei Freigabe einer Handbuch-Version (sofern Auto-PDF aktiviert) — siehe „Handbuch-Versionen".
- Manuell über den Knopf **„PDF jetzt erzeugen"** auf der Versions-Karte.

## Was im PDF steht

In strukturierten Sektionen:

1. **Deckblatt** mit Firmenname, Stand-Datum, Versionsnummer.
2. **Zusammenfassung** (Notfall-Quick-Card mit Pflichtrollen-Telefonen).
3. **Firma und Standorte**.
4. **Krisenstab** mit allen Pflichtrollen.
5. **Mitarbeiter** mit Kontaktdaten.
6. **Dienstleister** mit Hotlines.
7. **Systeme** gruppiert nach Kategorie und Notfall-Level, mit RTO/RPO/Eigentümer.
8. **Wiederanlauf-Reihenfolge** als Tabelle.
9. **Szenarien** mit Schritt-Listen.
10. **Kommunikations-Vorlagen**.
11. **Sofortmittel**.
12. **Versicherungen** (nur in Admin-PDF).
13. **Audit-Auszug** der letzten 30 Tage.

## Revisionssicherheit

Jedes PDF wird mit einem **SHA-256-Hash** signiert, der im Footer steht. Wenn jemand das PDF nachträglich verändert, ändert sich der Hash. So ist nachweisbar, dass das in der Akte abgelegte PDF unverändert ist.

Die Hash-Anzeige im Footer kann pro Mandant abgeschaltet werden (System-Settings → SHA-256 im PDF-Footer).

## Papierformat

System-Settings erlauben die Wahl zwischen:

- **A4** (Standard in Deutschland).
- **US Letter** (für US-Kunden mit eigenen Druckern).

## Wer das PDF herunterladen darf

Im Anwendungs-Modus: nur **Admin und Owner**. Wenn das PDF über einen **Freigabelink** geteilt wurde, kann auch ein externer Auditor es ohne App-Konto sehen — bis der Link abläuft oder widerrufen wird.

## Drucken oder digital

Beides ist sinnvoll:

- **Gedruckt** im Server-Raum, beim Notfallbeauftragten zu Hause, im Schließfach.
- **Digital** auf dem privaten Smartphone des Notfallbeauftragten.

> **Praxis-Hinweis**: Ein 80-seitiges PDF lesen Sie im Ernstfall nicht — die ersten 5 Seiten (Deckblatt, Zusammenfassung, Krisenstab) sollten alles Wichtige enthalten. Die App ist darauf ausgelegt.
