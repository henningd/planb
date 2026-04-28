<?php

namespace App\Support\Manual;

/**
 * Inhaltsverzeichnis des Online-Benutzerhandbuchs.
 *
 * Reihenfolge entspricht der Sidebar-Navigation auf /handbuch.
 * Jedes Kapitel ist eine Markdown-Datei in resources/manual/.
 */
class ManualCatalog
{
    /**
     * @return list<array{category: string, slug: string, title: string, summary: string}>
     */
    public static function all(): array
    {
        return [
            // Erste Schritte
            ['category' => 'Erste Schritte', 'slug' => 'einleitung', 'title' => 'Was ist ein Notfallhandbuch?', 'summary' => 'Worum es bei diesem System geht und warum jeder Betrieb ab fünf Mitarbeitern eines braucht.'],
            ['category' => 'Erste Schritte', 'slug' => 'konto', 'title' => 'Konto anlegen und anmelden', 'summary' => 'Registrierung, Anmeldung, Passwort vergessen, Zwei-Faktor-Authentifizierung.'],
            ['category' => 'Erste Schritte', 'slug' => 'einrichtung', 'title' => 'Geführte Einrichtung', 'summary' => 'Der Wizard mit den neun Pflicht-Schritten — pausierbar, fortsetzbar, nachvollziehbar.'],
            ['category' => 'Erste Schritte', 'slug' => 'branchen-template', 'title' => 'Branchen-Template anwenden', 'summary' => 'Vorlagen für typische Geschäftsmodelle, die viele Stammdaten in einem Schritt anlegen.'],
            ['category' => 'Erste Schritte', 'slug' => 'dashboard', 'title' => 'Das Dashboard verstehen', 'summary' => 'Was bedeuten die Kacheln, was sind „Heute zu erledigen"-Punkte, wo geht es weiter?'],

            // Stammdaten
            ['category' => 'Stammdaten', 'slug' => 'firma', 'title' => 'Firma', 'summary' => 'Firmenprofil, Branche, Rechtsform, Mitarbeiterzahl, Datenschutzbehörde.'],
            ['category' => 'Stammdaten', 'slug' => 'standorte', 'title' => 'Standorte', 'summary' => 'Hauptsitz, Filialen, Werkstätten, Lager — die physischen Orte des Unternehmens.'],
            ['category' => 'Stammdaten', 'slug' => 'mitarbeiter', 'title' => 'Mitarbeiter', 'summary' => 'Personen mit Kontaktdaten, Funktion und Krisenrolle.'],
            ['category' => 'Stammdaten', 'slug' => 'rollen', 'title' => 'Rollen und Pflichtrollen', 'summary' => 'Die fünf Pflichtrollen, eigene Rollen, Hauptpersonen und Stellvertretungen.'],
            ['category' => 'Stammdaten', 'slug' => 'dienstleister', 'title' => 'Dienstleister', 'summary' => 'Externe IT-Dienstleister, Hotlines, SLA, Vertragsdaten.'],
            ['category' => 'Stammdaten', 'slug' => 'systeme', 'title' => 'Systeme', 'summary' => 'IT- und Geschäfts-Systeme: erfassen, klassifizieren, RTO/RPO, Eigentümer.'],
            ['category' => 'Stammdaten', 'slug' => 'abhaengigkeiten', 'title' => 'Abhängigkeiten', 'summary' => 'Wer hängt von wem ab? Visualisierung als Netzwerk-Graph.'],
            ['category' => 'Stammdaten', 'slug' => 'aufgaben-inbox', 'title' => 'Aufgaben-Inbox', 'summary' => 'Zentrale Sicht auf alle System-Aufgaben mit Fälligkeit und RACI.'],
            ['category' => 'Stammdaten', 'slug' => 'recovery-zeitplan', 'title' => 'Recovery-Zeitplan', 'summary' => 'Wiederanlauf als Gantt-Diagramm — wer macht wann was.'],
            ['category' => 'Stammdaten', 'slug' => 'versicherungen', 'title' => 'Versicherungen', 'summary' => 'Cyberversicherung, Hotline, Police, Selbstbehalt, Vertragsende.'],
            ['category' => 'Stammdaten', 'slug' => 'sofortmittel', 'title' => 'Sofortmittel', 'summary' => 'Notfall-Ressourcen wie USV, Notebooks, Bargeld, Schlüssel.'],
            ['category' => 'Stammdaten', 'slug' => 'notfall-level', 'title' => 'Notfall-Level', 'summary' => 'Klassifizierung der Systeme nach Kritikalität und maximaler Ausfallzeit.'],

            // Ernstfall
            ['category' => 'Ernstfall', 'slug' => 'krisen-cockpit', 'title' => 'Krisen-Cockpit', 'summary' => 'Das reduzierte Live-Cockpit für den Ernstfall.'],
            ['category' => 'Ernstfall', 'slug' => 'szenarien', 'title' => 'Szenarien', 'summary' => 'Vorgefertigte Playbooks für typische Notlagen.'],
            ['category' => 'Ernstfall', 'slug' => 'wiederanlauf', 'title' => 'Wiederanlauf', 'summary' => 'Reihenfolge der System-Wiederherstellung nach einem Ausfall.'],
            ['category' => 'Ernstfall', 'slug' => 'meldepflichten', 'title' => 'Vorfälle und Meldepflichten', 'summary' => 'Vorfall melden, Fristen einhalten (DSGVO 72h, NIS2, Versicherung).'],
            ['category' => 'Ernstfall', 'slug' => 'protokolle-uebungen', 'title' => 'Protokolle und Übungen', 'summary' => 'Szenario-Läufe als Tabletop-Übung oder echte Lage starten — der War-Room.'],
            ['category' => 'Ernstfall', 'slug' => 'lessons-learned', 'title' => 'Lessons Learned', 'summary' => 'Strukturierte After-Action-Auswertung mit Maßnahmen.'],
            ['category' => 'Ernstfall', 'slug' => 'risiken', 'title' => 'Risiko-Register', 'summary' => 'Risiken bewerten, behandeln, Restrisiko nachweisen.'],
            ['category' => 'Ernstfall', 'slug' => 'kommunikations-vorlagen', 'title' => 'Kommunikations-Vorlagen', 'summary' => 'Vorbereitete Texte für Mitarbeiter, Kunden, Behörden — versendbar via E-Mail, SMS, Slack, Teams.'],
            ['category' => 'Ernstfall', 'slug' => 'testplan', 'title' => 'Testplan', 'summary' => 'Geplante Notfall-Tests mit Verantwortlichen und Fälligkeiten.'],

            // Notfallhandbuch & Versionen
            ['category' => 'Notfallhandbuch', 'slug' => 'handbuch-erstellen', 'title' => 'Handbuch-Versionen', 'summary' => 'Versionen erstellen, freigeben, PDF erzeugen, Lesebestätigungen einholen.'],
            ['category' => 'Notfallhandbuch', 'slug' => 'pdf-export', 'title' => 'PDF-Export', 'summary' => 'Revisionssichere PDFs mit SHA-256-Hash, Wappen und Footer.'],
            ['category' => 'Notfallhandbuch', 'slug' => 'qr-aushang', 'title' => 'QR-Aushang am Server', 'summary' => 'Druckbarer Sticker mit QR-Code, der zur System-Detailseite führt.'],

            // Compliance & Audit
            ['category' => 'Compliance', 'slug' => 'compliance-dashboard', 'title' => 'Compliance-Dashboard', 'summary' => 'Reifegrad-Score nach BSI 200-4 / NIS2 mit 30-Tage-Trend.'],
            ['category' => 'Compliance', 'slug' => 'audit-log', 'title' => 'Audit-Log', 'summary' => 'Lückenlose Änderungshistorie, Filter, CSV/PDF-Export.'],
            ['category' => 'Compliance', 'slug' => 'mandanten-export', 'title' => 'Mandanten-Archiv', 'summary' => 'Vollständiger ZIP-Export für DSGVO-Auskunft und Datenrückgabe.'],

            // Team & Freigaben
            ['category' => 'Team & Freigaben', 'slug' => 'benutzer', 'title' => 'Benutzer einladen', 'summary' => 'App-Benutzer und Rollen (Owner, Admin, Member).'],
            ['category' => 'Team & Freigaben', 'slug' => 'freigabelinks', 'title' => 'Freigabelinks', 'summary' => 'Read-only-Links mit Ablauf, z. B. für Auditor oder Versicherung.'],
            ['category' => 'Team & Freigaben', 'slug' => 'zwei-faktor', 'title' => 'Zwei-Faktor-Authentifizierung', 'summary' => 'TOTP-App, Recovery-Codes, 2FA-Pflicht für Admins.'],

            // Einstellungen
            ['category' => 'Einstellungen', 'slug' => 'system-settings', 'title' => 'System-Einstellungen', 'summary' => 'Mandanten-Defaults, Backup, Auto-PDF, Aufbewahrung.'],
            ['category' => 'Einstellungen', 'slug' => 'branding', 'title' => 'Branding', 'summary' => 'Eigenes Logo, Anzeigename, Primärfarbe pro Mandant.'],
            ['category' => 'Einstellungen', 'slug' => 'api-webhooks', 'title' => 'API & Webhooks', 'summary' => 'Tokens, Zabbix, Prometheus-Anbindung, automatische Incidents.'],

            // Anhang
            ['category' => 'Anhang', 'slug' => 'compliance-dokumente', 'title' => 'Compliance-Dokumente', 'summary' => 'Impressum, Datenschutz, AGB, AVV, TOM, Subprocessors, security.txt — die öffentlichen Pflicht-Seiten der Plattform.'],
            ['category' => 'Anhang', 'slug' => 'glossar', 'title' => 'Glossar', 'summary' => 'Begriffe von BIA bis WAR-Room verständlich erklärt.'],
            ['category' => 'Anhang', 'slug' => 'faq', 'title' => 'FAQ', 'summary' => 'Häufige Fragen und Antworten.'],
        ];
    }

    /**
     * @return array{category: string, slug: string, title: string, summary: string}|null
     */
    public static function find(string $slug): ?array
    {
        foreach (self::all() as $entry) {
            if ($entry['slug'] === $slug) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return array<string, list<array{category: string, slug: string, title: string, summary: string}>>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $entry) {
            $grouped[$entry['category']][] = $entry;
        }

        return $grouped;
    }

    public static function content(string $slug): ?string
    {
        $path = resource_path('manual/'.$slug.'.md');
        if (! is_file($path)) {
            return null;
        }

        return file_get_contents($path) ?: null;
    }

    /**
     * @return list<array{level: int, slug: string, title: string}>
     */
    public static function tableOfContents(string $markdown): array
    {
        $toc = [];
        foreach (preg_split('/\R/', $markdown) ?: [] as $line) {
            if (preg_match('/^(#{2,3})\s+(.+?)\s*$/u', $line, $match) === 1) {
                $level = strlen($match[1]);
                $title = trim($match[2]);
                $slug = self::slugify($title);
                $toc[] = ['level' => $level, 'slug' => $slug, 'title' => $title];
            }
        }

        return $toc;
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';

        return trim($text, '-');
    }
}
