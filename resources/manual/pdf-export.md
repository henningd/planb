## Wofür der PDF-Export

Im Ernstfall sind oft die App, die Cloud, das Internet nicht erreichbar — gerade dann brauchen Sie das Handbuch. Das PDF ist die **offline-taugliche Variante**: gedruckt im Schrank, auf dem Laptop, am USB-Stick.

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
