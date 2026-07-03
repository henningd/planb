<?php

namespace App\Support\Marketing;

use App\Enums\Nis2Readiness;
use App\Support\Bcms\MaturityCatalog;

/**
 * Statischer Fragenkatalog für den öffentlichen NIS2-Quick-Check (Lead-Magnet).
 *
 * Fünf Handlungsfelder mit je zwei Fragen. Jede Frage wird auf einer
 * Drei-Punkte-Skala beantwortet: `no` = 0, `partial` = 1, `yes` = 2 Punkte.
 * Aus der Gesamtpunktzahl im Verhältnis zum Maximum wird die
 * {@see Nis2Readiness}-Stufe abgeleitet.
 *
 * Bewusst wie der {@see MaturityCatalog} als versionierter
 * PHP-Katalog gehalten – Marketing-Inhalte gehören ins Repository, nicht in
 * die Datenbank. Jede Dimension liefert zusätzlich einen Empfehlungstext, der
 * in der Ergebnis-Auswertung angezeigt wird, wenn dort noch Lücken bestehen.
 *
 * @phpstan-type Question array{key: string, text: string}
 * @phpstan-type Dimension array{key: string, title: string, recommendation: string, questions: array<int, Question>}
 */
class Nis2QuickCheckCatalog
{
    /**
     * Punktwerte je Antwortmöglichkeit.
     */
    public const ANSWER_SCORES = [
        'no' => 0,
        'partial' => 1,
        'yes' => 2,
    ];

    /**
     * Die fünf Handlungsfelder mit ihren Fragen und Empfehlungen.
     *
     * @return array<int, Dimension>
     */
    public static function dimensions(): array
    {
        return [
            [
                'key' => 'betroffenheit',
                'title' => 'Betroffenheit & Verantwortung',
                'recommendation' => 'Klären Sie zuerst verbindlich, ob und als was Sie unter NIS2 fallen – als „wichtige" oder „besonders wichtige" Einrichtung (abhängig von Sektor und Unternehmensgröße). Verankern Sie die Verantwortung für Cybersicherheit auf Leitungsebene inklusive nachweisbarer Schulung: NIS2 macht die Geschäftsführung persönlich haftbar.',
                'questions' => [
                    [
                        'key' => 'betroffenheit_geklaert',
                        'text' => 'Wissen Sie sicher, ob Ihr Unternehmen unter die NIS2-Pflichten fällt (nach Sektor und Größe)?',
                    ],
                    [
                        'key' => 'leitung_verantwortung',
                        'text' => 'Hat die Geschäftsführung die Verantwortung für Cybersicherheit verbindlich übernommen und sich dazu schulen lassen?',
                    ],
                ],
            ],
            [
                'key' => 'risikomanagement',
                'title' => 'Risikomanagement & Sicherheitsmaßnahmen',
                'recommendation' => 'Dokumentieren Sie eine Risikoanalyse für Ihre kritischen Systeme und legen Sie technische wie organisatorische Maßnahmen fest – etwa Zugriffskontrolle, Mehr-Faktor-Authentifizierung und geregeltes Patch-Management.',
                'questions' => [
                    [
                        'key' => 'risikoanalyse',
                        'text' => 'Gibt es eine dokumentierte Risikoanalyse für Ihre geschäftskritischen IT-Systeme?',
                    ],
                    [
                        'key' => 'sicherheitsmassnahmen',
                        'text' => 'Sind grundlegende Schutzmaßnahmen (z. B. Zugriffskontrolle, MFA, Patch-Management) verbindlich festgelegt?',
                    ],
                ],
            ],
            [
                'key' => 'incident',
                'title' => 'Vorfallserkennung & Meldepflicht (24h/72h)',
                'recommendation' => 'NIS2 verlangt gestufte Meldungen: eine Erstmeldung binnen 24 Stunden, eine ausführliche Meldung binnen 72 Stunden und einen Abschlussbericht binnen eines Monats. Definieren Sie einen klaren Prozess, um Vorfälle zu erkennen, zu bewerten und fristgerecht an die zuständige Stelle (in Deutschland das BSI) zu melden.',
                'questions' => [
                    [
                        'key' => 'incident_prozess',
                        'text' => 'Gibt es einen definierten Prozess, um Sicherheitsvorfälle zu erkennen und zu behandeln?',
                    ],
                    [
                        'key' => 'meldefrist',
                        'text' => 'Könnten Sie einen meldepflichtigen Vorfall fristgerecht melden (Erstmeldung binnen 24 h, Meldung binnen 72 h)?',
                    ],
                ],
            ],
            [
                'key' => 'continuity',
                'title' => 'Notfallvorsorge & Wiederanlauf',
                'recommendation' => 'Halten Sie Notfall- und Wiederanlaufpläne mit getesteten Backups und definierten Wiederanlaufzeiten (RTO) für Ihre kritischen Systeme bereit – und üben Sie den Ernstfall regelmäßig, damit die Pläne im Notfall tatsächlich tragen.',
                'questions' => [
                    [
                        'key' => 'notfallplan',
                        'text' => 'Existiert ein Notfall- bzw. Wiederanlaufplan (inkl. getesteter Backups) für Ihre kritischen Systeme?',
                    ],
                    [
                        'key' => 'uebungen',
                        'text' => 'Werden Notfälle regelmäßig geübt und die Pläne dabei aktuell gehalten?',
                    ],
                ],
            ],
            [
                'key' => 'lieferkette',
                'title' => 'Lieferkette & Datensouveränität',
                'recommendation' => 'NIS2 nimmt auch Ihre Lieferkette in die Pflicht. Bewerten Sie die Sicherheit Ihrer wichtigsten IT-Dienstleister und verschaffen Sie sich Klarheit darüber, wo Ihre Daten liegen – Drittstaaten-Speicherung kann mit CLOUD Act und DSGVO kollidieren.',
                'questions' => [
                    [
                        'key' => 'dienstleister_bewertung',
                        'text' => 'Bewerten Sie die Informationssicherheit Ihrer wichtigsten IT-Dienstleister und Lieferanten?',
                    ],
                    [
                        'key' => 'datenstandort',
                        'text' => 'Wissen Sie, wo Ihre Daten gespeichert werden (EU oder Drittstaat, mögliches CLOUD-Act-Risiko)?',
                    ],
                ],
            ],
        ];
    }

