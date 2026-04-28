## Worum es geht

Das Mandanten-Archiv ist ein **vollständiger ZIP-Export** aller Daten Ihres Mandanten. Anwendungsfälle:

- **DSGVO-Auskunftsanfrage** — wenn ein Mitarbeiter wissen will, welche Daten gespeichert sind.
- **Datenrückgabe bei Vertragsende** — wenn Sie zu einem anderen Anbieter wechseln.
- **Backup vor riskanten Änderungen** — bevor Sie z. B. eine große Reorganisation durchführen.
- **Audit-Vorbereitung** — alles, was der Auditor sehen will, in einem Download.

Erreichbar über die Sidebar **„Einstellungen → System"** (Admin only), Bereich „Daten-Export".

## Inhalt des ZIPs

| Datei | Inhalt |
|---|---|
| `daten.json` | Alle Stammdaten (Mitarbeiter, Systeme, Aufgaben, Risiken, Lessons …) als strukturiertes JSON. |
| `audit-log.csv` | Komplette Aktivitäten-Historie als CSV. |
| `handbook-versions/*.pdf` | Alle revisionssicheren Handbuch-PDFs. |
| `README.txt` | Übersichts-Datei mit Stand, Mandantname und Inhaltsbeschreibung. |

## Knopf finden

In den System-Settings unter dem Bereich **„Daten-Export"** sehen Sie zwei Knöpfe:

- **„Vollständiges Archiv (ZIP)"** — der Komplett-Export.
- **„Backup herunterladen"** — nur die Daten als JSON, ohne PDFs und Audit-Log.

Beides führt zu einem direkten Download im Browser.

## Größe und Dauer

- Bei kleinen Mandanten (10 Mitarbeiter, 15 Systeme) ist das ZIP unter 5 MB und in wenigen Sekunden fertig.
- Bei großen Mandanten mit vielen Handbuch-Versionen und PDFs kann es 50+ MB werden und 30 Sekunden dauern.

Während der Erzeugung blockiert der Browser-Tab — Sie sehen das durch den Download-Indikator unten.

## DSGVO-Aspekt

Wenn ein Mitarbeiter eine **Auskunft nach Art. 15 DSGVO** verlangt, können Sie ihm das ZIP geben. Es enthält alle personenbezogenen Daten, die Sie zu ihm gespeichert haben (Mitarbeiter-Eintrag, Krisenrolle, Audit-Spur seiner Anmeldungen).

Achtung: das ZIP enthält **alle Mandanten-Daten**, nicht nur die einer einzigen Person. Wenn Sie nur die einer Person liefern wollen, müssen Sie das JSON nachträglich filtern.

## Wer das tun darf

Nur **Admin und Owner**.

## Sicherheit

Das ZIP ist eine sensible Datei — alle Mitarbeiter-Daten, alle Versicherungen, alle Vertragsnummern. Bewahren Sie es im Tresor oder verschlüsselt auf, nicht offen auf dem USB-Stick.

> **Praxis-Hinweis**: Erzeugen Sie **vor jeder größeren Reorganisation** ein Archiv. Wenn etwas schiefgeht, haben Sie den Stand vor dem Eingriff.
