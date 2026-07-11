## Worum es geht

Ein Ausfall trifft Sie nicht nur im eigenen Haus. Wenn Ihr IT-Systemhaus verschlüsselt wird, Ihr Cloud-Anbieter tagelang offline ist oder ein kritischer Zulieferer plötzlich nicht mehr liefert, steht Ihr Betrieb genauso still — nur haben Sie das Problem dann nicht selbst in der Hand. Genau diese **Abhängigkeit von Dritten** bewerten Sie hier.

NIS2 verlangt das ausdrücklich: Artikel 21 nennt die Sicherheit der **Lieferkette** als eigenständige Risikomaßnahme. Sie müssen nicht nur wissen, *wer* Ihre Dienstleister sind, sondern *wie kritisch* jeder einzelne ist und *wie es um dessen Sicherheit bestellt* ist.

Erreichbar über die Sidebar **„BCMS & Governance → Lieferketten-Risiko"**.

## Abgrenzung zu den Dienstleister-Stammdaten

Wichtig, damit nichts doppelt gepflegt wird:

- Die **Stammdaten** eines Partners — Name, Hotline, Vertragsnummer, SLA, Ansprechpartner — leben unter [Dienstleister](dienstleister). Das ist das Telefonbuch für den Ernstfall.
- Die **Risikobewertung** hier setzt darauf auf. Sie bewertet je Dienstleister, wie sehr Sie von ihm abhängen und ob er sicherheitstechnisch geprüft wurde.

Ein Dienstleister muss also zuerst unter [Dienstleister](dienstleister) angelegt sein — im Bewertungsformular wählen Sie ihn nur noch aus einer Liste aus. Pro Dienstleister gibt es genau eine Risikobewertung.

## Eine Bewertung anlegen

Knopf **„Neue Bewertung"**. Das Formular bietet:

- **Dienstleister** — Auswahl aus Ihren bestehenden [Dienstleistern](dienstleister). Pflichtfeld.
- **Kritikalität** — wie hart trifft Sie sein Ausfall? Vier Stufen: *Niedrig, Mittel, Hoch, Kritisch*. „Kritisch" heißt: ohne diesen Partner steht ein Kernprozess still.
- **Sicherheitsbewertung** — Stand der Prüfung: *Nicht bewertet, In Prüfung, Bestanden, Nicht bestanden*. Hier dokumentieren Sie z. B. das Ergebnis eines Sicherheitsfragebogens, eines Zertifikatsnachweises (ISO 27001) oder einer Auditierung.
- **Wiederbewertung** — Intervall für die turnusmäßige Neubewertung: *Monatlich, Quartalsweise, Halbjährlich, Jährlich, Alle 2 Jahre*. Leer lassen für eine einmalige Bewertung.
- **Zuletzt bewertet** und **Nächste Bewertung** — Datumsfelder. Setzen Sie ein Intervall, aber kein Fälligkeitsdatum, rechnet die App die nächste Bewertung automatisch aus (ab letzter Bewertung, sonst ab heute).
- **Ausweich-Dienstleister** — wer springt ein, wenn dieser Partner ausfällt? Der wichtigste Satz gegen Klumpenrisiko: „Fällt A aus, rufen wir B an."
- **Notizen** — Freitext für Kontext, offene Punkte, Prüfergebnisse.

## Kritische Lieferanten erkennen und pflegen

Die Kachelübersicht zeigt jede Bewertung mit farbigen Badges für Kritikalität und Sicherheitsstatus. Sortiert wird nach der nächsten fälligen Wiederbewertung — was als Nächstes ansteht, steht oben. Über den Filter **Kritikalität** blenden Sie gezielt nur die kritischen Partner ein.

Ist eine Wiederbewertung überfällig, erscheint ein rotes **„Überfällig"**-Badge. Nach einer durchgeführten Prüfung genügt ein Klick auf **„Als bewertet markieren"**: das setzt das Datum auf heute und schiebt — bei gesetztem Intervall — die nächste Fälligkeit automatisch weiter.

## Faustregeln

- Fangen Sie bei den **kritischen** Partnern an: IT-Dienstleister, Fachsoftware-Hersteller, Cloud/SaaS, kritische Zulieferer. Nicht jeder Reinigungsvertrag braucht eine Sicherheitsbewertung.
- Für jeden als **kritisch** eingestuften Partner sollte ein **Ausweich-Dienstleister** eingetragen sein — sonst ist die Abhängigkeit ungemildert.
- Ein Lieferanten-Ausfall gehört zusätzlich als Bedrohung ins [Risiko-Register](risiken) (Kategorie „Dritte/Lieferkette"), wenn Sie ihn mit Wahrscheinlichkeit und Schadenshöhe durchrechnen wollen. Welche Prozesse an einem Partner hängen, halten Sie in den [Geschäftsprozessen / BIA](geschaeftsprozesse) fest.

## Wer was sehen darf

Die Seite ist nur verfügbar, wenn das Feature `supply_chain_risk` für Ihren Mandanten aktiv ist. Ist es abgeschaltet, erscheint der Menüpunkt nicht.

> **Praxis-Hinweis**: Verwechseln Sie „bewertet" nicht mit „sicher". Der Status *Bestanden* heißt nur, dass Sie hingeschaut haben. Der eigentliche Wert entsteht durch die regelmäßige Wiederbewertung — ein Zertifikat von vorletztem Jahr sagt wenig über die Lage heute.