    /**
     * Maximal erreichbare Gesamtpunktzahl (Anzahl Fragen × 2).
     */
    public static function maxScore(): int
    {
        return count(self::allKeys()) * max(self::ANSWER_SCORES);
    }

    /**
     * Alle Frage-Keys über sämtliche Handlungsfelder hinweg.
     *
     * @return array<int, string>
     */
    public static function allKeys(): array
    {
        $keys = [];

        foreach (self::dimensions() as $dimension) {
            foreach ($dimension['questions'] as $question) {
                $keys[] = $question['key'];
            }
        }

        return $keys;
    }

    /**
     * Punktzahl aus einem Antwort-Array berechnen. Unbekannte oder fehlende
     * Antworten zählen als `no` (0 Punkte).
     *
     * @param  array<string, string>  $answers
     */
    public static function scoreFor(array $answers): int
    {
        $score = 0;

        foreach (self::allKeys() as $key) {
            $answer = $answers[$key] ?? 'no';
            $score += self::ANSWER_SCORES[$answer] ?? 0;
        }

        return $score;
    }

    /**
     * Lesbare Bezeichnung einer Antwortmöglichkeit.
     */
    public static function answerLabel(string $answer): string
    {
        return match ($answer) {
            'yes' => 'Ja',
            'partial' => 'Teilweise',
            default => 'Nein',
        };
    }

    /**
     * Handlungsfelder, in denen noch mindestens eine Frage nicht mit „Ja“
     * beantwortet wurde – samt zugehörigem Empfehlungstext. Basis sowohl für
     * die Ergebnisanzeige im Assistenten als auch für die PDF-Auswertung.
     *
     * @param  array<string, string>  $answers
     * @return array<int, array{key: string, title: string, recommendation: string}>
     */
    public static function openRecommendations(array $answers): array
    {
        $open = [];

        foreach (self::dimensions() as $dimension) {
            foreach ($dimension['questions'] as $question) {
                if (($answers[$question['key']] ?? 'no') !== 'yes') {
                    $open[] = [
                        'key' => $dimension['key'],
                        'title' => $dimension['title'],
                        'recommendation' => $dimension['recommendation'],
                    ];
                    break;
                }
            }
        }

        return $open;
    }

    /**
     * NIS2-Bereitschaftsstufe aus erreichter Punktzahl im Verhältnis zum
     * Maximum ableiten.
     */
    public static function readinessForScore(int $score, int $max): Nis2Readiness
    {
        if ($max <= 0) {
            return Nis2Readiness::Kritisch;
        }

        $ratio = $score / $max;

        return match (true) {
            $ratio < 0.4 => Nis2Readiness::Kritisch,
            $ratio < 0.8 => Nis2Readiness::Aufbau,
            default => Nis2Readiness::Solide,
        };
    }
}
