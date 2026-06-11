<?php

namespace App\Support\Marketing;

/**
 * Inhalte der SEO-Ratgeberseiten unter /notfallhandbuch und /krisenmanagement.
 * Statisch wie der FeatureCatalog: Marketing-Texte gehören versioniert ins
 * Repository, nicht in die Datenbank.
 *
 * @phpstan-type GuideSection array{heading: string, paragraphs: array<int, string>, list?: array<int, array{title: string, text: string}>}
 * @phpstan-type Guide array{slug: string, title: string, browser_title: string, meta_description: string, tagline: string, lead: string, sections: array<int, GuideSection>, faqs: array<int, array{q: string, a: string}>}
 */
class GuideCatalog
{
    /**
     * @return array<string, Guide>
     */
    public static function all(): array
    {
        return [
            'notfallhandbuch' => [
                'slug' => 'notfallhandbuch',
                'title' => 'Notfallhandbuch für Unternehmen: Inhalte, Aufbau und Pflege',
                'browser_title' => 'Notfallhandbuch erstellen: Inhalte, Aufbau & Vorlage für Unternehmen',
                'meta_description' => 'Was gehört in ein Notfallhandbuch? Definition, Pflichtinhalte, Aufbau in fünf Schritten und Tipps zur Pflege – der Praxis-Ratgeber für kleine und mittelständische Unternehmen inkl. NIS2 und BSI 200-4.',
                'tagline' => 'Der Praxis-Ratgeber: von der leeren Seite zum belastbaren Notfallhandbuch.',
                'lead' => 'Wenn Server stillstehen, ein Verschlüsselungstrojaner zuschlägt oder eine Schlüsselperson ausfällt, entscheidet die Vorbereitung über Stunden oder Wochen Stillstand. Dieser Ratgeber zeigt, was in ein Notfallhandbuch gehört, wie Sie es Schritt für Schritt aufbauen und wie es aktuell bleibt.',
                'sections' => [
                    [
                        'heading' => 'Was ist ein Notfallhandbuch?',
                        'paragraphs' => [
                            'Ein Notfallhandbuch ist die zentrale, jederzeit verfügbare Sammlung aller Informationen, die ein Unternehmen braucht, um auf Notfälle strukturiert zu reagieren: Wer entscheidet? Welche Systeme sind kritisch? Wie laufen Wiederanlauf und Kommunikation? Es übersetzt abstrakte Notfallvorsorge in konkrete, sofort ausführbare Schritte.',
                            'Im Unterschied zum Notfallkonzept, das Strategie und Rahmen beschreibt, ist das Notfallhandbuch das operative Arbeitsdokument für den Ernstfall. Es richtet sich an die Menschen, die in der Nacht des Vorfalls tatsächlich handeln müssen – nicht an Auditoren oder Berater. Gute Notfallhandbücher sind deshalb knapp, eindeutig und aktuell.',
                        ],
                    ],
                    [
                        'heading' => 'Warum jedes Unternehmen eines braucht',
                        'paragraphs' => [
                            'Cyberangriffe, IT-Ausfälle, Stromausfälle oder der plötzliche Ausfall von Schlüsselpersonen treffen längst nicht mehr nur Konzerne. Gerade kleine und mittelständische Unternehmen ohne eigene Stabsstelle für Krisenmanagement verlieren im Ernstfall wertvolle Zeit, weil Zuständigkeiten, Passwörter, Dienstleisterkontakte und Wiederanlaufreihenfolgen verstreut oder nur in Köpfen vorhanden sind.',
                            'Dazu kommen regulatorische Anforderungen: Die NIS2-Richtlinie verpflichtet viele Unternehmen zu Business Continuity und Incident-Management, der BSI-Standard 200-4 beschreibt den Aufbau eines Business-Continuity-Management-Systems, und auch Cyber-Versicherer und Kunden-Audits fragen zunehmend nach dokumentierter Notfallvorsorge. Ein gepflegtes Notfallhandbuch ist der zentrale Nachweis.',
                        ],
                    ],
                    [
                        'heading' => 'Diese Inhalte gehören in ein Notfallhandbuch',
                        'paragraphs' => [
                            'Der Umfang hängt von Größe und Branche ab – die folgenden Bausteine bilden den bewährten Kern:',
                        ],
                        'list' => [
                            ['title' => 'Rollen und Vertretungen', 'text' => 'Wer leitet den Krisenstab, wer entscheidet über Abschaltungen, wer kommuniziert? Jede Rolle braucht eine benannte Person und eine Vertretung.'],
                            ['title' => 'Kritische Systeme und Prozesse', 'text' => 'Eine priorisierte Übersicht: Was muss zuerst wieder laufen, was kann warten? Inklusive Abhängigkeiten zwischen Systemen.'],
                            ['title' => 'Wiederanlaufpläne', 'text' => 'Pro kritischem System: Schritte zum Wiederanlauf, benötigte Zugänge, Zeitbedarf und Ausweichverfahren für die Überbrückung.'],
                            ['title' => 'Notfallkontakte', 'text' => 'Erreichbarkeiten von Mitarbeitenden, IT-Dienstleistern, Versicherung, Behörden und Banken – auch offline verfügbar.'],
                            ['title' => 'Kommunikationsvorlagen', 'text' => 'Vorbereitete Texte für Mitarbeitende, Kunden und Partner, damit unter Stress niemand bei null anfängt.'],
                            ['title' => 'Szenario-Checklisten', 'text' => 'Schritt-für-Schritt-Anleitungen für typische Lagen wie Ransomware, Serverausfall oder Stromausfall.'],
                            ['title' => 'Meldepflichten', 'text' => 'Wer muss wann informiert werden – etwa Datenschutzbehörde innerhalb von 72 Stunden bei Datenpannen oder Meldewege nach NIS2.'],
                        ],
                    ],
                    [
                        'heading' => 'Notfallhandbuch erstellen: in fünf Schritten',
                        'paragraphs' => [
                            'Ein belastbares Notfallhandbuch entsteht nicht an einem Nachmittag, aber auch nicht in einem Mammutprojekt. Bewährt hat sich dieses Vorgehen:',
                        ],
                        'list' => [
                            ['title' => '1. Kritische Prozesse und Systeme erfassen', 'text' => 'Mit den Fachbereichen klären, welche Abläufe das Geschäft tragen und welche IT-Systeme dahinterstehen.'],
                            ['title' => '2. Rollen besetzen', 'text' => 'Krisenstab, IT-Verantwortliche und Kommunikationsrollen benennen – inklusive Vertretungen und Erreichbarkeiten.'],
                            ['title' => '3. Wiederanlauf definieren', 'text' => 'Für jedes kritische System Prioritäten, Wiederanlaufschritte und Ausweichverfahren dokumentieren.'],
                            ['title' => '4. Szenarien und Checklisten ausarbeiten', 'text' => 'Die wahrscheinlichsten Notfälle als konkrete Handlungsabläufe beschreiben, vom ersten Verdacht bis zur Entwarnung.'],
                            ['title' => '5. Testen, üben, verbessern', 'text' => 'Planübungen und Tests decken Lücken auf, bevor es der Ernstfall tut. Erkenntnisse fließen zurück ins Handbuch.'],
                        ],
                    ],
                    [
                        'heading' => 'Word-Dokument oder digitales Notfallhandbuch?',
                        'paragraphs' => [
                            'Viele Unternehmen starten mit einem Word-Dokument oder einer Vorlage. Das Problem zeigt sich später: Statische Dokumente veralten unbemerkt, niemand erinnert an Reviews, Änderungen sind nicht nachvollziehbar – und liegt die Datei auf dem Server, der gerade ausgefallen ist, ist sie im entscheidenden Moment unerreichbar.',
                            'Ein digitales Notfallhandbuch wie PlanB führt strukturiert durch alle Bausteine, erinnert an fällige Überprüfungen, protokolliert jede Änderung revisionssicher im Aktivitätsprotokoll und bleibt als PDF-Export und Notfallkarte auch dann verfügbar, wenn die eigene IT steht. Für Audits liefern Compliance-Dashboard und Audit-Export prüffähige Nachweise auf Knopfdruck.',
                        ],
                    ],
                    [
                        'heading' => 'Pflege: aktuell halten und üben',
                        'paragraphs' => [
                            'Das beste Notfallhandbuch verliert seinen Wert, wenn es veraltet. Etablieren Sie feste Review-Zyklen – je nach Dynamik des Unternehmens alle sechs bis zwölf Monate – und prüfen Sie nach jeder relevanten Änderung an Systemen, Personal oder Dienstleistern, ob das Handbuch noch stimmt.',
                            'Mindestens einmal im Jahr gehört eine Übung dazu: ein Szenario durchspielen, Checklisten am realen Ablauf messen und die Erkenntnisse als Lessons Learned dokumentieren. So wird aus einem Dokument gelebtes Krisenmanagement.',
                        ],
                    ],
                ],
                'faqs' => [
                    ['q' => 'Was ist der Unterschied zwischen Notfallhandbuch und Notfallkonzept?', 'a' => 'Das Notfallkonzept beschreibt Strategie, Geltungsbereich und Verantwortlichkeiten der Notfallvorsorge auf Leitungsebene. Das Notfallhandbuch ist das operative Arbeitsdokument daraus: konkrete Rollen, Pläne, Kontakte und Checklisten für den Ernstfall.'],
                    ['q' => 'Wie oft sollte ein Notfallhandbuch aktualisiert werden?', 'a' => 'Als Faustregel: vollständiger Review alle sechs bis zwölf Monate, zusätzlich anlassbezogen bei jeder relevanten Änderung an Systemen, Personal oder Dienstleistern. Ein digitales Notfallhandbuch erinnert automatisch an fällige Überprüfungen.'],
                    ['q' => 'Wer ist im Unternehmen für das Notfallhandbuch verantwortlich?', 'a' => 'Die Gesamtverantwortung liegt bei der Geschäftsführung. Die operative Pflege übernimmt typischerweise die IT-Leitung oder ein benannter Notfallbeauftragter – wichtig ist eine klar benannte Person mit Vertretung statt geteilter Zuständigkeit.'],
                    ['q' => 'Reicht eine Notfallhandbuch-Vorlage aus dem Internet?', 'a' => 'Eine Vorlage hilft beim Einstieg, bleibt aber generisch: Sie kennt weder Ihre kritischen Systeme noch Ihre Rollen und Dienstleister. Entscheidend ist die strukturierte Erfassung der eigenen Organisation – genau dabei führt ein geführtes Werkzeug Schritt für Schritt.'],
                ],
            ],
            'krisenmanagement' => [
                'slug' => 'krisenmanagement',
                'title' => 'Krisenmanagement im Mittelstand: Phasen, Rollen und Werkzeuge',
                'browser_title' => 'Krisenmanagement für Unternehmen: Phasen, Krisenstab & Praxis-Leitfaden',
                'meta_description' => 'Krisenmanagement praxisnah erklärt: die vier Phasen, Aufbau des Krisenstabs, Krisenkommunikation und typische Szenarien im Mittelstand – inkl. Anforderungen aus NIS2 und BSI 200-4.',
                'tagline' => 'Der Leitfaden für Unternehmen ohne eigene Stabsstelle.',
                'lead' => 'Krisenmanagement ist keine Frage der Unternehmensgröße, sondern der Vorbereitung. Dieser Leitfaden erklärt die vier Phasen des Krisenmanagements, die Rollen im Krisenstab und die Werkzeuge, mit denen auch mittelständische Unternehmen im Ernstfall handlungsfähig bleiben.',
                'sections' => [
                    [
                        'heading' => 'Was ist Krisenmanagement?',
                        'paragraphs' => [
                            'Krisenmanagement umfasst alle Strukturen, Prozesse und Maßnahmen, mit denen ein Unternehmen außergewöhnliche Lagen bewältigt, die den normalen Geschäftsbetrieb bedrohen – vom Cyberangriff über den Standortausfall bis zur Reputationskrise. Es beantwortet im Kern drei Fragen: Wer entscheidet? Was passiert zuerst? Wie wird kommuniziert?',
                            'Krisenmanagement ist eng verzahnt mit dem Business Continuity Management (BCM) nach BSI-Standard 200-4: Während BCM die Fortführung kritischer Geschäftsprozesse sicherstellt, steuert das Krisenmanagement die Lage selbst – mit Krisenstab, Lagebild und Kommunikation. Das Notfallhandbuch ist das verbindende Werkzeug beider Disziplinen.',
                        ],
                    ],
                    [
                        'heading' => 'Die vier Phasen des Krisenmanagements',
                        'paragraphs' => [
                            'Professionelles Krisenmanagement beginnt lange vor der Krise und endet erst nach ihrer Auswertung:',
                        ],
                        'list' => [
                            ['title' => '1. Prävention', 'text' => 'Risiken systematisch erfassen und bewerten: Welche Szenarien sind wahrscheinlich, welche existenzbedrohend? Ein Risiko-Register schafft die Grundlage für gezielte Vorsorge.'],
                            ['title' => '2. Vorbereitung', 'text' => 'Notfallhandbuch aufbauen, Krisenstab benennen, Wiederanlaufpläne und Kommunikationsvorlagen erstellen – und regelmäßig üben.'],
                            ['title' => '3. Bewältigung', 'text' => 'Im Ernstfall: Krisenstab aktivieren, Lage dokumentieren, Entscheidungen nach vorbereiteten Checklisten treffen, Stakeholder informieren.'],
                            ['title' => '4. Nachbereitung', 'text' => 'Den Vorfall strukturiert auswerten: Was hat funktioniert, was nicht? Lessons Learned fließen zurück in Handbuch und Vorsorge.'],
                        ],
                    ],
                    [
                        'heading' => 'Der Krisenstab: Rollen und Verantwortung',
                        'paragraphs' => [
                            'Auch ohne eigene Stabsstelle braucht jedes Unternehmen einen handlungsfähigen Krisenstab. Im Mittelstand sind das oft drei bis sechs Personen, die im Ernstfall klar definierte Rollen übernehmen:',
                        ],
                        'list' => [
                            ['title' => 'Krisenstabsleitung', 'text' => 'Trifft die finalen Entscheidungen und priorisiert – typischerweise Geschäftsführung oder eine von ihr benannte Person.'],
                            ['title' => 'IT-Verantwortung', 'text' => 'Bewertet die technische Lage, steuert Eindämmung und Wiederanlauf, koordiniert externe IT-Dienstleister.'],
                            ['title' => 'Kommunikation', 'text' => 'Informiert Mitarbeitende, Kunden, Partner und gegebenenfalls Presse – nach vorbereiteten Vorlagen und abgestimmter Sprachregelung.'],
                            ['title' => 'Recht und Datenschutz', 'text' => 'Prüft Meldepflichten (etwa DSGVO-Meldung binnen 72 Stunden, NIS2-Meldewege) und rechtliche Folgen von Entscheidungen.'],
                            ['title' => 'Dokumentation', 'text' => 'Führt das Lageprotokoll: Wer hat wann was entschieden? Diese Nachvollziehbarkeit ist für Versicherung, Behörden und die eigene Auswertung entscheidend.'],
                        ],
                    ],
                    [
                        'heading' => 'Krisenkommunikation: intern zuerst',
                        'paragraphs' => [
                            'In der Krise entsteht der größte Schaden oft nicht durch den Vorfall selbst, sondern durch schlechte Kommunikation. Die Reihenfolge ist entscheidend: erst die eigenen Mitarbeitenden, dann Kunden und Partner, dann die Öffentlichkeit. Wer seine Belegschaft aus der Presse informiert werden lässt, verliert Vertrauen und Kontrolle über die Lage.',
                            'Vorbereitete Kommunikationsvorlagen für die wahrscheinlichsten Szenarien sparen im Ernstfall Stunden und verhindern Fehler unter Stress. Dazu gehören auch geklärte Meldewege: Datenschutzbehörde, Cyber-Versicherer, gegebenenfalls BSI beziehungsweise zuständige NIS2-Meldestelle.',
                        ],
                    ],
                    [
                        'heading' => 'Typische Krisenszenarien im Mittelstand',
                        'paragraphs' => [
                            'Die Erfahrung zeigt: Wenige Szenarien decken die meisten Ernstfälle ab. Wer diese vorbereitet, ist für den Großteil aller Lagen gerüstet – Ransomware und Cyberangriffe mit Verschlüsselung oder Datenabfluss, der Ausfall zentraler IT-Systeme oder des Rechenzentrums, Stromausfall und Gebäudeschäden am Standort, der plötzliche Ausfall von Schlüsselpersonen sowie Störungen bei kritischen Dienstleistern und Lieferanten.',
                            'Für jedes dieser Szenarien gehören ins Notfallhandbuch: Auslöser und Eskalationskriterien, eine Schritt-für-Schritt-Checkliste, Verantwortliche und die passenden Kommunikationsvorlagen.',
                        ],
                    ],
                    [
                        'heading' => 'Werkzeuge: vom Plan zur gelebten Praxis',
                        'paragraphs' => [
                            'Papier ist geduldig – Krisen sind es nicht. Software-gestütztes Krisenmanagement macht den Unterschied zwischen einem Ordner im Regal und gelebter Handlungsfähigkeit: Ein Vorfallmodus führt den Krisenstab durch die vorbereiteten Checklisten, der War Room dokumentiert Entscheidungen in Echtzeit, und das Aktivitätsprotokoll macht im Nachgang lückenlos nachvollziehbar, wer wann was getan hat.',
                            'PlanB verbindet beides: das strukturierte Notfallhandbuch für die Vorbereitung und die operativen Werkzeuge für die Bewältigung – inklusive Lessons Learned für die Nachbereitung und Compliance-Nachweisen für NIS2 und BSI 200-4.',
                        ],
                    ],
                ],
                'faqs' => [
                    ['q' => 'Brauchen kleine Unternehmen wirklich einen Krisenstab?', 'a' => 'Ja – nur kleiner. Schon drei klar benannte Rollen (Leitung, IT, Kommunikation) mit Vertretungen machen den Unterschied zwischen koordiniertem Handeln und Chaos. Entscheidend ist nicht die Größe des Stabs, sondern dass Zuständigkeiten vor der Krise geklärt sind.'],
                    ['q' => 'Was ist der Unterschied zwischen Krisenmanagement und Business Continuity Management?', 'a' => 'BCM stellt sicher, dass kritische Geschäftsprozesse weiterlaufen oder schnell wiederanlaufen. Krisenmanagement steuert die außergewöhnliche Lage selbst: Lagebild, Entscheidungen, Kommunikation. In der Praxis greifen beide ineinander – das Notfallhandbuch ist das gemeinsame Fundament.'],
                    ['q' => 'Welche Normen und Gesetze sind für Krisenmanagement relevant?', 'a' => 'Für viele Unternehmen die NIS2-Richtlinie (Risikomanagement, Incident-Meldungen, Business Continuity), der BSI-Standard 200-4 für Business Continuity Management sowie die DSGVO mit der 72-Stunden-Meldepflicht bei Datenpannen. Branchenabhängig kommen weitere Anforderungen hinzu, etwa ISO 22301.'],
                    ['q' => 'Wie oft sollte der Ernstfall geübt werden?', 'a' => 'Mindestens einmal jährlich eine Planübung zu einem realistischen Szenario, ergänzt um kleinere Tests einzelner Bausteine wie Erreichbarkeiten oder Wiederanlaufpläne. Jede Übung liefert Lessons Learned, die das Krisenmanagement messbar verbessern.'],
                ],
            ],
            'it-notfallplan' => [
                'slug' => 'it-notfallplan',
                'title' => 'IT-Notfallplan erstellen: Aufbau, Inhalte und typische Fehler',
                'browser_title' => 'IT-Notfallplan erstellen: Aufbau, Inhalte & Vorlage für den Mittelstand',
                'meta_description' => 'IT-Notfallplan Schritt für Schritt: Welche Systeme, Wiederanlaufpläne und Zugänge hineingehören, wie RTO und RPO helfen und welche Fehler Sie vermeiden sollten – der Praxis-Ratgeber.',
                'tagline' => 'Wenn die IT steht, zählt jede Minute – und jeder dokumentierte Schritt.',
                'lead' => 'Der IT-Notfallplan ist das Herzstück der technischen Notfallvorsorge: Er legt fest, in welcher Reihenfolge Systeme wiederanlaufen, wer welche Zugänge braucht und wie der Betrieb überbrückt wird. Dieser Ratgeber zeigt Aufbau, Pflichtinhalte und die Fehler, die im Ernstfall am teuersten sind.',
                'sections' => [
                    [
                        'heading' => 'Was ist ein IT-Notfallplan?',
                        'paragraphs' => [
                            'Der IT-Notfallplan beschreibt, wie ein Unternehmen auf den Ausfall seiner IT reagiert: vom einzelnen Serverausfall über den Verschlüsselungstrojaner bis zum kompletten Rechenzentrumsausfall. Er ist der technische Kern des Notfallhandbuchs – während das Handbuch die gesamte Organisation abdeckt, fokussiert der IT-Notfallplan auf Systeme, Daten und deren Wiederherstellung.',
                            'Ein guter IT-Notfallplan beantwortet für jedes kritische System drei Fragen: Wie schnell muss es wieder laufen? Wie viel Datenverlust ist verkraftbar? Und wie genau läuft der Wiederanlauf ab – Schritt für Schritt, mit benannten Verantwortlichen und dokumentierten Zugängen.',
                        ],
                    ],
                    [
                        'heading' => 'RTO und RPO: die zwei wichtigsten Kennzahlen',
                        'paragraphs' => [
                            'Zwei Kennzahlen strukturieren jeden IT-Notfallplan: Die Recovery Time Objective (RTO) gibt an, wie lange ein System maximal ausfallen darf, bevor der Schaden untragbar wird. Die Recovery Point Objective (RPO) beschreibt, wie viel Datenverlust akzeptabel ist – sie bestimmt direkt die nötige Backup-Frequenz.',
                            'Beide Werte werden pro System mit den Fachbereichen festgelegt, nicht von der IT allein: Ob die Warenwirtschaft vier Stunden oder zwei Tage stehen darf, ist eine Geschäftsentscheidung. Aus RTO und RPO ergibt sich die Wiederanlauf-Reihenfolge – und damit das Rückgrat des Plans.',
                        ],
                    ],
                    [
                        'heading' => 'Diese Inhalte gehören in den IT-Notfallplan',
                        'paragraphs' => [
                            'Bewährt hat sich pro kritischem System ein eigener Steckbrief mit folgenden Punkten:',
                        ],
                        'list' => [
                            ['title' => 'Systembeschreibung und Priorität', 'text' => 'Was leistet das System, welche Prozesse hängen daran, welche RTO/RPO gelten?'],
                            ['title' => 'Abhängigkeiten', 'text' => 'Welche anderen Systeme, Netzwerkkomponenten oder Dienstleister müssen vorher laufen?'],
                            ['title' => 'Wiederanlaufschritte', 'text' => 'Die konkrete Reihenfolge der Wiederherstellung – so beschrieben, dass auch die Vertretung sie ausführen kann.'],
                            ['title' => 'Zugänge und Lizenzen', 'text' => 'Wo liegen Admin-Zugänge, Wiederherstellungsschlüssel und Lizenznachweise – auch offline verfügbar, falls der Passwortmanager selbst betroffen ist?'],
                            ['title' => 'Ausweichverfahren', 'text' => 'Wie wird der Geschäftsbetrieb überbrückt, solange das System steht – Papierprozess, Ersatzsystem, manueller Workaround?'],
                            ['title' => 'Dienstleister und Eskalation', 'text' => 'Wer unterstützt beim Wiederanlauf, mit welchen Reaktionszeiten laut Vertrag, und wer eskaliert wann?'],
                        ],
                    ],
                    [
                        'heading' => 'In vier Schritten zum IT-Notfallplan',
                        'paragraphs' => [
                            'Der Weg zum belastbaren IT-Notfallplan folgt einer klaren Logik:',
                        ],
                        'list' => [
                            ['title' => '1. Systeme inventarisieren', 'text' => 'Alle Systeme erfassen und mit den Fachbereichen nach Geschäftskritikalität priorisieren.'],
                            ['title' => '2. RTO und RPO festlegen', 'text' => 'Pro System klären, wie lange Ausfall und wie viel Datenverlust tragbar sind – daraus folgt die Wiederanlauf-Reihenfolge.'],
                            ['title' => '3. Wiederanlauf dokumentieren', 'text' => 'Für die kritischen Systeme Schritt-für-Schritt-Pläne mit Zugängen, Abhängigkeiten und Ausweichverfahren schreiben.'],
                            ['title' => '4. Testen und aktualisieren', 'text' => 'Wiederanlaufpläne regelmäßig durchspielen – ein Backup, das nie testweise zurückgespielt wurde, ist nur eine Hoffnung.'],
                        ],
                    ],
                    [
                        'heading' => 'Die teuersten Fehler in der Praxis',
                        'paragraphs' => [
                            'Drei Fehler tauchen in fast jeder Nachbetrachtung auf: Der Plan liegt ausschließlich digital auf Systemen, die im Ernstfall selbst betroffen sind. Die Wiederanlauf-Reihenfolge ignoriert Abhängigkeiten – das ERP startet nicht ohne Datenbank, die Datenbank nicht ohne Storage. Und der Plan kennt nur den Normalfall einer verfügbaren IT-Mannschaft, aber keine Vertretungsregelung für Urlaub und Krankheit.',
                            'Dazu kommt der Klassiker: Der Plan wurde einmal geschrieben und nie aktualisiert. Neue Systeme fehlen, alte Dienstleisterkontakte stimmen nicht mehr. Ein digitales Notfallhandbuch mit Erinnerungen an fällige Reviews und einem Aktivitätsprotokoll über jede Änderung beugt genau dem vor.',
                        ],
                    ],
                    [
                        'heading' => 'Verzahnung mit Notfallhandbuch und BCM',
                        'paragraphs' => [
                            'Der IT-Notfallplan steht nicht allein: Er ist Teil des Notfallhandbuchs, das zusätzlich Rollen, Krisenkommunikation und organisatorische Szenarien regelt, und er liefert die technische Grundlage für das Business Continuity Management nach BSI-Standard 200-4. Wer den IT-Notfallplan in dieser Struktur pflegt, erfüllt zugleich zentrale Anforderungen aus NIS2 an Backup-Management, Wiederherstellung und Krisenbewältigung.',
                        ],
                    ],
                ],
                'faqs' => [
                    ['q' => 'Was ist der Unterschied zwischen IT-Notfallplan und Notfallhandbuch?', 'a' => 'Der IT-Notfallplan ist der technische Teil: Systeme, Wiederanlauf, Zugänge, Backups. Das Notfallhandbuch umfasst zusätzlich die organisatorische Seite – Krisenstab, Kommunikation, Meldepflichten und Szenarien jenseits der IT. Der IT-Notfallplan ist also ein Kapitel des Notfallhandbuchs.'],
                    ['q' => 'Was bedeuten RTO und RPO?', 'a' => 'RTO (Recovery Time Objective) ist die maximal tolerierbare Ausfallzeit eines Systems. RPO (Recovery Point Objective) ist der maximal tolerierbare Datenverlust, gemessen als Zeitspanne seit der letzten Sicherung. Beide Werte bestimmen Wiederanlauf-Reihenfolge und Backup-Strategie.'],
                    ['q' => 'Wo sollte der IT-Notfallplan aufbewahrt werden?', 'a' => 'Immer mehrfach: im digitalen Notfallhandbuch für die tägliche Pflege, als aktueller PDF-Export an einem von der eigenen IT unabhängigen Ort und in Kurzform als Notfallkarte für die wichtigsten Erreichbarkeiten und ersten Schritte.'],
                    ['q' => 'Wie oft muss der Wiederanlauf getestet werden?', 'a' => 'Kritische Systeme mindestens einmal jährlich, idealerweise als realistische Wiederherstellungsübung inklusive Backup-Rückspielung. Jeder Test gehört dokumentiert – auch als Nachweis für Versicherer und Audits.'],
                ],
            ],
            'bsi-200-4' => [
                'slug' => 'bsi-200-4',
                'title' => 'BSI-Standard 200-4 umsetzen: Business Continuity Schritt für Schritt',
                'browser_title' => 'BSI 200-4 umsetzen: BCM-Anforderungen & Praxis-Leitfaden für KMU',
                'meta_description' => 'BSI-Standard 200-4 verständlich erklärt: das Stufenmodell, die Kernelemente vom BCM-Aufbau über die Business-Impact-Analyse bis zu Übungen – und wie der Mittelstand pragmatisch startet.',
                'tagline' => 'Der BCM-Standard des BSI – pragmatisch übersetzt für Unternehmen ohne Stabsstelle.',
                'lead' => 'Der BSI-Standard 200-4 beschreibt, wie Organisationen ein Business Continuity Management System (BCMS) aufbauen. Was nach Konzernwerk klingt, ist bewusst gestuft angelegt – auch kleine und mittlere Unternehmen können konform starten. Dieser Leitfaden erklärt das Stufenmodell und den pragmatischen Einstieg.',
                'sections' => [
                    [
                        'heading' => 'Was ist der BSI-Standard 200-4?',
                        'paragraphs' => [
                            'Der BSI-Standard 200-4 ist der aktuelle Standard des Bundesamts für Sicherheit in der Informationstechnik für Business Continuity Management. Er löst den älteren Standard 100-4 (Notfallmanagement) ab und beschreibt, wie Organisationen die Fortführung ihrer kritischen Geschäftsprozesse bei Störungen und Krisen sicherstellen – von der Vorsorge über die Bewältigung bis zur kontinuierlichen Verbesserung.',
                            'Anders als sein Vorgänger denkt 200-4 vom Geschäftsprozess her, nicht von der IT: Im Zentrum steht die Frage, welche Abläufe das Unternehmen tragen und wie schnell sie nach einer Störung wieder verfügbar sein müssen.',
                        ],
                    ],
                    [
                        'heading' => 'Das Stufenmodell: drei Wege zum BCMS',
                        'paragraphs' => [
                            'Die wichtigste Neuerung des 200-4 ist sein Stufenmodell – es erlaubt einen Einstieg passend zur Reife der Organisation:',
                        ],
                        'list' => [
                            ['title' => 'Reaktiv-BCMS', 'text' => 'Der Einstieg: grundlegende Strukturen für die Bewältigung – Krisenstab, Notfallhandbuch, Sofortmaßnahmen. Geeignet für Organisationen, die schnell handlungsfähig werden wollen.'],
                            ['title' => 'Aufbau-BCMS', 'text' => 'Die Zwischenstufe: ergänzt systematische Vorsorge mit Business-Impact-Analyse, Risikobetrachtung und Notfallplänen für die wichtigsten Prozesse.'],
                            ['title' => 'Standard-BCMS', 'text' => 'Der Vollausbau: ein vollständiges Managementsystem mit Leitlinie, Kennzahlen, Übungsprogramm und kontinuierlicher Verbesserung – anschlussfähig an ISO 22301.'],
                        ],
                    ],
                    [
                        'heading' => 'Die Kernelemente in der Praxis',
                        'paragraphs' => [
                            'Unabhängig von der Stufe stehen vier Bausteine im Zentrum jeder 200-4-Umsetzung:',
                        ],
                        'list' => [
                            ['title' => 'Business-Impact-Analyse (BIA)', 'text' => 'Welche Geschäftsprozesse sind kritisch, welche Ressourcen brauchen sie, wie schnell müssen sie wieder laufen? Die BIA liefert Prioritäten und Wiederanlaufzeiten.'],
                            ['title' => 'Risikoanalyse', 'text' => 'Welche Szenarien bedrohen die kritischen Prozesse – und wo lohnt Vorsorge mehr als Reaktion? Ein gepflegtes Risiko-Register hält das Ergebnis aktuell.'],
                            ['title' => 'Notfallvorsorge und -handbuch', 'text' => 'Rollen, Wiederanlaufpläne, Ausweichverfahren und Kommunikation – dokumentiert so, dass sie im Ernstfall tatsächlich nutzbar sind.'],
                            ['title' => 'Üben und Verbessern', 'text' => 'Planübungen und Tests prüfen die Vorsorge; Lessons Learned fließen zurück. Ohne Übungsnachweis bleibt jedes BCMS Theorie.'],
                        ],
                    ],
                    [
                        'heading' => 'Pragmatisch starten im Mittelstand',
                        'paragraphs' => [
                            'Der häufigste Fehler bei 200-4 ist der Versuch, sofort das Standard-BCMS zu bauen – mit monatelanger Dokumentationsarbeit, die nie in den Ernstfall-Modus kommt. Der Standard selbst empfiehlt das Gegenteil: mit dem Reaktiv-BCMS beginnen, also zuerst Krisenstab benennen, Notfallhandbuch aufbauen und die wichtigsten Szenarien vorbereiten.',
                            'Von dort wächst das BCMS iterativ: Die Business-Impact-Analyse schärft die Prioritäten, das Risiko-Register systematisiert die Vorsorge, regelmäßige Übungen liefern den Reifegrad. Ein digitales Werkzeug, das diese Bausteine verbindet und jede Änderung nachvollziehbar dokumentiert, nimmt dabei den größten Teil der Verwaltungsarbeit ab.',
                        ],
                    ],
                    [
                        'heading' => 'Verhältnis zu ISO 22301 und NIS2',
                        'paragraphs' => [
                            'Der BSI-Standard 200-4 ist methodisch eng an der internationalen Norm ISO 22301 ausgerichtet – wer 200-4 auf Standard-Stufe umsetzt, hat den Großteil des Wegs zu einer ISO-Zertifizierung zurückgelegt. Für die NIS2-Richtlinie ist 200-4 der naheliegende Umsetzungsrahmen: NIS2 fordert ausdrücklich Business Continuity, Backup-Management und Krisenbewältigung – genau die Disziplinen, die der Standard strukturiert.',
                        ],
                    ],
                ],
                'faqs' => [
                    ['q' => 'Ist der BSI-Standard 200-4 verpflichtend?', 'a' => 'Für die meisten Unternehmen nicht direkt – er ist ein Standard, kein Gesetz. Verbindlich wird er über Umwege: NIS2 und Branchenregulierung fordern Business Continuity, Behörden und KRITIS-Betreiber orientieren sich am BSI, und Kunden-Audits fragen zunehmend nach BCM-Strukturen nach anerkanntem Standard.'],
                    ['q' => 'Was ist der Unterschied zwischen BSI 100-4 und 200-4?', 'a' => 'Der 200-4 ersetzt den 100-4 und bringt zwei wesentliche Neuerungen: das Stufenmodell (Reaktiv-, Aufbau-, Standard-BCMS) für einen skalierbaren Einstieg und die konsequente Ausrichtung an Geschäftsprozessen statt primär an IT-Systemen.'],
                    ['q' => 'Wie groß ist der Aufwand für ein Reaktiv-BCMS?', 'a' => 'Überschaubar: Krisenstab und Rollen benennen, die wichtigsten Szenarien mit Checklisten vorbereiten und ein Notfallhandbuch mit Kontakten und Wiederanlaufplänen aufbauen. Mit geführter Struktur entsteht eine erste belastbare Version in wenigen Arbeitssitzungen.'],
                    ['q' => 'Kann man sich nach BSI 200-4 zertifizieren lassen?', 'a' => 'Eine eigenständige 200-4-Zertifizierung gibt es nicht; der Standard fließt in IT-Grundschutz-Zertifizierungen ein. Wer ein extern zertifizierbares BCMS anstrebt, wählt ISO 22301 – die 200-4-Umsetzung ist dafür die beste Vorarbeit.'],
                ],
            ],
            'nis2-checkliste' => [
                'slug' => 'nis2-checkliste',
                'title' => 'NIS2-Checkliste: Anforderungen und Umsetzung für Unternehmen',
                'browser_title' => 'NIS2-Checkliste: Wer betroffen ist & welche Maßnahmen Pflicht sind',
                'meta_description' => 'NIS2 kompakt: Wer betroffen ist, welche zehn Mindestmaßnahmen die Richtlinie fordert, wie die Meldepflichten funktionieren und mit welcher Checkliste Unternehmen die Umsetzung strukturieren.',
                'tagline' => 'Von der Betroffenheitsprüfung bis zur Meldekette – NIS2 strukturiert umsetzen.',
                'lead' => 'Die NIS2-Richtlinie weitet die Cybersicherheitspflichten in der EU massiv aus: Statt weniger hundert KRITIS-Betreiber sind nun zehntausende Unternehmen erfasst – inklusive persönlicher Verantwortung der Geschäftsleitung. Diese Checkliste zeigt, wer betroffen ist, was gefordert wird und womit Sie anfangen sollten.',
                'sections' => [
                    [
                        'heading' => 'Was ist NIS2?',
                        'paragraphs' => [
                            'NIS2 ist die EU-Richtlinie über Maßnahmen für ein hohes gemeinsames Cybersicherheitsniveau (Network and Information Security). Sie ersetzt die erste NIS-Richtlinie und verschärft die Anforderungen deutlich: mehr betroffene Sektoren, konkrete Mindestmaßnahmen, strenge Meldepflichten und empfindliche Sanktionen. In Deutschland erfolgt die Umsetzung über das NIS2-Umsetzungsgesetz; zuständige Aufsicht ist das BSI.',
                        ],
                    ],
                    [
                        'heading' => 'Wer ist betroffen?',
                        'paragraphs' => [
                            'NIS2 unterscheidet wesentliche und wichtige Einrichtungen in 18 Sektoren – von Energie, Transport und Gesundheit über digitale Infrastruktur bis zu verarbeitendem Gewerbe, Lebensmitteln und Chemie. Als Faustregel gilt: Unternehmen ab 50 Beschäftigten oder 10 Millionen Euro Jahresumsatz in einem der Sektoren fallen in den Anwendungsbereich; für besonders kritische Bereiche gelten keine Größenschwellen.',
                            'Wichtig: Auch wer selbst nicht direkt erfasst ist, bekommt NIS2 über die Lieferkette zu spüren – betroffene Kunden müssen die Sicherheit ihrer Lieferanten bewerten und reichen Anforderungen vertraglich weiter. Eine dokumentierte Notfallvorsorge wird damit faktisch zur Geschäftsvoraussetzung.',
                        ],
                    ],
                    [
                        'heading' => 'Die zehn Mindestmaßnahmen nach Artikel 21',
                        'paragraphs' => [
                            'Die Richtlinie schreibt einen Katalog von Risikomanagement-Maßnahmen vor, die jede betroffene Einrichtung umsetzen muss:',
                        ],
                        'list' => [
                            ['title' => 'Risikoanalyse und Sicherheitskonzepte', 'text' => 'Systematische Bewertung der Risiken für Netz- und Informationssysteme.'],
                            ['title' => 'Bewältigung von Sicherheitsvorfällen', 'text' => 'Prozesse für Erkennung, Reaktion und Wiederherstellung – inklusive Vorfallmodus und Dokumentation.'],
                            ['title' => 'Business Continuity', 'text' => 'Backup-Management, Wiederherstellung nach Notfällen und Krisenmanagement – das Kerngebiet des Notfallhandbuchs.'],
                            ['title' => 'Sicherheit der Lieferkette', 'text' => 'Bewertung und vertragliche Absicherung der Sicherheit von Dienstleistern und Lieferanten.'],
                            ['title' => 'Sicherheit bei Erwerb und Entwicklung', 'text' => 'Sicherheitsanforderungen über den gesamten Lebenszyklus von Systemen.'],
                            ['title' => 'Wirksamkeitsbewertung', 'text' => 'Verfahren, um die eigenen Maßnahmen regelmäßig zu überprüfen.'],
                            ['title' => 'Cyberhygiene und Schulungen', 'text' => 'Grundlegende Sicherheitspraktiken und regelmäßige Sensibilisierung der Beschäftigten.'],
                            ['title' => 'Kryptografie', 'text' => 'Konzepte für Verschlüsselung, wo angemessen.'],
                            ['title' => 'Personal-, Zugriffs- und Anlagensicherheit', 'text' => 'Zugriffskontrolle und Management der eigenen Assets.'],
                            ['title' => 'Multi-Faktor-Authentifizierung und sichere Kommunikation', 'text' => 'MFA sowie gesicherte Sprach-, Video- und Textkommunikation – auch für den Notfall.'],
                        ],
                    ],
                    [
                        'heading' => 'Meldepflichten: 24 Stunden, 72 Stunden, ein Monat',
                        'paragraphs' => [
                            'Erhebliche Sicherheitsvorfälle müssen gestaffelt gemeldet werden: eine Frühwarnung binnen 24 Stunden nach Kenntnis, eine ausführlichere Meldung binnen 72 Stunden und ein Abschlussbericht spätestens nach einem Monat. Parallel kann bei Datenpannen die 72-Stunden-Meldung nach DSGVO an die Datenschutzbehörde fällig sein.',
                            'Diese Fristen sind unter Stress nur zu halten, wenn die Meldekette vorbereitet ist: Wer erkennt und bewertet den Vorfall, wer meldet an welche Stelle, welche Informationen müssen rein? Genau dafür gehören Meldewege und Kommunikationsvorlagen ins Notfallhandbuch.',
                        ],
                    ],
                    [
                        'heading' => 'Geschäftsführung in der Pflicht',
                        'paragraphs' => [
                            'NIS2 nimmt die Leitungsebene ausdrücklich in die Verantwortung: Die Geschäftsleitung muss die Risikomanagement-Maßnahmen billigen, ihre Umsetzung überwachen und an Schulungen teilnehmen – und haftet bei Verstößen persönlich. Delegieren lässt sich die Arbeit, nicht die Verantwortung. Ein nachvollziehbar gepflegtes Notfall- und Krisenmanagement mit Aktivitätsprotokoll ist hier der wichtigste Entlastungsnachweis.',
                        ],
                    ],
                    [
                        'heading' => 'Checkliste: so strukturieren Sie die Umsetzung',
                        'paragraphs' => [
                            'Für den Einstieg hat sich diese Reihenfolge bewährt:',
                        ],
                        'list' => [
                            ['title' => '1. Betroffenheit prüfen', 'text' => 'Sektor, Unternehmensgröße und Rolle in der Lieferkette bewerten – im Zweifel rechtlich absichern.'],
                            ['title' => '2. Verantwortung verankern', 'text' => 'Zuständigkeiten auf Leitungsebene festlegen, Budget und Berichtsweg klären.'],
                            ['title' => '3. Bestandsaufnahme', 'text' => 'Kritische Prozesse, Systeme und Dienstleister erfassen – die Grundlage für Risikoanalyse und Prioritäten.'],
                            ['title' => '4. Lücken zu den zehn Maßnahmen schließen', 'text' => 'Gap-Analyse gegen den Artikel-21-Katalog; zuerst Incident-Response, Business Continuity und Meldewege.'],
                            ['title' => '5. Notfallhandbuch und Krisenorganisation aufbauen', 'text' => 'Rollen, Wiederanlaufpläne, Szenario-Checklisten und Kommunikationsvorlagen – getestet, nicht nur dokumentiert.'],
                            ['title' => '6. Nachweise organisieren', 'text' => 'Umsetzung, Übungen und Änderungen revisionssicher dokumentieren – ein Compliance-Dashboard zeigt den Stand auf einen Blick.'],
                        ],
                    ],
                ],
                'faqs' => [
                    ['q' => 'Betrifft NIS2 auch kleine Unternehmen?', 'a' => 'Direkt meist erst ab 50 Beschäftigten oder 10 Millionen Euro Umsatz in einem der 18 Sektoren – mit Ausnahmen für besonders kritische Bereiche. Indirekt aber häufig ja: Betroffene Kunden müssen ihre Lieferkette absichern und geben Anforderungen an kleinere Zulieferer weiter.'],
                    ['q' => 'Welche Strafen drohen bei Verstößen?', 'a' => 'Die Richtlinie sieht Bußgelder von bis zu 10 Millionen Euro oder 2 Prozent des weltweiten Jahresumsatzes für wesentliche Einrichtungen vor (7 Millionen bzw. 1,4 Prozent für wichtige). Hinzu kommt die persönliche Verantwortung der Geschäftsleitung.'],
                    ['q' => 'Womit sollte man bei der NIS2-Umsetzung anfangen?', 'a' => 'Mit dem, was im Ernstfall sofort trägt und zugleich Pflicht ist: Vorfallbewältigung, Business Continuity und Meldewege – also Krisenorganisation und Notfallhandbuch. Diese Bausteine liefern den schnellsten Risiko- und Compliance-Gewinn; die übrigen Maßnahmen folgen aus der Gap-Analyse.'],
                    ['q' => 'Reicht eine ISO-27001-Zertifizierung für NIS2?', 'a' => 'Sie deckt viele Anforderungen ab, ersetzt die NIS2-Pflichten aber nicht vollständig – insbesondere Registrierung, Meldepflichten und die spezifischen Business-Continuity-Anforderungen bleiben eigenständig nachzuweisen. ISO 27001 ist eine sehr gute Basis, kein Freifahrtschein.'],
                ],
            ],
        ];
    }

    /**
     * @return Guide|null
     */
    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }
}
