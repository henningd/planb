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

    private const DEFAULT_TERMS = <<<'TEXT'
        ALLGEMEINE GESCHÄFTSBEDINGUNGEN

        Stand: April 2026

        Hinweis: Diese AGB richten sich ausschließlich an Unternehmer im Sinne
        des § 14 BGB, juristische Personen des öffentlichen Rechts oder
        öffentlich-rechtliche Sondervermögen. Verbraucher im Sinne des § 13 BGB
        sind keine Vertragspartner.

        § 1 GELTUNGSBEREICH

        (1) Diese Allgemeinen Geschäftsbedingungen (nachfolgend „AGB") gelten
        für alle Verträge zwischen der Arento AI GmbH i. G., Wiesenstr. 28,
        53773 Hennef (nachfolgend „Anbieter") und ihren Kunden über die
        Bereitstellung der unter dieser Domain erreichbaren Plattform
        (nachfolgend „Plattform") sowie damit verbundene Leistungen.

        (2) Abweichende, entgegenstehende oder ergänzende Bedingungen des
        Kunden werden nicht Vertragsbestandteil, es sei denn, der Anbieter
        stimmt ihrer Geltung ausdrücklich schriftlich zu.

        (3) Maßgeblich ist die zum Zeitpunkt des Vertragsschlusses gültige
        Fassung dieser AGB.

        § 2 VERTRAGSGEGENSTAND

        (1) Der Anbieter stellt dem Kunden die Plattform als Software-as-a-
        Service (SaaS) zur Erfassung, Verwaltung und Auswertung von Notfall-
        und Krisenmanagement-Daten zur Nutzung über das Internet bereit.

        (2) Der Funktionsumfang ergibt sich aus der zum Zeitpunkt des Vertrags-
        schlusses verfügbaren Leistungsbeschreibung auf der Plattform-Website
        bzw. im jeweils gewählten Tarif. Der Anbieter ist berechtigt, den
        Leistungsumfang fortlaufend weiterzuentwickeln, soweit dadurch die
        bisherigen Hauptleistungen nicht wesentlich eingeschränkt werden.

        (3) Eine Verschaffung der Software in Eigentum oder zur dauerhaften
        Nutzung außerhalb der Plattform ist nicht geschuldet.

        § 3 VERTRAGSSCHLUSS, TESTPHASE

        (1) Die Darstellung der Leistungen auf der Website stellt kein
        verbindliches Angebot dar, sondern eine Aufforderung zur Abgabe eines
        Angebots durch den Kunden.

        (2) Mit der Registrierung eines Kontos und der Bestätigung dieser AGB
        gibt der Kunde ein Angebot zum Vertragsschluss ab. Der Vertrag kommt
        mit der Aktivierung des Zugangs durch den Anbieter zustande.

        (3) Der Anbieter kann unentgeltliche Testzugänge anbieten. Soweit
        nicht ausdrücklich abweichend vereinbart, enden Testzugänge automatisch
        nach Ablauf der vereinbarten Testdauer, ohne dass es einer Kündigung
        bedarf.

        § 4 LEISTUNGSUMFANG, VERFÜGBARKEIT

        (1) Der Anbieter stellt die Plattform mit einer angestrebten Verfüg-
        barkeit von 99 % im Jahresmittel bereit, gemessen am Anbieter-Server-
        ausgang. Ausgenommen sind Zeiten, in denen die Plattform aufgrund von
        Wartungsarbeiten, höherer Gewalt oder Störungen außerhalb des Einfluss-
        bereichs des Anbieters nicht erreichbar ist.

        (2) Geplante Wartungsfenster werden, soweit möglich, in betriebsarme
        Zeiten gelegt und mit angemessener Vorankündigung mitgeteilt.

        (3) Soweit zwischen den Parteien keine individuelle Service-Level-
        Vereinbarung getroffen wurde, gilt vorstehende Verfügbarkeit als
        ausreichend; weitergehende Reaktionszeiten oder Wiederherstellungs-
        zeiten werden nicht geschuldet.

        § 5 MITWIRKUNGSPFLICHTEN DES KUNDEN

        (1) Der Kunde ist verpflichtet, eine zur Plattform-Nutzung geeignete
        IT-Infrastruktur (insb. aktuellen Browser, stabile Internetverbindung)
        vorzuhalten.

        (2) Der Kunde ist verpflichtet, Zugangsdaten geheim zu halten, gegen
        unbefugten Zugriff zu schützen und den Anbieter unverzüglich zu
        informieren, wenn Anhaltspunkte für eine missbräuchliche Nutzung des
        Kontos bestehen. Sicherheitsfunktionen wie Zwei-Faktor-Authentifizierung
        sollen genutzt werden.

        (3) Der Kunde ist allein verantwortlich für die Inhalte, die er in der
        Plattform speichert oder über sie versendet, sowie dafür, dass er zur
        Verarbeitung der dort eingestellten personenbezogenen Daten nach DSGVO
        berechtigt ist.

        (4) Der Kunde ist verpflichtet, die Plattform nicht zu missbrauchen,
        insbesondere keine rechtswidrigen, beleidigenden oder Rechte Dritter
        verletzenden Inhalte einzustellen, keine Schadsoftware einzubringen
        und keine Sicherheitsmechanismen zu umgehen.

        § 6 NUTZUNGSRECHT

        (1) Der Anbieter räumt dem Kunden für die Vertragslaufzeit ein
        nicht-ausschließliches, nicht übertragbares und nicht unterlizenzier-
        bares Recht zur Nutzung der Plattform für die eigenen geschäftlichen
        Zwecke des Kunden ein.

        (2) Eine Weitergabe der Zugangsdaten an Dritte außerhalb der vom
        Kunden eingerichteten Mitarbeiter-Konten ist nicht zulässig. Im
        Rahmen des gewählten Tarifs ist die Anlage von Mitarbeiter-Konten
        zulässig.

        (3) Eine Vervielfältigung, Bearbeitung oder Reverse-Engineering der
        Plattform-Software ist nur in den vom Gesetz zwingend zugelassenen
        Fällen gestattet.

        § 7 DATEN DES KUNDEN, AUFTRAGSVERARBEITUNG

        (1) Sämtliche vom Kunden auf der Plattform gespeicherten Daten
        bleiben Eigentum des Kunden.

        (2) Soweit der Anbieter im Rahmen der Vertragsdurchführung perso-
        nenbezogene Daten im Auftrag des Kunden verarbeitet, schließen die
        Parteien zusätzlich einen Vertrag zur Auftragsverarbeitung nach
        Art. 28 DSGVO. Der Kunde bleibt Verantwortlicher im Sinne der DSGVO.

        (3) Der Anbieter führt regelmäßige Datensicherungen durch. Der
        Kunde ist gleichwohl gehalten, eigene Sicherungen wesentlicher
        Daten vorzunehmen, soweit diese außerhalb der Plattform benötigt
        werden. Die Plattform stellt hierfür Export-Funktionen bereit.

        (4) Nach Vertragsende stellt der Anbieter dem Kunden auf Anforderung
        innerhalb einer angemessenen Frist die Kundendaten in einem strukturierten,
        maschinenlesbaren Format zur Verfügung. Anschließend werden die
        Daten gelöscht, soweit keine gesetzlichen Aufbewahrungspflichten
        entgegenstehen.

        § 8 VERGÜTUNG, ZAHLUNGSBEDINGUNGEN

        (1) Die Vergütung richtet sich nach dem zum Zeitpunkt des Vertrags-
        schlusses gültigen Tarif. Die ausgewiesenen Preise sind Netto-Preise
        zzgl. der jeweils geltenden gesetzlichen Umsatzsteuer.

        (2) Soweit nicht anders vereinbart, erfolgt die Abrechnung im Voraus
        für die jeweils gewählte Abrechnungsperiode (z. B. monatlich oder
        jährlich) per Lastschrift, Kreditkarte oder Rechnung.

        (3) Bei Zahlungsverzug ist der Anbieter berechtigt, gesetzliche
        Verzugszinsen geltend zu machen und nach erfolgloser Mahnung den
        Zugang zur Plattform zu sperren.

        (4) Der Anbieter ist berechtigt, die Vergütung mit einer Ankündigungs-
        frist von mindestens sechs (6) Wochen zum Beginn einer neuen Abrech-
        nungsperiode anzupassen. Erhöht der Anbieter die Vergütung um mehr
        als 5 Prozent gegenüber der vorigen Periode, steht dem Kunden ein
        Sonderkündigungsrecht zum Wirksamwerden der Erhöhung zu.

        § 9 VERTRAGSLAUFZEIT, KÜNDIGUNG

        (1) Der Vertrag wird, soweit nicht abweichend vereinbart, auf
        unbestimmte Zeit geschlossen und kann von jeder Partei mit einer
        Frist von einem Monat zum Ende eines jeden Abrechnungszeitraums
        gekündigt werden.

        (2) Eine Kündigung aus wichtigem Grund bleibt unberührt. Ein wichtiger
        Grund liegt für den Anbieter insbesondere bei wesentlichem Verstoß
        des Kunden gegen seine Mitwirkungspflichten oder bei Zahlungsverzug
        von mehr als zwei aufeinanderfolgenden Abrechnungszeiträumen vor.

        (3) Kündigungen bedürfen mindestens der Textform (E-Mail an die im
        Impressum genannte Adresse genügt).

        § 10 ÄNDERUNGEN DIESER AGB UND DER LEISTUNGEN

        (1) Der Anbieter ist berechtigt, diese AGB sowie die Leistungs-
        beschreibung mit einer Ankündigungsfrist von mindestens sechs (6)
        Wochen anzupassen, soweit dies aus rechtlichen, technischen oder
        wirtschaftlichen Gründen erforderlich ist und die Anpassung den
        Kunden nicht unangemessen benachteiligt.

        (2) Widerspricht der Kunde der Änderung nicht innerhalb von vier (4)
        Wochen nach Zugang der Änderungsmitteilung in Textform, gilt die
        Änderung als angenommen. Auf diese Folge wird der Anbieter in der
        Änderungsmitteilung gesondert hinweisen. Im Falle eines fristgerechten
        Widerspruchs steht dem Anbieter ein Sonderkündigungsrecht zum Wirksam-
        werden der Änderung zu.

        § 11 HAFTUNG

        (1) Der Anbieter haftet unbeschränkt für Schäden aus der Verletzung
        des Lebens, des Körpers oder der Gesundheit, die auf einer vorsätz-
        lichen oder fahrlässigen Pflichtverletzung des Anbieters, eines seiner
        gesetzlichen Vertreter oder Erfüllungsgehilfen beruhen, sowie für
        sonstige Schäden, die auf einer vorsätzlichen oder grob fahrlässigen
        Pflichtverletzung beruhen.

        (2) Bei leicht fahrlässiger Verletzung wesentlicher Vertragspflichten
        (Kardinalpflichten) ist die Haftung des Anbieters auf den vertrags-
        typisch vorhersehbaren Schaden begrenzt. Wesentliche Vertragspflichten
        sind solche, deren Erfüllung die ordnungsgemäße Durchführung des
        Vertrages überhaupt erst ermöglicht und auf deren Einhaltung der
        Kunde regelmäßig vertrauen darf.

        (3) Im Übrigen ist die Haftung für leichte Fahrlässigkeit ausge-
        schlossen, soweit nicht zwingend gesetzlich gehaftet wird (z. B.
        nach dem Produkthaftungsgesetz).

        (4) Für den Verlust von Daten haftet der Anbieter nur in dem Umfang,
        in dem ein Schaden auch bei ordnungsgemäßer und regelmäßiger Daten-
        sicherung durch den Kunden eingetreten wäre. Der Kunde ist gehalten,
        eigene Datensicherungen vorzunehmen.

        § 12 SCHUTZRECHTE, GEHEIMHALTUNG

        (1) Sämtliche Rechte an der Plattform-Software, deren Quellcode,
        zugehörigen Dokumentationen und Marken stehen dem Anbieter zu.

        (2) Die Parteien werden alle ihnen im Rahmen der Geschäftsbeziehung
        bekannt gewordenen vertraulichen Informationen der jeweils anderen
        Partei vertraulich behandeln und nicht an Dritte weitergeben, es sei
        denn, dies ist zur Erfüllung des Vertrages oder aufgrund gesetzlicher
        Vorgaben erforderlich.

        § 13 SCHLUSSBESTIMMUNGEN

        (1) Nebenabreden, Änderungen und Ergänzungen dieses Vertrages
        bedürfen mindestens der Textform. Dies gilt auch für die Aufhebung
        dieser Klausel.

        (2) Auf das Vertragsverhältnis findet ausschließlich das Recht der
        Bundesrepublik Deutschland unter Ausschluss des UN-Kaufrechts
        (CISG) Anwendung.

        (3) Ausschließlicher Gerichtsstand für alle Streitigkeiten aus oder
        im Zusammenhang mit diesem Vertrag ist, soweit der Kunde Kaufmann,
        juristische Person des öffentlichen Rechts oder öffentlich-rechtliches
        Sondervermögen ist, der Sitz des Anbieters.

        (4) Sollte eine Bestimmung dieses Vertrages unwirksam sein oder
        werden, so berührt dies die Wirksamkeit der übrigen Bestimmungen
        nicht. An die Stelle der unwirksamen Bestimmung tritt die gesetzliche
        Regelung; ist eine solche nicht vorhanden, gilt eine wirksame Regelung
        als vereinbart, die dem wirtschaftlichen Zweck der unwirksamen
        Bestimmung am nächsten kommt.

        TEXT;

    private const DEFAULT_AV_CONTRACT = <<<'TEXT'
        VERTRAG ZUR AUFTRAGSVERARBEITUNG NACH ART. 28 DSGVO

        Stand: April 2026

        zwischen

        — dem Verantwortlichen (Kunde, im Folgenden „Auftraggeber") und
        — der Arento AI GmbH i. G., Wiesenstr. 28, 53773 Hennef
          (im Folgenden „Auftragnehmer" oder „Auftragsverarbeiter").

        § 1 GEGENSTAND UND DAUER

        (1) Der Auftragnehmer verarbeitet personenbezogene Daten im Auftrag
        des Auftraggebers im Rahmen des zwischen den Parteien geschlossenen
        Hauptvertrags über die Nutzung der Plattform.

        (2) Die Dauer der Auftragsverarbeitung entspricht der Laufzeit des
        Hauptvertrags.

        § 2 ART, UMFANG UND ZWECK DER VERARBEITUNG

        (1) Gegenstand der Verarbeitung sind personenbezogene Daten, die der
        Auftraggeber im Rahmen der Plattform-Nutzung selbst eingibt:
        Mitarbeiter-Stammdaten, Krisenrollen, Kontaktdaten, ggf. Notfall-
        Informationen.

        (2) Zweck der Verarbeitung ist ausschließlich die Bereitstellung der
        Plattform und der vereinbarten Funktionen.

        (3) Die betroffenen Personenkategorien sind:
        — Mitarbeiter des Auftraggebers,
        — externe Dienstleister-Kontakte des Auftraggebers,
        — Versicherungs- und Behördenkontakte (Klartextdaten),
        — Plattform-Benutzer des Auftraggebers (App-Zugang).

        § 3 PFLICHTEN DES AUFTRAGNEHMERS

        Der Auftragnehmer verpflichtet sich:
        — die Daten ausschließlich auf Weisung des Auftraggebers zu verarbeiten,
        — alle eingesetzten Personen auf das Datengeheimnis zu verpflichten,
        — geeignete technische und organisatorische Maßnahmen (TOM) gemäß
          Art. 32 DSGVO zu treffen — siehe separates Dokument unter /tom,
        — bei der Wahrung der Betroffenenrechte (Art. 15–22 DSGVO) angemessen
          mitzuwirken,
        — den Auftraggeber unverzüglich (spätestens binnen 24 Stunden nach
          Kenntnisnahme) bei einer Datenpanne zu informieren,
        — Datenschutz-Folgenabschätzungen zu unterstützen, soweit erforderlich.

        § 4 SUBUNTERNEHMER (UNTERAUFTRAGSVERARBEITER)

        (1) Der Auftraggeber stimmt dem Einsatz der unter /subprocessors
        gelisteten Unterauftragsverarbeiter ausdrücklich zu.

        (2) Der Auftragnehmer wird neue Subunternehmer mindestens 30 Tage
        vor Aufnahme der Verarbeitung in Textform anzeigen. Der Auftraggeber
        kann widersprechen; bei berechtigtem Widerspruch besteht ein
        Sonderkündigungsrecht für den Hauptvertrag.

        § 5 WEISUNGSBEFUGNIS

        (1) Weisungen sind in Textform zu erteilen. Der Auftragnehmer hat den
        Auftraggeber unverzüglich zu informieren, wenn er der Auffassung ist,
        dass eine Weisung gegen die DSGVO verstößt.

        (2) Mündliche Weisungen sind unverzüglich in Textform zu bestätigen.

        § 6 KONTROLLRECHTE

        (1) Der Auftraggeber hat das Recht, die Einhaltung der Vorschriften
        dieses Vertrags durch geeignete Maßnahmen zu kontrollieren — in der
        Regel durch Vorlage aktueller Selbstauskünfte, Zertifikate oder
        Berichte unabhängiger Prüfer.

        (2) Vor-Ort-Prüfungen finden nur in dringenden Ausnahmefällen statt
        und werden mit angemessener Frist (mindestens 30 Tage) vorab
        vereinbart.

        § 7 DATENRÜCKGABE UND LÖSCHUNG

        (1) Nach Beendigung des Hauptvertrags stellt der Auftragnehmer dem
        Auftraggeber auf Anforderung sämtliche im Auftrag verarbeiteten Daten
        in einem strukturierten, maschinenlesbaren Format zur Verfügung
        (siehe Funktion „Mandanten-Archiv (ZIP)" in der Plattform).

        (2) Anschließend werden die Daten gelöscht, soweit keine gesetzlichen
        Aufbewahrungspflichten entgegenstehen.

        § 8 HAFTUNG

        Die Haftung richtet sich nach den Regelungen des Hauptvertrags und
        nach Art. 82 DSGVO.

        § 9 SCHLUSSBESTIMMUNGEN

        (1) Diese Vereinbarung ist Anlage zum Hauptvertrag und erlischt mit
        dessen Beendigung.

        (2) Bei Widersprüchen zwischen diesem AVV und dem Hauptvertrag gehen
        die Regelungen dieses AVV vor, soweit es um die Verarbeitung perso-
        nenbezogener Daten geht.

        Hinweis: Diese Grundstruktur ist eine Vorlage und ersetzt keine
        juristische Prüfung im Einzelfall. Vor Abschluss durch eine fach-
        kundige Stelle prüfen lassen — insbesondere bei Behörden-Kunden
        und KRITIS-Betreibern, die eigene Mustervorlagen vorgeben.

        TEXT;

    private const DEFAULT_TOM = <<<'TEXT'
        TECHNISCHE UND ORGANISATORISCHE MAßNAHMEN (TOM) NACH ART. 32 DSGVO

        Stand: April 2026

        Verantwortlich
        Arento AI GmbH i. G., Wiesenstr. 28, 53773 Hennef
        E-Mail: info@arento.ai

        Diese TOM-Liste beschreibt die zum Schutz personenbezogener Daten
        eingesetzten Maßnahmen und ist Anlage zum Vertrag zur Auftrags-
        verarbeitung.

        1. VERTRAULICHKEIT (ART. 32 ABS. 1 LIT. B DSGVO)

        Zutrittskontrolle (physisch)
        — Hosting bei DigitalOcean (Rechenzentrum Frankfurt FRA1, ISO 27001-
          zertifiziert).
        — Kein direkter Zutritt der Mitarbeiter des Auftragnehmers zu Servern.
        — Büroräume sind durch Schließanlage und Alarmanlage gesichert.

        Zugangskontrolle (logisch)
        — Authentifizierung mit individuellen Benutzerkonten (E-Mail + Passwort).
        — Passwörter gespeichert als bcrypt-Hash (nicht im Klartext).
        — Zwei-Faktor-Authentifizierung (TOTP) optional, für Admins erzwingbar.
        — Brute-Force-Schutz mit Login-Throttling.
        — Sitzungs-Cookies sind HttpOnly, Secure, SameSite=Lax.

        Zugriffskontrolle (Berechtigungskonzept)
        — Drei Rollen pro Mandant: Owner, Admin, Member.
        — Sensible Bereiche (Versicherungen, Audit-Log, Versionsfreigaben)
          nur für Admin/Owner.
        — Mandantentrennung auf Anwendungs- und Datenbankebene
          (BelongsToCurrentCompany-Trait, Global Scope).
        — Lückenloses Audit-Log über alle sicherheitsrelevanten Änderungen.

        Trennungskontrolle (Mandantentrennung)
        — Jeder Datensatz hat ein company_id-Feld; sämtliche Queries werden
          automatisch gefiltert.
        — Cross-Tenant-Zugriffe sind weder über die UI noch über die API
          möglich (HTTP 403).

        Pseudonymisierung
        — UUIDs als Primärschlüssel; keine sequentiellen IDs in URLs.
        — Personenbezogene Daten werden nicht pseudonymisiert für die
          Verarbeitung selbst, da der Zweck der Plattform die direkte
          Adressierbarkeit der Personen erfordert (Notfall-Kontaktdaten).

        2. INTEGRITÄT (ART. 32 ABS. 1 LIT. B DSGVO)

        Eingabekontrolle
        — Vollständiges Audit-Log (Wer, Wann, Was, Vorher/Nachher) für alle
          audit-relevanten Tabellen.
        — Audit-Log ist nur lesbar (keine Bearbeitung), automatische
          Aufbewahrung pro Mandant konfigurierbar.

        Weitergabekontrolle
        — Alle Verbindungen verschlüsselt mit TLS 1.2 oder höher.
        — HTTPS strict (HSTS-Header), keine HTTP-Endpunkte für Datenübertragung.
        — API-Webhooks ausschließlich mit Bearer-Token-Authentifizierung;
          Token werden als SHA-256-Hash gespeichert.

        3. VERFÜGBARKEIT UND BELASTBARKEIT (ART. 32 ABS. 1 LIT. B DSGVO)

        Verfügbarkeitskontrolle
        — Tägliche Datenbank-Backups, 30 Tage Aufbewahrung.
        — Hosting-Anbieter mit Multi-AZ-Architektur (Hochverfügbarkeit).
        — Monitoring der Anwendung mit automatischen Alarmen.

        Wiederherstellbarkeit
        — Datenbank-Restore aus Backup innerhalb von 4 Stunden.
        — Mandanten-Archiv (ZIP) jederzeit durch den Auftraggeber abrufbar
          (vollständiger Selbst-Export aller Stammdaten, Audit-Log und
          Handbuch-PDFs).

        4. VERFAHREN ZUR REGELMÄßIGEN ÜBERPRÜFUNG (ART. 32 ABS. 1 LIT. D DSGVO)

        — Sicherheits-Updates der Plattform-Komponenten werden mindestens
          monatlich eingespielt; sicherheitskritische Updates innerhalb von
          7 Tagen.
        — Composer- und npm-Abhängigkeiten werden auf bekannte
          Schwachstellen gescannt (Composer Audit, npm audit).
        — Automatisierte Test-Suite mit über 600 Tests, die bei jedem
          Release durchläuft.
        — Vulnerability-Disclosure-Policy unter /.well-known/security.txt
          mit Kontakt für Sicherheits-Meldungen.

        5. INCIDENT-RESPONSE

        — Internes Incident-Response-Verfahren mit Rollen, Eskalation und
          Meldekette.
        — Datenpannen werden binnen 24 Stunden nach Kenntnisnahme an
          betroffene Auftraggeber gemeldet.
        — Aufsichtsbehörde wird gemäß Art. 33 DSGVO binnen 72 Stunden
          informiert.

        Hinweis: Diese TOM beschreibt den aktuellen Stand. Maßnahmen werden
        fortlaufend dem Stand der Technik angepasst. Wesentliche Änderungen
        werden den Auftraggebern in Textform mitgeteilt.

        TEXT;

    private const DEFAULT_ACCESSIBILITY = <<<'TEXT'
        ERKLÄRUNG ZUR BARRIEREFREIHEIT

        Stand: April 2026

        Diese Erklärung zur digitalen Barrierefreiheit gilt für die unter
        dieser Domain erreichbare Plattform PlanB einschließlich aller
        eingeloggten Bereiche (Dashboard, Notfallhandbuch, Krisen-Cockpit,
        Compliance-Module) sowie der öffentlichen Marketing- und
        Rechtsseiten.

        ## Konformitätsstand

        Die Plattform ist nach Selbsteinschätzung des Anbieters **weitgehend
        konform** mit den Web Content Accessibility Guidelines (WCAG) 2.1
        Level AA und der zugrundeliegenden europäischen Norm EN 301 549.
        Eine externe Prüfung durch eine offizielle Überwachungsstelle ist
        bisher nicht erfolgt.

        Für öffentliche Auftraggeber gilt zusätzlich die Barrierefreie-
        Informationstechnik-Verordnung (BITV 2.0), für privatwirtschaftliche
        Anbieter ab Juni 2025 das Barrierefreiheitsstärkungsgesetz (BFSG).

        ## Bekannte Barrieren

        Die folgenden Bereiche entsprechen aktuell **nicht vollständig** den
        WCAG-2.1-AA-Anforderungen:

        - **PDF-Exporte**: Generierte Notfallhandbuch-, Audit-Log- und
          Versions-PDFs sind sichtbar lesbar, aber noch nicht in vollem
          Umfang als „tagged PDF" gemäß PDF/UA strukturiert.
          Screenreader-Nutzer können den Inhalt zwar erfassen, eine
          fehlerfreie Vorlese-Reihenfolge ist nicht in jedem Fall
          gewährleistet. Wir arbeiten an einer barrierefreien Variante.
        - **Komplexe Diagramme** (Risiko-Heatmap, Recovery-Gantt,
          Abhängigkeitsgraphen): Werden derzeit primär visuell dargestellt.
          Eine vollständige Text-Alternative für jede Position ist noch
          nicht implementiert. Die zugrundeliegenden Daten sind aber
          jeweils in einer parallel angezeigten Tabelle abrufbar.
        - **Drag-and-Drop-Interaktionen** (z. B. Aufgaben-Inbox,
          Kanban-Spalten): Tastatur-Alternativen über Sortier-Buttons sind
          vorhanden, aber die Bedienung ist mit Tastatur weniger flüssig
          als per Maus.

        ## Bereits erreichbare Stärken

        - Die gesamte Plattform ist **per Tastatur** vollständig bedienbar
          (Tab-Reihenfolge, Fokus-Indikatoren, Skip-Links).
        - **Sichtbare Fokus-Marker** auf allen interaktiven Elementen.
        - **Semantisches HTML** (Überschriften-Hierarchie, Landmarks,
          ARIA-Labels) für die Hauptansichten.
        - **Kontraste** der Standard-Oberfläche entsprechen mindestens
          WCAG-AA (4,5:1 für Fließtext, 3:1 für Bedienelemente).
        - **Responsive Design**: Die Inhalte funktionieren in einer
          Bandbreite von 320 px bis 4K ohne horizontales Scrollen.
        - **Keine Zeitlimits** auf Formularen; sicherheitsrelevante
          Sitzungen warnen rechtzeitig vor dem Ablauf.

        ## Erstellung dieser Erklärung

        Diese Erklärung wurde am **15. April 2026** auf Basis einer
        Selbstbewertung des Anbieters erstellt. Sie wird mindestens
        **einmal jährlich** sowie bei wesentlichen Änderungen der
        Plattform überprüft und aktualisiert.

        ## Feedback und Kontakt

        Sie haben eine Barriere entdeckt, eine Information ist nicht
        zugänglich oder Sie benötigen einen barrierefreien Auszug
        (z. B. Notfallhandbuch in einer alternativen Form)? Bitte
        wenden Sie sich an:

        Arento AI GmbH i. G.
        Wiesenstr. 28
        53773 Hennef
        E-Mail: barrierefreiheit@arento.ai

        Wir bemühen uns, Rückmeldungen innerhalb von **vier Wochen** zu
        beantworten.

        ## Schlichtungsverfahren (für Bundesbehörden)

        Sollten Sie als Nutzer einer öffentlichen Stelle des Bundes mit
        einer Antwort des Anbieters nicht zufrieden sein, können Sie sich
        an die **Schlichtungsstelle nach § 16 Behindertengleichstellungs-
        gesetz (BGG)** wenden:

        Schlichtungsstelle nach dem Behindertengleichstellungsgesetz
        bei dem Beauftragten der Bundesregierung für die Belange von
        Menschen mit Behinderungen
        Mauerstraße 53
        10117 Berlin
        Telefon: +49 30 18527-2805
        E-Mail: info@schlichtungsstelle-bgg.de
        Web: https://www.schlichtungsstelle-bgg.de

        Die Schlichtung ist für Sie kostenfrei.

        TEXT;

    private const DEFAULT_SUBPROCESSORS = <<<'TEXT'
        SUBUNTERAUFTRAGSVERARBEITER (SUBPROCESSORS)

        Stand: April 2026

        Diese Liste beschreibt alle Unterauftragsverarbeiter, die im Rahmen
        der Plattform-Bereitstellung eingesetzt werden. Mit jedem Anbieter
        besteht ein Vertrag zur Auftragsverarbeitung nach Art. 28 DSGVO.

        EINGESETZTE SUBPROCESSORS

        | Anbieter | Sitz | Zweck | Drittland | Rechtsgrundlage |
        |---|---|---|---|---|
        | DigitalOcean, LLC | USA, Region FRA1 (Frankfurt) | Hosting, Datenbank, Datei-Speicher | Datenhaltung in der EU; Drittland-Risiko nur bei US-Anbieter-Strukturen | Art. 28 + EU-SCC (Art. 46 DSGVO) |
        | Strato AG | Deutschland | E-Mail-Versand und -Empfang | nein | Art. 28 DSGVO |
        | avento.ai | (auf Anfrage) | SMS-Versand für Krisen-Kommunikation, sofern Funktion genutzt | abhängig vom Anbieter — wird auf Anfrage geklärt | Art. 28 DSGVO |

        OPTIONALE SUBPROCESSORS (NUR BEI AKTIVER NUTZUNG)

        Werden ausschließlich aktiviert, wenn der Auftraggeber die ent-
        sprechende Funktion in den Plattform-Einstellungen einschaltet:

        | Anbieter | Sitz | Zweck | Drittland | Rechtsgrundlage |
        |---|---|---|---|---|
        | Slack Technologies, LLC | USA | Versand in mandanteneigene Slack-Channels via Webhook | ja (USA) | EU-SCC (Art. 46 DSGVO) + ergänzende Maßnahmen |
        | Microsoft Corporation / Microsoft Ireland Operations Ltd. | EU/USA | Versand in mandanteneigene Teams-Channels via Webhook | je nach Tenant-Konfiguration des Auftraggebers | EU-SCC (Art. 46 DSGVO) |
        | Telegram FZ-LLC | VAE | Versand in mandanteneigene Telegram-Kanäle | ja (VAE) | EU-SCC (Art. 46 DSGVO) + ergänzende Maßnahmen |

        ÄNDERUNGEN AN DIESER LISTE

        Neue Subprocessors werden mindestens 30 Tage vor Aufnahme der
        Verarbeitung in Textform angekündigt. Auftraggeber haben das Recht,
        zu widersprechen — siehe § 4 des AVV.

        Diese Liste wird mindestens jährlich überprüft und bei Änderungen
        sofort aktualisiert.

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
                'default' => self::DEFAULT_TERMS,
                'label' => 'AGB (Plain-Text/Markdown)',
                'description' => 'Allgemeine Geschäftsbedingungen. Wird unter /agb gerendert.',
            ],
            'platform_av_contract' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => self::DEFAULT_AV_CONTRACT,
                'label' => 'AVV (Auftragsverarbeitung Art. 28 DSGVO)',
                'description' => 'Vertragsvorlage zur Auftragsverarbeitung. Wird unter /auftragsverarbeitung gerendert.',
            ],
            'platform_tom' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => self::DEFAULT_TOM,
                'label' => 'TOM (Technische und organisatorische Maßnahmen)',
                'description' => 'TOM nach Art. 32 DSGVO. Anlage zum AVV, wird unter /tom gerendert.',
            ],
            'platform_subprocessors' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => self::DEFAULT_SUBPROCESSORS,
                'label' => 'Subprocessor-Liste',
                'description' => 'Liste der Unterauftragsverarbeiter. Wird unter /subprocessors gerendert.',
            ],
            'platform_accessibility' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => self::DEFAULT_ACCESSIBILITY,
                'label' => 'Erklärung zur Barrierefreiheit (Markdown)',
                'description' => 'Barrierefreiheitserklärung nach BITV 2.0 / BFSG. Wird unter /barrierefreiheit gerendert.',
            ],
            'platform_security_contact' => [
                'scope' => self::SYSTEM,
                'type' => 'string',
                'default' => 'security@arento.ai',
                'label' => 'Security-Kontakt-E-Mail',
                'description' => 'Wird in /.well-known/security.txt gerendert (RFC 9116). Empfehlung: dedizierte Adresse, nicht die Firmen-Hauptadresse.',
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
