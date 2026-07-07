## Wozu Vorlagen

Im Ernstfall bleibt keine Zeit zum Texten. Die Vorlagen sind **vorgefertigte Anschreiben** für die typischen Situationen — Mitarbeiter informieren, Kunden vorwarnen, Behörden melden, Dienstleister alarmieren. Sie können sie ansehen, kopieren und – bei E-Mail und SMS – mit wenigen Klicks direkt aus dem System verschicken.

Erreichbar über die Sidebar im Ernstfall-Block unter **„Vorlagen"** (zwischen „Meldepflichten" und „Testplan"). Sichtbar ist der Punkt für die Rollen **Berater, Administrator und Inhaber** — ein einfaches Mitglied sieht ihn nicht.

## Eine Vorlage anlegen

Oben rechts auf **„Neue Vorlage"** (oder das kleine **Plus** neben einer Zielgruppen-Überschrift — dann ist die Zielgruppe schon vorausgewählt). Pflichtangaben:

- **Name** — z. B. „Mitarbeiter-Information bei Cyberangriff".
- **Zielgruppe** — Mitarbeiter, Kunden, Presse, Behörden, Dienstleister, Social Media, Sonstige. Die Zielgruppe ist ein Ordnungs-Label; sie steuert **nicht**, wer beim Versand tatsächlich erreicht wird (siehe „Wer wird erreicht?").
- **Kanal** — E-Mail, SMS, Telefon, Messenger, Aushang, Intranet, Slack, Microsoft Teams.
- **Inhalt** — der Text.

Optional: **Betreff** (z. B. bei E-Mail), **Bezug zu einem Szenario** (dann wird die Vorlage beim passenden Szenario-Lauf vorgeschlagen) und ein **Fallback** („bei E-Mail-Ausfall: SMS-Liste, dann Telefon-Kette").

Über das **„⋮"-Menü** an einer Vorlage lässt sie sich später **bearbeiten** oder löschen. Mit **„Vorschau"** (Augen-Symbol) sehen Sie die fertige Nachricht mit eingesetzten Platzhaltern, ohne etwas zu verschicken.

## Platzhalter

Im Text können Sie Platzhalter verwenden, die beim Versand automatisch ersetzt werden:

- `{{firma}}` — Firmenname.
- `{{ansprechpartner}}` — primärer Ansprechpartner aus dem Mitarbeiter-Bereich.
- `{{datum}}` — heutiges Datum.
- `{{zeitpunkt}}` — aktueller Zeitstempel.

Beispiel: „Sehr geehrte Mandantin, sehr geehrter Mandant, am {{datum}} hat ein IT-Sicherheitsvorfall die Systeme von {{firma}} betroffen …"

## Eine Vorlage anwenden — Schritt für Schritt

Welche Versand-Schaltfläche an einer Vorlage erscheint, hängt vom Kanal ab.

**SMS:** Klick auf **„SMS senden"** (Papierflieger-Symbol) öffnet den Dialog **„SMS senden"**. Oben steht die fertige Nachricht, darunter eine **Empfängerliste** mit allen Mitarbeitern, die eine Mobilnummer hinterlegt haben — alle mit Häkchen vorausgewählt (Schlüsselpersonen mit gelbem Badge). Wen Sie nicht erreichen wollen, haken Sie ab; bei großen Teams helfen die Knöpfe **„Alle auswählen"** und **„Alle abwählen"** über der Liste. Dann unten **„Senden vorbereiten (N)"**. Achtung: Damit ist noch nichts verschickt — es erscheint ein **rotes Warnfeld** („Wirklich senden? … kostet pro Empfänger"), und erst der zweite, rote Klick **„N SMS jetzt verschicken"** löst den Versand über den SMS-Dienst (seven.io) aus. Danach sehen Sie pro Empfänger ein grünes „OK" oder einen roten Fehler.

Ist auf dem Server **kein SMS-Gateway konfiguriert** (Server-Einstellung `SEVENIO_API_KEY`), werden **keine echten SMS verschickt** — der Versand wird nur **simuliert**. Der Dialog warnt dann deutlich („SMS-Gateway nicht konfiguriert"), und die Ergebnisse tragen das Badge **„Simuliert — kein Gateway"**. So können Sie den Ablauf gefahrlos durchspielen, wissen aber sicher, dass niemand eine Nachricht erhalten hat.

**E-Mail:** Klick auf **„E-Mail senden"** — gleicher Ablauf, nur dass die Empfängerliste die Mitarbeiter mit hinterlegter **E-Mail-Adresse** enthält. Nach **„Senden vorbereiten"** und **„Jetzt verschicken"** geht die E-Mail raus; der Versand wird pro Empfänger protokolliert.

**Slack / Microsoft Teams:** Ein einzelner Senden-Button postet die Nachricht nach einer Rückfrage direkt in den **hinterlegten Channel** (die Webhook-URL wird einmalig in den System-Einstellungen pro Mandant gepflegt). Keine Empfängerauswahl.

**Telefon, Messenger, Aushang, Intranet, Social Media:** Kein automatischer Versand — diese Vorlagen sind Textbausteine zum **Vorlesen, Kopieren oder Aushängen**.

## Direkt aus dem Krisen-Cockpit senden

Im Ernstfall müssen Sie nicht erst diese Seite suchen. Das **[Krisen-Cockpit](/handbuch/krisen-cockpit)** hat einen eigenen Abschnitt **„Kommunikation"**, der die relevanten Vorlagen als Karten anzeigt. An jeder Karte gibt es **„Vorlage öffnen"** (Text lesen/kopieren) und – je nach Kanal – **„Per SMS senden"** bzw. **„Per E-Mail senden"**. Diese öffnen denselben Versand-Dialog mit der gleichen zweistufigen Bestätigung **direkt im Cockpit** — ohne Umweg über diese Seite.

## Wer wird erreicht?

Wichtig zu verstehen: Die Empfänger von **E-Mail und SMS** kommen ausschließlich aus den **Mitarbeiter-Stammdaten** (Felder „E-Mail" und „Mobil" unter [Mitarbeiter](/handbuch/mitarbeiter)). Es gibt aktuell **keine** eigenen Verteiler für Kunden, Presse oder Behörden — eine als „Kunden" oder „Behörden" gekennzeichnete Vorlage geht beim E-Mail-/SMS-Versand trotzdem an die Mitarbeiter mit Kontaktdaten und dient sonst als Text zum Kopieren. Pflegen Sie deshalb bei den wichtigen Personen unbedingt E-Mail-Adresse und Mobilnummer.

## Versand-Historie und Vorschau

Pro Vorlage gibt es im „⋮"-Menü die **„Versand-Historie"**: Zeitpunkt, wer versendet hat, Anzahl Empfänger, erfolgreich/fehlgeschlagen. So ist nachweisbar: „Am 14. Mai um 9:32 Uhr wurden 28 Mitarbeiter per Mail informiert." Jeder Versand landet zusätzlich im [Audit-Log](/handbuch/audit-log).

> **Praxis-Hinweis**: Pflegen Sie mindestens **drei Vorlagen** pro Notlage (Mitarbeiter, Kunden, Behörden) — das gilt als Compliance-Pflicht und ist im Compliance-Score gewichtet.
