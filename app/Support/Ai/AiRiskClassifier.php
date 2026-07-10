<?php

namespace App\Support\Ai;

use App\Enums\AiRiskClass;

/**
 * Bildet den risikobasierten Entscheidungsbaum der EU-KI-Verordnung ab:
 * verboten (Art. 5) → hoch (Annex III / Sicherheitsbauteil Annex I) →
 * begrenzt (Transparenz Art. 50) → minimal. Reine Logik, damit sie
 * unabhängig testbar ist.
 */
class AiRiskClassifier
{
    /**
     * @param  array{prohibited?: bool, high_risk_area?: bool, safety_component?: bool, transparency?: bool}  $answers
     */
    public static function classify(array $answers): AiRiskClass
    {
        if (! empty($answers['prohibited'])) {
            return AiRiskClass::Prohibited;
        }

        if (! empty($answers['high_risk_area']) || ! empty($answers['safety_component'])) {
            return AiRiskClass::High;
        }

        if (! empty($answers['transparency'])) {
            return AiRiskClass::Limited;
        }

        return AiRiskClass::Minimal;
    }
}
