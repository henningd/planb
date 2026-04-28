## Was eine Handbuch-Version ist

Eine **Handbuch-Version** ist ein **eingefrorener Schnappschuss** aller Stammdaten zu einem bestimmten Zeitpunkt. Wenn Sie heute eine Version 1.0 anlegen und später Mitarbeiter ändern, bleibt die 1.0 unverändert — sie ist und bleibt der Stand vom Anlage-Tag.

Erreichbar über die Sidebar **„Team & Freigaben → Versionshistorie"** (Admin only).

## Eine Version anlegen

Knopf **„Neue Version"**. Pflichtangaben:

- **Versionsnummer** — z. B. „1.0", „1.1", „2.0". Wir empfehlen Semantic Versioning: `MAJOR.MINOR`.
- **Stand** — das Datum, das im PDF als „Stand:" erscheint.
- **Erstellt durch** — Mitarbeiter aus der Liste.
- **Änderungs-Grund** — kurzer Text, was sich seit der letzten Version geändert hat. Pflicht.

Speichern → die Version ist angelegt, aber **noch nicht freigegeben**.

## Freigabe (Approval)

Eine angelegte Version ist erst „in Arbeit". Damit sie offiziell zählt, klicken Sie **„Freigeben"**:

- **Freigegeben durch** — wer hat genehmigt? (typischerweise Geschäftsführung oder Notfallbeauftragte).
- **Freigegeben am** — Datum.

Erst nach Freigabe:

- Wird das **PDF erzeugt** (sofern automatisches PDF aktiviert ist).
- Erscheint die Version in der Lesebestätigungs-Liste.
- Wird sie für Lessons-Learned-Verknüpfungen sichtbar.

## PDF-Erzeugung

Sobald die Version freigegeben ist, erzeugt das System automatisch ein **revisionssicheres PDF** mit:

- Wappen / Logo (falls Branding gesetzt).
- Stand-Datum.
- Allen Pflicht-Sektionen (Firma, Standorte, Mitarbeiter, Rollen, Dienstleister, Systeme, Szenarien …).
- **SHA-256-Hash** im Footer als Revisionsanker.

Das PDF ist über den **„PDF herunterladen"**-Knopf in der Versionsliste erreichbar.

Die automatische Erzeugung kann pro Mandant abgeschaltet werden (System-Settings → Auto-PDF), dann muss manuell auf **„PDF jetzt erzeugen"** geklickt werden.

## Lesebestätigungen

Nach Freigabe können Sie Mitarbeiter (insbesondere Schlüsselpersonen) bitten, die neue Version zu lesen und zu bestätigen. Die Bestätigungs-Liste:

- Zeigt pro Mitarbeiter: hat bestätigt / hat nicht bestätigt.
- Erlaubt manuelle Bestätigung durch den Admin (z. B. wenn jemand offline gelesen hat).

Compliance-Score: für regulierte Sektoren ist 100 % Lesebestätigung Pflicht.

## Versionsverlauf

In der Versionshistorie sehen Sie alle Versionen, die freigegebenen mit grünem Badge. Sie können jede frühere Version als PDF herunterladen — wichtig für Audits („Welcher Stand war zum Zeitpunkt X gültig?").

## Wer was sehen darf

Versionsverwaltung ist nur für **Admin und Owner** sichtbar.

> **Praxis-Hinweis**: Geben Sie **mindestens einmal pro Jahr** eine neue Version frei, auch wenn sich wenig geändert hat. Ein altes Handbuch ist im Audit-Kontext fast wertlos.
