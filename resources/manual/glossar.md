## Begriffe von A bis Z

### Audit-Log

Zeitlich sortierte Liste aller Änderungen an audit-relevanten Daten. Wer hat wann was geändert, mit Vorher-/Nachher-Werten. Manipulationsgeschützt, exportierbar als CSV oder PDF.

### BIA — Business Impact Analysis

Bewertung, wie stark ein Ausfall verschiedener Prozesse das Geschäft trifft. In dieser Plattform implizit über Notfall-Level + Ausfallkosten.

### BSI 200-4

Standard des Bundesamts für Sicherheit in der Informationstechnik für **Business Continuity Management** (Notfallmanagement). Eine der zwei Hauptreferenzen für den Compliance-Score.

### Compliance-Score

Wert zwischen 0 und 100, der den Reifegrad des Notfallmanagements abbildet. Berechnet aus elf gewichteten Pflicht-Checks. Wird täglich automatisch ge-snapshot.

### DSGVO

Datenschutz-Grundverordnung der EU. Relevant: Art. 33 (Meldefrist 72h für Datenpannen), Art. 34 (Information Betroffener), Art. 30 (Verarbeitungsverzeichnis).

### Eskalations-Kette

Reihenfolge der zu informierenden Personen bei einem Vorfall: erst Notfallbeauftragte, dann Geschäftsführung, dann Datenschutz, dann Behörde.

### Idempotency-Key

Eindeutige Kennung eines Monitoring-Alarms (Zabbix `event_id`, Prometheus `fingerprint`). Verhindert, dass derselbe Alarm zweimal verarbeitet wird.

### KRITIS

Kritische Infrastruktur. Strenge Pflichten, betrifft Energie, Wasser, Gesundheit, Verkehr, IT/TK, Finanz/Versicherung. Im Firmenprofil markierbar.

### Krisenrolle (Crisis Role)

Eine der fünf Pflichtrollen: Geschäftsführung, Notfallbeauftragte, IT-Leitung, Datenschutzbeauftragte, Kommunikation.

### Lessons Learned

Strukturierte Auswertung nach einer Übung oder einem Vorfall. Drei Felder: Ursache, was lief gut, was lief nicht gut. Plus konkrete Action-Items.

### Live-Inzident-Modus / Krisen-Cockpit

Reduziertes Live-Dashboard, das aktiv wird, wenn ein Szenario-Lauf als „echte Lage" markiert ist. Zeigt Krisenstab, Wiederanlauf-Reihenfolge, offene Schritte, Meldepflichten.

### NIS2

EU-Richtlinie für Network and Information Security, Version 2. Trifft mittelständische Unternehmen ab ~50 MA in regulierten Sektoren. Pflicht: Krisenmanagement, Vorfall-Meldung, Asset-Inventar.

### Notfall-Level

Klassifizierungs-Stufe für Systeme: kritisch, hoch, mittel, niedrig. Bestimmt Wiederanlauf-Reihenfolge.

### Onboarding-Wizard

Geführte Erst-Einrichtung in 9 Schritten: Firmenprofil, Branchen-Template, Standorte, Mitarbeiter, Rollen, Dienstleister, Systeme, Sofortmittel, erste Handbuch-Version.

### RACI

Verantwortungs-Modell mit vier Rollen pro Aufgabe: Responsible (Ausführer), Accountable (Verantwortlicher fürs Ergebnis), Consulted (Konsultation), Informed (Information).

### Recovery-Gantt

Graphische Wiederanlauf-Zeitplanung. Zeigt pro System einen Balken in Wiederanlauf-Reihenfolge, abhängig von RTO und Abhängigkeiten.

### Restrisiko

Risiko nach Anwendung der Maßnahmen. Als zweiter Score (Restwahrscheinlichkeit × Restschaden) gepflegt.

### Risiko-Register

Liste aller Risiken mit Bewertung, Maßnahmen, Eigentümer, Review-Termin. Anforderung aus ISO 27001 und NIS2.

### RPO — Recovery Point Objective

Wie alt darf das Backup höchstens sein, das im Wiederanlauf benutzt wird? Beispiel: RPO = 4h heißt, im schlimmsten Fall verlieren wir 4 Stunden Daten.

### RTO — Recovery Time Objective

Wie lange darf der Ausfall maximal dauern? Beispiel: RTO = 4h heißt, nach 4 Stunden müssen wir wieder produktiv sein.

### Schlüsselperson

Mitarbeiter, deren Lesebestätigung pro Handbuch-Version Pflicht ist. Markierung im Mitarbeiter-Datensatz.

### Stellvertretung

Pro Krisenrolle eine Hauptperson plus beliebig viele Stellvertretungen. Im Ernstfall wird die Liste von oben nach unten abgearbeitet.

### Szenario

Vorgefertigtes Playbook für eine typische Notlage. Schritt-Liste mit Verantwortlichen und Dauer.

### Szenario-Lauf

Konkrete Durchführung eines Szenarios — als Tabletop-Übung oder echte Lage.

### Tabletop-Übung

Schreibtisch-Übung: der Krisenstab geht ein Szenario durch, ohne dass tatsächlich ein Notfall vorliegt. Wichtigste Vorbereitungs-Form.

### TMG

Telemediengesetz. Relevant für Impressums-Pflichtangaben (§ 5 TMG).

### USV

Unterbrechungsfreie Stromversorgung. Akku-Puffer, der bei Stromausfall genug Zeit gibt, um Server geordnet runterzufahren.

### Vorfall (Incident)

Konkretes Ereignis (Cyberangriff, Datenpanne, Systemausfall) mit Meldepflichten und Countdown.

### War-Room

Echtzeit-Multi-User-Sicht auf einen aktiven Szenario-Lauf. Mehrere Personen sehen sich gegenseitig live arbeiten.

### Wiederanlauf

Geordnete Wiederherstellung der Systeme nach einem Total-Ausfall. Reihenfolge ergibt sich aus Notfall-Level und Abhängigkeiten.
