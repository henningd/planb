<?php

namespace App\Support\Settings;

/**
 * Single source of truth for every system/company setting:
 * key, scope, type, hardcoded fallback default, UI label.
 *
 * Adding a new setting = add one entry here, then wire its effect.
 */
class SettingsCatalog
{
    public const SYSTEM = 'system';

    public const COMPANY = 'company';

    private const DEFAULT_IMPRINT = <<<'TEXT'
        IMPRESSUM

        Angaben gemäß § 5 Telemediengesetz (TMG)

        Anbieter
        Arento AI GmbH i. G.
        Wiesenstr. 28
        53773 Hennef
        Deutschland

        Kontakt
        E-Mail: info@arento.ai

        Vertretungsberechtigter Geschäftsführer
        Daniel Henninger

        Hinweis zur Vorgesellschaft
        Die Gesellschaft befindet sich in Gründung (i. G.). Bis zur Eintragung in
        das Handelsregister bestehen die Vorschriften der Vor-GmbH. Registereintrag,
        Registergericht, Handelsregisternummer und ggf. Umsatzsteuer-Identifikations-
        nummer (§ 27 a UStG) werden nach erfolgter Eintragung an dieser Stelle
        ergänzt.

        Inhaltlich verantwortlich gemäß § 18 Abs. 2 MStV
        Daniel Henninger, Anschrift wie oben

        Online-Streitbeilegung
        Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung
        (OS) bereit, erreichbar unter https://ec.europa.eu/consumers/odr/.
        Wir sind nicht verpflichtet und nicht bereit, an einem Streit-
        beilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.

        Haftung für Inhalte
        Die Inhalte unserer Seiten wurden mit größter Sorgfalt erstellt. Für die
        Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch
        keine Gewähr übernehmen. Als Diensteanbieter sind wir gemäß § 7 Abs. 1 TMG
        für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen
        verantwortlich. Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch
        nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen
        zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige
        Tätigkeit hinweisen.

        Haftung für Links
        Unser Angebot kann Links zu externen Webseiten Dritter enthalten, auf
        deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese
        fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der
        verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der
        Seiten verantwortlich.

        Urheberrecht
        Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen
        Seiten unterliegen dem deutschen Urheberrecht. Vervielfältigung,
        Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der
        Grenzen des Urheberrechts bedürfen der schriftlichen Zustimmung des
        jeweiligen Autors bzw. Erstellers.

        TEXT;

    private const DEFAULT_PRIVACY = <<<'TEXT'
        DATENSCHUTZERKLÄRUNG

        Stand: April 2026

        1. VERANTWORTLICHER

        Verantwortlich für die Datenverarbeitung im Sinne der DSGVO ist:

        Arento AI GmbH i. G.
        Wiesenstr. 28
        53773 Hennef
        Deutschland
        E-Mail: info@arento.ai
        Vertretungsberechtigter Geschäftsführer: Daniel Henninger

        Einen Datenschutzbeauftragten haben wir derzeit nicht bestellt, da die
        gesetzlichen Voraussetzungen (Art. 37 DSGVO, § 38 BDSG) nicht erfüllt
        sind. Bei Fragen zum Datenschutz wenden Sie sich bitte direkt an die
        oben genannte E-Mail-Adresse.

        2. ALLGEMEINES ZUR DATENVERARBEITUNG

        Wir verarbeiten personenbezogene Daten unserer Nutzerinnen und Nutzer
        grundsätzlich nur, soweit dies zur Bereitstellung einer funktionsfähigen
        Plattform sowie unserer Inhalte und Leistungen erforderlich ist. Die
        Verarbeitung personenbezogener Daten erfolgt regelmäßig nur nach
        Einwilligung der betroffenen Person oder in den Fällen, in denen eine
        vorherige Einholung einer Einwilligung aus tatsächlichen Gründen nicht
        möglich ist und die Verarbeitung der Daten durch gesetzliche
        Vorschriften gestattet ist.

