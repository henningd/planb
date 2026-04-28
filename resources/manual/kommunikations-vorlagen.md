## Wozu Vorlagen

Im Ernstfall bleibt keine Zeit zum Texten. Die Vorlagen sind **vorgefertigte Anschreiben** für die typischen Situationen — Mitarbeiter informieren, Kunden vorwarnen, Behörden melden, Versicherer alarmieren. Sie können sie kopieren und mit zwei Klicks rausschicken.

Erreichbar über die Sidebar **„Ernstfall → Vorlagen"** (Admin only).

## Eine Vorlage anlegen

Knopf **„Neue Vorlage"**. Pflichtangaben:

- **Name** — z. B. „Mitarbeiter-Information bei Cyberangriff".
- **Zielgruppe** — Mitarbeiter, Kunden, Behörden, Versicherer, Sonstige.
- **Kanal** — E-Mail, SMS, Telefon, Messenger, Aushang, Intranet, Slack, Microsoft Teams.
- **Inhalt** — der Text.

Optional:

- **Betreff** (bei E-Mail / Aushang).
- **Bezug zu einem Szenario** — die Vorlage wird dann beim entsprechenden Szenario-Lauf vorgeschlagen.
- **Notfall-Fallback** — was tun, wenn der Hauptkanal ausgefallen ist? Z. B. „bei E-Mail-Ausfall: SMS-Liste, dann Telefon-Kette".

## Platzhalter

Im Text können Sie Platzhalter verwenden, die beim Versand automatisch ersetzt werden:

- `{{firma}}` — Firmenname.
- `{{ansprechpartner}}` — primärer Ansprechpartner aus dem Mitarbeiter-Bereich.
- `{{datum}}` — heutiges Datum.
- `{{zeitpunkt}}` — aktueller Zeitstempel.

Beispiel: „Sehr geehrte Mandantin, sehr geehrter Mandant, am {{datum}} hat ein IT-Sicherheitsvorfall die Systeme von {{firma}} betroffen …"

## Versand-Wege

Je nach Kanal:

- **E-Mail**: Versand-Modal mit Empfänger-Auswahl (alle Mitarbeiter mit E-Mail). Versand über die hinterlegte Mail-Konfiguration. Audit-Spur pro Empfänger.
- **SMS**: Versand-Modal mit Empfänger-Auswahl (alle Mitarbeiter mit Mobilnummer). Versand über den konfigurierten SMS-Provider. Audit-Spur pro Empfänger.
- **Slack** / **Microsoft Teams**: Versand an den hinterlegten Channel-Webhook. Genau ein „Empfänger" (der Channel), Audit pro Versand.
- **Telefon, Messenger, Aushang, Intranet**: kein direkter Versand aus der App — der Inhalt ist als Vorlage zum Kopieren gedacht.

Slack- und Teams-Webhook-URLs werden in den **System-Settings** pro Mandant gepflegt.

## Versand-Historie

Pro Vorlage gibt es im Kontextmenü **„Versand-Historie"**. Sie sehen alle vergangenen Versendungen mit:

- Zeitstempel.
- Wer hat versendet.
- Wie viele Empfänger.
- Erfolgreich / Fehlgeschlagen.

So ist nachweisbar: „Am 14. Mai um 9:32 Uhr wurden 28 Mitarbeiter per Mail informiert".

## Vorschau

Vor dem Versand können Sie eine **Vorschau** anschauen — alle Platzhalter sind dann schon ersetzt.

## Wer was sehen darf

Vorlagen sind nur für **Admin und Owner** sichtbar.

> **Praxis-Hinweis**: Pflegen Sie mindestens **drei Vorlagen** pro Notlage (Mitarbeiter, Kunden, Behörden) — das gilt als Compliance-Pflicht und ist im Compliance-Score gewichtet.
