## Wozu Versicherungen pflegen

Im Ernstfall ist die Frage „Welche Versicherung deckt das?" oft die entscheidende. Sie pflegen hier Ihre relevanten Policen — Cyber, Betriebshaftpflicht, Vertrauensschaden, eventuell Sach.

Erreichbar über die Sidebar **„Notfallhandbuch → Versicherungen"** (Admin only). Der Bereich ist das zentrale Register für **Policen, Nachweise und Schadenabsicherung** — nicht nur Stammdaten, sondern alles, was im Schadenfall gebraucht wird.

Pro Versicherung erfassbar: **Art** (Cyber, Betriebsunterbrechung, Elektronik, Gebäude, Betriebshaftpflicht, Inhalt, Maschinen, Transport, Sach, branchenspezifisch), Versicherer, Policennummer, **Laufzeit**, **Deckungssumme**, Selbstbehalt, **Schadenhotline** und **E-Mail Schadenmeldung**, Ansprechpartner, **Meldefristen**, **benötigte Unterlagen im Schadenfall**, **zuständige interne Rolle**, **Bezug zu Szenarien** (z. B. Cyberangriff, Brand, Wasserschaden, Betriebsunterbrechung) sowie der **Hinweis, ob vor Beauftragung von Forensik / Sanierung / Ersatzbeschaffung eine Freigabe des Versicherers nötig ist**. Zusätzlich Prüf-/Testtermine: „Schadenmeldeweg getestet am", „Letzte/Nächste Prüfung".

Das erscheint im Handbuch-PDF unter **„8.2 Versicherungen und Schadenmeldung"** und wird im **Audit-Bericht** geprüft (Police aktuell? Meldeweg getestet? Deckung zu den Top-Risiken? nächste Prüfung terminiert?). Versicherungen gehören damit in diesen Bereich — nicht zu den Notfallressourcen.

## Eine Police anlegen

Knopf **„Neue Versicherung"**. Pflichtangaben:

- **Versicherer** — Firmenname, z. B. „Allianz Versicherungs-AG".
- **Versicherungstyp** — Cyber, Betriebshaftpflicht, Vertrauensschaden, sonstige.
- **Police-Nummer**.
- **Vertragsbeginn**.

Empfohlen:

- **Vertragsende** — Erinnerung kurz davor sinnvoll.
- **Hotline** — die 24/7-Schadensnummer (bei Cyber-Versicherungen meist eigene IR-Hotline).
- **Selbstbehalt** — wichtig für Eskalations-Entscheidungen.
- **Deckungssumme** — Grenzwert, bis zu dem die Versicherung zahlt.
- **Ansprechpartner** — Maklername oder Schaden-Sachbearbeiter.
- **E-Mail Schadensanzeige**.
- **Notiz** — z. B. „Cyber-Police umfasst auch IT-Forensik".

## Bezug zu Notfall-Vorlagen

In Kommunikations-Vorlagen (z. B. „Schadensmeldung an Cyberversicherung") können Sie auf den Versicherer per Platzhalter referenzieren — die richtige Police wird automatisch eingefüllt.

## Erinnerungen

Wenn das Vertragsende näher als drei Monate kommt, taucht die Police auf dem Dashboard in der „Heute"-Liste auf — Sie haben Zeit zu verlängern oder neu zu vergleichen.

## Wer was sehen darf

Versicherungen sind nur für **Admin und Owner** sichtbar — sie enthalten oft kommerziell sensible Daten (Selbstbehalt, Deckungssumme).

## DSGVO-Hinweis

Versicherungs-Daten sind keine personenbezogenen Daten der Mitarbeiter, sondern Vertragsdaten Ihres Unternehmens. Trotzdem im Audit-Log sichtbar.
