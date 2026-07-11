## Wozu KI-Governance

Die **EU-KI-Verordnung** (Verordnung (EU) 2024/1689, „AI Act") verlangt von Unternehmen, die KI einsetzen, dass sie ihre KI-Systeme **kennen, einstufen, überwachen und dokumentieren**. PlanB ist selbst kein KI-System — es ist das Werkzeug, mit dem Sie diese Pflichten erfüllen und nachweisen.

Erreichbar über die Sidebar **„BCMS & Governance → KI-Governance"** (Admin only).

> Hinweis: Dies ersetzt keine Rechtsberatung, ist aber ein belastbarer Startpunkt.

## KI-System-Register

Erfassen Sie jedes eingesetzte KI-System mit:

- **Rolle** (Anbieter, Betreiber, Importeur, Händler) — sie bestimmt Ihre Pflichten.
- **Risikoklasse** (verboten, hoch, begrenzt, minimal) — mit farbigem Hinweis auf die Pflichtenlage.
- Zweck/Einsatzkontext, Anbieter, **menschliche Aufsicht**, Datenquellen, Transparenzmaßnahmen, Konformitätsstatus, EU-Datenbank-Registrierung, zuständige interne Rolle und **Prüftermine**.

Über den Filter sehen Sie z. B. auf einen Blick alle Hochrisiko-Systeme.

**Verknüpfungen:** Im Formular lässt sich jedes KI-System mit den betroffenen [Risiken](risiken), [Geschäftsprozessen](geschaeftsprozesse) und [Notfall-Szenarien](szenarien) verbinden. So ist auf der Detailseite und im Audit-Bericht sofort ersichtlich, wo ein KI-System im BCMS wirkt — z. B. welches Risiko es verstärkt oder welcher Prozess bei seinem Ausfall betroffen ist.

## Klassifizierungs-Assistent

Der Knopf **„Klassifizierung"** führt durch den Entscheidungsbaum der Verordnung — von oben nach unten, die erste zutreffende Stufe zählt:

1. **Verbotene Praktik** (Art. 5) → Einsatz unzulässig.
2. **Hochrisiko** (Annex III oder Sicherheitsbauteil Annex I) → volle Pflichten.
3. **Transparenz** (Interaktion mit Menschen, synthetische Inhalte) → Art. 50.
4. Sonst **minimal**.

Das Ergebnis lässt sich direkt als KI-System ins Register übernehmen.

## Protokoll & Nachweise

Öffnen Sie ein System (Klick auf den Namen), führen Sie ein **revisionssicheres Protokoll**: Prüfungen, Aufsichts-Eingriffe, Tests, Vorfälle, Änderungen, Schulungen — je Eintrag mit Datum und Verfasser. Zusätzlich werden **alle Änderungen an den Stammdaten automatisch im Audit-Log** erfasst — doppelter Nachweis.

**Meldepflicht bei schwerwiegenden Vorfällen (Art. 73):** Erfassen Sie einen Protokoll-Eintrag vom Typ **Vorfall**, können Sie ihn als **meldepflichtigen schwerwiegenden Vorfall (Art. 73 EU-KI-VO)** markieren und das Datum der Meldung an die Marktüberwachungsbehörde festhalten. Anbieter und Betreiber von Hochrisiko-KI müssen solche Vorfälle unverzüglich melden. Noch nicht gemeldete Art.-73-Vorfälle werden im Audit-Bericht gesondert als Warnung ausgewiesen.

## Auswertung

- Im **[Compliance-Dashboard](compliance-dashboard)** fließt ein Prüfpunkt „KI-Systeme erfasst & eingestuft" in den Reifegrad ein (schlägt bei verbotenen Praktiken, fehlender Einstufung oder überfälligen Prüfungen aus).
- Im **Audit-Bericht** (Knopf auf der Geschäftsprozesse-Seite) erscheint eine Sektion „KI-Systeme (EU-KI-Verordnung)" mit Rolle, Risikoklasse, Aufsicht/Konformität und Prüfstatus.
