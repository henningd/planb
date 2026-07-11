## Was eine BIA ist

Die **Business-Impact-Analyse (BIA)** beantwortet die Frage: „Welche Geschäftsprozesse müssen im Ernstfall zuerst wieder laufen — und wie schnell?" Statt einzelne Systeme zu betrachten, blickt die BIA auf die **Prozesse**, mit denen Ihr Unternehmen Geld verdient und Verpflichtungen erfüllt (z. B. Auftragsabwicklung, Lohnbuchhaltung, Warenausgang), und stuft sie nach Kritikalität ein. Das ist die fachliche Grundlage für den [Wiederanlauf](wiederanlauf): Erst wenn Sie wissen, welcher Prozess wie wichtig ist, können Sie sinnvoll priorisieren.

Der BSI-Standard **200-4** macht die BIA zum Kern des Notfallmanagements; **NIS2 (Art. 21)** verlangt Aufrechterhaltung des Betriebs und Wiederherstellung. Dieses Modul liefert genau diesen Nachweis.

Erreichbar über die Sidebar **„BCMS & Governance → Geschäftsprozesse / BIA"**.

## Einen Prozess erfassen

Knopf **„Neuer Prozess"**. Pflichtangaben:

- **Prozessname** — kurz und sprechend, z. B. „Auftragsabwicklung" oder „Lohn- und Gehaltsabrechnung".
- **Kritikalität** — Niedrig, Mittel, Hoch, Existenzkritisch.

Empfohlen:

- **Beschreibung** — worum geht es bei diesem Prozess?
- **Stoßzeiten** — wann läuft der Prozess unter Volllast (z. B. „Mo–Fr 08–18 Uhr", „Monatsende")? Ein Ausfall zur Stoßzeit wiegt schwerer.
- **Benötigte Systeme** — welche [Systeme](systeme) müssen verfügbar sein, damit der Prozess läuft? Diese Verknüpfung ist die Brücke zwischen der Prozess- und der Systemsicht.

## Kritikalität einstufen

Die vier Stufen bilden ab, wie schwer der Ausfall wiegt:

- **Niedrig** — verschmerzbar, kann warten.
- **Mittel** — spürbar, aber überbrückbar.
- **Hoch** — es wird ernst, Kunden/Abläufe leiden schnell.
- **Existenzkritisch** — bedroht das Unternehmen unmittelbar.

Die Liste lässt sich oben nach Kritikalität filtern. Prozesse mit **Hoch** oder **Existenzkritisch** gelten als *kritisch* (`isCritical()`) — nur sie erscheinen im Handbuch-PDF (siehe unten). Übertreiben Sie nicht: Wenn alles „existenzkritisch" ist, ist nichts priorisiert.

## RTO, RPO und MTPD

Drei Wiederanlaufziele, jeweils in **Stunden** erfasst:

- **MTPD** (Maximum Tolerable Period of Disruption) — die absolute Schmerzgrenze: Wie lange darf der Prozess maximal stillstehen, bevor untragbarer Schaden entsteht? Der RTO muss immer *kleiner* als der MTPD sein.
- **RTO** (Recovery Time Objective) — Zielzeit, bis der Prozess wieder läuft.
- **RPO** (Recovery Point Objective) — maximal tolerierbarer Datenverlust (wie alt darf das Backup höchstens sein).

Diese Werte steuern die Reihenfolge im [Wiederanlauf](wiederanlauf) und die Backup-Anforderungen an die verknüpften Systeme.

## Ersatzprozess und Übergabe an den Wiederanlauf

Im Feld **„Ersatzprozess / Notbetrieb"** halten Sie fest, wie der Prozess *ohne* seine Systeme weiterläuft — z. B. Papier-Notbetrieb, Ausweichstandort, manuelle Erfassung mit Nachbuchung. Das ist die prozessübergreifende Ergänzung zum systembezogenen Notfall-Workaround (siehe [Systeme](systeme)). Im Ernstfall greift dieser Ersatzprozess, bis der geplante [Wiederanlauf](wiederanlauf) den Regelbetrieb wiederherstellt.

## Verantwortlich

Pro Prozess ordnen Sie einen Verantwortlichen zu — wahlweise als **Person** (Mitarbeiter) oder als **Rolle**. Personen sind eindeutiger, Rollen bleiben auch bei Personalwechsel gültig. Die Verantwortlichkeit erscheint sowohl im Handbuch-PDF als auch im Audit-Bericht.

## Prüftermine (Review)

Eine BIA ist kein Einmal-Dokument. Über **„Letzte Prüfung"** und **„Nächste Prüfung"** dokumentieren Sie den Review-Zyklus. Ist der Termin der nächsten Prüfung überschritten, markiert die Plattform den Prozess als **überfällig** (rotes Badge, `isReviewOverdue()`) — auf der Kachel und im Audit-Bericht. So sehen Sie auf einen Blick, welche Prozesse neu bewertet werden müssen.

## Verknüpfung mit Risiken, Maßnahmen und Offenen Punkten

Der eigentliche Mehrwert entsteht durch die **Verknüpfung** eines Prozesses mit der Governance:

- **[Risiken](risiken)** — welche Bedrohungen gefährden diesen Prozess? (aus dem Risiko-Register)
- **Präventionsmaßnahmen** — was tun wir vorbeugend, um den Prozess zu schützen?
- **[Offene Punkte / Klärpunkte](offene-punkte)** — welche ungelösten Fragen hängen an diesem Prozess?

Die Zuordnung erfolgt beim jeweiligen Risiko, der Maßnahme bzw. dem Offenen Punkt. Im Audit-Bericht werden diese Einträge dann **prozesszentrisch** zusammengeführt — und Einträge *ohne* Prozessbezug gesondert ausgewiesen, damit die BIA vollständig wird.

## Wo die BIA erscheint

- **Handbuch-PDF, Kapitel 9.1 „Kritische Geschäftsprozesse (BIA)"** — eine kompakte Tabelle *nur* der kritischen Prozesse (Hoch/Existenzkritisch) mit Kritikalität, RTO, abhängigen Systemen, Ersatzprozess und Verantwortlichem. Siehe [PDF-Export](pdf-export).
- **Audit-Bericht (PDF)** — der ausführliche, prozesszentrische Governance-Bericht: pro Prozess alle Kennzahlen (MTPD/RTO/RPO), Prüftermine, Systeme sowie die verknüpften Risiken, Maßnahmen und Offenen Punkte. Erreichbar über den Knopf **„Audit-Bericht (PDF)"** oben auf der Seite. Dieser Bericht ist der NIS2-/BSI-200-4-Nachweis der gelebten BIA.

## Wer was sehen darf

Das Modul ist Teil des Governance-Bereichs und pro Mandant über den Feature-Schalter **BIA** (`features.bia`) zuschaltbar.

> **Praxis-Hinweis**: Beginnen Sie mit den 5–10 wichtigsten Prozessen und stufen Sie sie ehrlich ein. Verknüpfen Sie danach Systeme und Risiken — so wächst die BIA vom Kern nach außen, statt als leere Pflichtübung zu verpuffen.
