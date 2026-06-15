<?php

namespace App\Support\Bcms;

use App\Enums\BcmsStage;

/**
 * Statischer Fragenkatalog für das Reifegrad-Self-Assessment nach dem
 * BSI-200-4-Stufenmodell.
 *
 * Drei Handlungsfelder mit je drei Fragen. Jede Frage wird auf einer
 * Drei-Punkte-Skala beantwortet: `no` = 0, `partial` = 1, `yes` = 2 Punkte.
 * Aus der Gesamtpunktzahl im Verhältnis zum Maximum wird die BCMS-Stufe
 * abgeleitet (<40 % Reaktiv, <80 % Aufbau, sonst Standard).
 *
 * @phpstan-type Question array{key: string, text: string}
 * @phpstan-type Dimension array{title: string, questions: array<int, Question>}
 */
class MaturityCatalog
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
     * Die drei Handlungsfelder mit ihren Fragen.
     *
     * @return array<int, Dimension>
     */
    public static function dimensions(): array
    {
        return [
            [
                'title' => 'Vorsorge & BIA',
                'questions' => [
                    [
                        'key' => 'bia_durchgefuehrt',
                        'text' => 'Wurde eine Business-Impact-Analyse (BIA) für die kritischen Geschäftsprozesse durchgeführt?',
                    ],
                    [
                        'key' => 'kritische_prozesse_identifiziert',
                        'text' => 'Sind die zeitkritischen Prozesse und ihre Wiederanlaufzeiten (RTO) dokumentiert?',
                    ],
                    [
                        'key' => 'ressourcen_abhaengigkeiten_bekannt',
                        'text' => 'Sind die benötigten Ressourcen und Abhängigkeiten (IT, Personal, Lieferanten) erfasst?',
                    ],
                ],
            ],
            [
                'title' => 'Notfallbewältigung',
                'questions' => [
                    [
                        'key' => 'notfallplaene_vorhanden',
                        'text' => 'Liegen dokumentierte Notfall- und Wiederanlaufpläne für die kritischen Prozesse vor?',
                    ],
                    [
                        'key' => 'notfallorganisation_definiert',
                        'text' => 'Ist eine Notfallorganisation mit klaren Rollen, Verantwortlichkeiten und Eskalation definiert?',
                    ],
                    [
                        'key' => 'alarmierung_erreichbarkeit',
                        'text' => 'Sind Alarmierungswege und Erreichbarkeiten festgelegt und aktuell gehalten?',
                    ],
                ],
            ],
            [
                'title' => 'Aufrechterhaltung & Verbesserung',
                'questions' => [
                    [
                        'key' => 'uebungen_durchgefuehrt',
                        'text' => 'Werden die Notfallpläne regelmäßig getestet und Übungen durchgeführt?',
                    ],
                    [
                        'key' => 'review_aktualisierung',
                        'text' => 'Werden die BCMS-Dokumente regelmäßig überprüft und bei Änderungen aktualisiert?',
                    ],
                    [
                        'key' => 'leitung_eingebunden',
                        'text' => 'Ist die Leitungsebene aktiv eingebunden und stellt die nötigen Ressourcen bereit?',
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
     * BCMS-Stufe aus erreichter Punktzahl im Verhältnis zum Maximum ableiten.
     */
    public static function stageForScore(int $score, int $max): BcmsStage
    {
        if ($max <= 0) {
            return BcmsStage::Reaktiv;
        }

        $ratio = $score / $max;

        return match (true) {
            $ratio < 0.4 => BcmsStage::Reaktiv,
            $ratio < 0.8 => BcmsStage::Aufbau,
            default => BcmsStage::Standard,
        };
    }
}
