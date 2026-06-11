<?php

namespace App\Support\Marketing;

/**
 * Inhalte der SEO-Ratgeberseiten unter /notfallhandbuch und /krisenmanagement.
 * Statisch wie der FeatureCatalog: Marketing-Texte gehören versioniert ins
 * Repository, nicht in die Datenbank.
 *
 * @phpstan-type GuideSection array{heading: string, paragraphs: array<int, string>, list?: array<int, array{title: string, text: string}>}
 * @phpstan-type Guide array{slug: string, title: string, browser_title: string, meta_description: string, tagline: string, lead: string, sections: array<int, GuideSection>, faqs: array<int, array{q: string, a: string}>, related_slug: string, related_label: string}
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
                'related_slug' => 'krisenmanagement',
                'related_label' => 'Ratgeber Krisenmanagement: Phasen, Rollen und Werkzeuge',
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
                'related_slug' => 'notfallhandbuch',
                'related_label' => 'Ratgeber Notfallhandbuch: Inhalte, Aufbau und Pflege',
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