        Rechtsgrundlagen sind insbesondere:
        — Art. 6 Abs. 1 lit. a DSGVO (Einwilligung)
        — Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung / vorvertragliche Maßnahmen)
        — Art. 6 Abs. 1 lit. c DSGVO (rechtliche Verpflichtung)
        — Art. 6 Abs. 1 lit. f DSGVO (berechtigte Interessen)

        3. BEREITSTELLUNG DER WEBSITE UND DER PLATTFORM (LOGFILES)

        Bei jedem Aufruf unserer Website und der Plattform erfasst unser
        Hosting-Anbieter automatisch Informationen, die Ihr Browser an unseren
        Server übermittelt. Diese sogenannten Server-Logfiles enthalten:

        — IP-Adresse des anfragenden Geräts (gekürzt, soweit technisch möglich)
        — Datum und Uhrzeit der Anfrage
        — Aufgerufene URL und HTTP-Methode
        — Übertragene Datenmenge und Statuscode
        — Referrer-URL
        — Browser-Typ und -Version, Betriebssystem

        Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO. Berechtigtes Interesse ist
        der sichere und stabile Betrieb der Plattform sowie die Erkennung und
        Abwehr von Angriffen.

        Speicherdauer: in der Regel 14 Tage, danach automatische Löschung. Bei
        sicherheitsrelevanten Vorfällen werden Logs länger aufbewahrt, bis der
        Vorfall vollständig aufgeklärt ist.

        4. NUTZUNG DER PLATTFORM (BENUTZERKONTO)

        Für die Nutzung der Plattform legen Sie ein Benutzerkonto an. Dabei
        verarbeiten wir:

        — Name, E-Mail-Adresse, Passwort (gespeichert als bcrypt-Hash, Klartext
          wird nicht abgelegt)
        — Zwei-Faktor-Authentifizierungs-Geheimnisse (verschlüsselt)
        — Zugehörigkeit zu Teams/Mandanten und Berechtigungen
        — Zeitstempel von Anmeldungen und Sitzungen

        Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung).

        Speicherdauer: für die Dauer der Vertragsbeziehung. Nach Vertragsende
        werden Konto-Daten nach einer angemessenen Karenzzeit gelöscht oder
        anonymisiert, soweit keine gesetzlichen Aufbewahrungspflichten
        entgegenstehen.

        5. INHALTLICHE NUTZUNG (NOTFALLHANDBUCH)

        Im Rahmen der Plattform-Nutzung erfassen Sie als Mandant Daten zu
        Mitarbeitenden, IT-Systemen, Dienstleistern, Kontakten,
        Versicherungen, Notfallszenarien und ähnlichen Inhalten Ihres
        Notfallhandbuchs. Diese Daten verarbeiten wir ausschließlich in
        Ihrem Auftrag (Art. 28 DSGVO).

        Bei Verarbeitung personenbezogener Daten Dritter (z. B.
        Mitarbeiter-Telefonnummern, Notfall-Kontakte) ist der Mandant für
        eine wirksame Rechtsgrundlage und für die Information der Betroffenen
        nach Art. 13/14 DSGVO selbst verantwortlich.

        Rechtsgrundlage gegenüber dem Mandanten: Art. 6 Abs. 1 lit. b DSGVO.
        Rechtsgrundlage für betroffene Mitarbeiter/Dritte: typischerweise
        § 26 BDSG bzw. Art. 6 Abs. 1 lit. b/f DSGVO — die konkrete Bewertung
        obliegt dem Mandanten.

        6. AUFTRAGSVERARBEITER UND DIENSTLEISTER

        Wir setzen folgende Auftragsverarbeiter ein, mit denen jeweils ein
        Vertrag nach Art. 28 DSGVO besteht:

        Hosting und Datenhaltung
        DigitalOcean, LLC, 101 Avenue of the Americas, 10th Floor, New York,
        NY 10013, USA — wir nutzen ausschließlich die EU-Region Frankfurt
        (FRA1). Anwendungsserver, Datenbank und Datei-Speicher liegen in
        Deutschland. Standardvertragsklauseln (Art. 46 DSGVO) sind als
        zusätzliche Garantie geschlossen.

        E-Mail-Versand und -Empfang
        Strato AG, Otto-Ostrowski-Str. 7, 10249 Berlin — verarbeitet
        transaktionale und kommunikative E-Mails (Anmeldebestätigungen,
        Passwort-Reset, Krisen-Mails). Datenverarbeitung in Deutschland.

        SMS-Versand für Krisen-Kommunikation
        avento.ai (Anbieter, Anschrift werden auf Anfrage mitgeteilt) —
        verarbeitet Empfänger-Mobilnummern und Nachrichteninhalte zur
        Zustellung. Wird nur eingesetzt, wenn der Mandant SMS-Vorlagen
        aktiv versendet.

        Optionale Krisen-Kommunikationskanäle (nur bei aktiver Nutzung
        durch den Mandanten)
        — Slack: Slack Technologies, LLC, USA — Versand in mandanteneigene
          Slack-Channels über Incoming-Webhook. Drittlandübermittlung in die
          USA auf Grundlage der EU-Standardvertragsklauseln (Art. 46 DSGVO).
        — Microsoft Teams: Microsoft Corporation / Microsoft Ireland
          Operations Ltd. — Versand in mandanteneigene Teams-Channels über
          Incoming-Webhook. Datenhaltung kann je nach Tenant-Konfiguration
          des Mandanten in der EU oder in den USA stattfinden.
        — Telegram: Telegram FZ-LLC, VAE — Versand in mandanteneigene
          Telegram-Kanäle. Drittlandübermittlung auf Grundlage der EU-
          Standardvertragsklauseln (Art. 46 DSGVO).

        Optionales Monitoring
        Wenn der Mandant in der Plattform Webhook-Endpunkte für Zabbix oder
        Prometheus Alertmanager freischaltet, werden eingehende Alarm-Daten
        (Hostname, Severity, Subject, Zeitstempel) zur automatischen Incident-
        Erstellung verarbeitet. Die Quelle der Alarme liegt in der Sphäre
        des Mandanten.

        7. DRITTLANDÜBERMITTLUNG

        Die Anwendungsplattform selbst (Stammdaten, Notfallhandbuch, Audit-
        Log) wird ausschließlich in der EU verarbeitet. Eine Drittland-
        übermittlung findet ausschließlich dann statt, wenn der Mandant
        Slack-, Teams- oder Telegram-Kanäle für Krisen-Kommunikation aktiv
        nutzt. Rechtsgrundlage ist in diesen Fällen Art. 46 Abs. 2 lit. c
        DSGVO (EU-Standardvertragsklauseln); ergänzend setzen wir
        technisch-organisatorische Maßnahmen wie Transportverschlüsselung
        ein.

        8. SPEICHERDAUER

        Wir speichern personenbezogene Daten nur so lange, wie es für die
        jeweiligen Zwecke erforderlich ist oder gesetzliche Aufbewahrungs-
        pflichten bestehen. Konkrete Fristen für die wichtigsten Kategorien:

        — Server-Logfiles: 14 Tage
        — Audit-Log der Plattform: pro Mandant konfigurierbar (Standard
          unbegrenzt, einstellbar bis 10 Jahre); auf Anfrage des Mandanten
          jederzeit kürzbar
        — Konto-Daten: für die Dauer der Vertragsbeziehung plus Karenzzeit
        — Inhaltliche Mandanten-Daten (Notfallhandbuch): bis zur Löschung
          durch den Mandanten oder Ende der Vertragsbeziehung

        9. DATENSICHERHEIT

        Wir setzen technisch-organisatorische Maßnahmen ein, die dem Stand
        der Technik entsprechen, insbesondere:

        — Transportverschlüsselung (TLS) für sämtliche Verbindungen
        — Passwörter werden ausschließlich als bcrypt-Hash gespeichert
        — Optional Zwei-Faktor-Authentifizierung (TOTP); für Administratoren
          erzwingbar
        — Mandantentrennung auf Anwendungs- und Datenbankebene
        — Lückenloser Audit-Log über sicherheitsrelevante Änderungen
        — Regelmäßige Datensicherungen

        10. IHRE RECHTE ALS BETROFFENE PERSON

        Soweit wir personenbezogene Daten von Ihnen verarbeiten, stehen
        Ihnen folgende Rechte zu:

        — Auskunft (Art. 15 DSGVO)
        — Berichtigung (Art. 16 DSGVO)
        — Löschung (Art. 17 DSGVO)
        — Einschränkung der Verarbeitung (Art. 18 DSGVO)
        — Datenübertragbarkeit (Art. 20 DSGVO)
        — Widerspruch (Art. 21 DSGVO), insbesondere gegen Verarbeitungen
          auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO
        — Widerruf erteilter Einwilligungen (Art. 7 Abs. 3 DSGVO) mit
          Wirkung für die Zukunft

        Bitte richten Sie Anfragen an info@arento.ai.

        11. BESCHWERDERECHT BEI DER AUFSICHTSBEHÖRDE

        Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde
        über die Verarbeitung Ihrer personenbezogenen Daten zu beschweren
        (Art. 77 DSGVO). Für unseren Sitz in Hennef ist zuständig:

        Landesbeauftragte für Datenschutz und Informationsfreiheit
        Nordrhein-Westfalen (LDI NRW)
        Kavalleriestraße 2-4
        40213 Düsseldorf
        Telefon: 0211 38424-0
        E-Mail: poststelle@ldi.nrw.de

        12. ÄNDERUNG DIESER DATENSCHUTZERKLÄRUNG

        Wir behalten uns vor, diese Datenschutzerklärung anzupassen, wenn
        sich rechtliche Vorgaben oder unsere Verarbeitungstätigkeiten
        ändern. Die jeweils aktuelle Fassung ist unter dieser URL abrufbar.

        TEXT;

    /**
     * @return array<string, array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}>
     */
    public static function all(): array
    {
        return [
            'registration_enabled' => [
                'scope' => self::SYSTEM,
                'type' => 'bool',
                'default' => true,
                'label' => 'Registrierung aktiv',
                'description' => 'Wenn deaktiviert, sehen Besucher kein Registrierungsformular.',
            ],
            'demo_locked' => [
                'scope' => self::SYSTEM,
                'type' => 'bool',
                'default' => false,
                'label' => 'Demo-Funktion sperren',
                'description' => 'Sperrt /admin/demo gegen versehentliches Wipe/Seed in Produktion.',
            ],
            'platform_name' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => '',
                'label' => 'Plattform-Name (Override)',
                'description' => 'Leer = APP_NAME aus .env. Wirkt im <title> und im Sidebar-Header.',
            ],
            'platform_footer' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => '',
                'label' => 'Plattform-Fußzeile',
                'description' => 'Optionaler Hinweis-Text (z. B. Impressum-Link), erscheint unten in der Sidebar.',
            ],
            'platform_contact_email' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => 'info@arento.ai',
                'label' => 'Kontakt-E-Mail',
                'description' => 'Wird auf der Landing-Page und in den Rechtsseiten ausgegeben.',
            ],
            'platform_contact_phone' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => '',
                'label' => 'Kontakt-Telefon',
                'description' => 'Wird auf der Landing-Page ausgegeben.',
            ],
            'platform_imprint' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => self::DEFAULT_IMPRINT,
                'label' => 'Impressum (Plain-Text/Markdown)',
                'description' => 'Pflichtangaben nach §5 TMG. Wird unter /impressum gerendert.',
            ],
            'platform_privacy' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => self::DEFAULT_PRIVACY,
                'label' => 'Datenschutzerklärung (Plain-Text/Markdown)',
                'description' => 'DSGVO-Pflichttext. Wird unter /datenschutz gerendert.',
            ],
            'platform_terms' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => "Anbieter\nArento AI GmbH i. G., Wiesenstr. 28, 53773 Hennef, info@arento.ai\n\nHinweis\nDieser Text ist ein Platzhalter und ersetzt KEINE wirksamen AGB. Bitte vor Produktivbetrieb durch eine fachkundige Stelle ausarbeiten lassen — insbesondere zu: Vertragsgegenstand, Leistungsumfang, Verfügbarkeit/SLA, Vergütung, Laufzeit/Kündigung, Haftung, Datenschutzauftragsverarbeitung, Gerichtsstand und anwendbarem Recht.",
                'label' => 'AGB (Plain-Text/Markdown)',
                'description' => 'Allgemeine Geschäftsbedingungen. Wird unter /agb gerendert.',
            ],

            'auto_pdf_enabled' => [
                'scope' => self::COMPANY,
                'type' => 'bool',
                'default' => false,
                'label' => 'Auto-PDF bei neuer Version',
                'description' => 'Erzeugt automatisch ein revisionssicheres PDF, sobald eine HandbookVersion angelegt wird.',
            ],
            'incident_mode_enabled' => [
                'scope' => self::COMPANY,
                'type' => 'bool',
                'default' => true,
                'label' => 'Live-Inzident-Modus',
                'description' => 'Zeigt im Ernstfall ein reduziertes Krisen-Cockpit mit Krisenstab, Wiederanlauf-Reihenfolge, Schritten und Meldepflichten. Bei einem aktiven Szenario-Lauf erscheint zusätzlich ein Banner.',
            ],
            'enforce_2fa_admins' => [
                'scope' => self::COMPANY,
                'type' => 'bool',
                'default' => false,
                'label' => '2FA-Pflicht für Team-Admins',
                'description' => 'Team-Admins ohne bestätigtes 2FA werden zur Security-Seite umgeleitet.',
            ],
            'share_link_default_days' => [
                'scope' => self::COMPANY,
                'type' => 'int',
                'default' => 30,
                'min' => 1,
                'max' => 365,
                'label' => 'Default-Laufzeit Freigabelinks (Tage)',
                'description' => 'Vorbelegung beim Anlegen eines neuen Freigabelinks.',
            ],
            'audit_retention_days' => [
                'scope' => self::COMPANY,
                'type' => 'int',
                'default' => 0,
                'min' => 0,
                'max' => 3650,
                'label' => 'Audit-Log Aufbewahrung (Tage)',
                'description' => '0 = unbegrenzt aufbewahren. Sonst tägliche Bereinigung älterer Einträge.',
            ],
            'pdf_paper_size' => [
                'scope' => self::COMPANY,
                'type' => 'enum',
                'default' => 'a4',
                'enum' => ['a4' => 'A4', 'letter' => 'US Letter'],
                'label' => 'PDF-Papierformat',
                'description' => '',
            ],
            'pdf_footer_show_hash' => [
                'scope' => self::COMPANY,
                'type' => 'bool',
                'default' => true,
                'label' => 'SHA-256 im PDF-Footer',
                'description' => 'Zeigt den PDF-Hash unten als Revisionsanker an.',
            ],
            'slack_webhook_url' => [
                'scope' => self::COMPANY,
                'type' => 'string',
                'default' => '',
                'label' => 'Slack-Webhook-URL',
                'description' => 'Incoming-Webhook-URL eines Slack-Channels. Vorlagen mit Kanal „Slack" werden hierhin gepostet.',
            ],
            'teams_webhook_url' => [
                'scope' => self::COMPANY,
                'type' => 'string',
                'default' => '',
                'label' => 'Microsoft-Teams-Webhook-URL',
                'description' => 'Incoming-Webhook-URL eines Teams-Channels. Vorlagen mit Kanal „Teams" werden hierhin gepostet.',
            ],
        ];
    }

    /**
     * @return array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}|null
     */
    public static function definition(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * @return array<string, array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}>
     */
    public static function byScope(string $scope): array
    {
        return array_filter(self::all(), fn ($def) => $def['scope'] === $scope);
    }

    public static function defaultFor(string $key): mixed
    {
        return self::definition($key)['default'] ?? null;
    }

    /**
     * Coerce a raw input value (typically string from a form) into the
     * type declared by the catalog. Unknown keys pass through unchanged.
     */
    public static function cast(string $key, mixed $value): mixed
    {
        $def = self::definition($key);
        if ($def === null) {
            return $value;
        }

        return match ($def['type']) {
            'bool' => (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'int' => (int) $value,
            'enum' => array_key_exists((string) $value, $def['enum'] ?? []) ? (string) $value : $def['default'],
            default => (string) $value,
        };
    }
}
