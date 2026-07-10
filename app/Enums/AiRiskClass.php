<?php

namespace App\Enums;

/**
 * Risikoklasse eines KI-Systems nach dem risikobasierten Ansatz der
 * EU-KI-Verordnung (Verordnung (EU) 2024/1689).
 */
enum AiRiskClass: string
{
    case Prohibited = 'prohibited';
    case High = 'high';
    case Limited = 'limited';
    case Minimal = 'minimal';
    case Unclassified = 'unclassified';

    public function label(): string
    {
        return match ($this) {
            self::Prohibited => 'Verboten (Art. 5)',
            self::High => 'Hochrisiko (Annex III)',
            self::Limited => 'Begrenztes Risiko (Transparenz)',
            self::Minimal => 'Minimales Risiko',
            self::Unclassified => 'Noch nicht eingestuft',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Prohibited => 'rose',
            self::High => 'red',
            self::Limited => 'amber',
            self::Minimal => 'emerald',
            self::Unclassified => 'zinc',
        };
    }

    /**
     * Kurzer Hinweis auf die wesentliche Pflichtenlage der Klasse.
     */
    public function obligationHint(): string
    {
        return match ($this) {
            self::Prohibited => 'Einsatz unzulässig — sofort einstellen.',
            self::High => 'Risikomanagement, technische Doku, Logging/Protokollierung, menschliche Aufsicht, Konformitätsbewertung, EU-Datenbank-Registrierung, Vorfallmeldung.',
            self::Limited => 'Transparenzpflichten (Art. 50): Kennzeichnung als KI, synthetische Inhalte markieren.',
            self::Minimal => 'Keine besonderen Pflichten; KI-Kompetenz (Art. 4) empfohlen.',
            self::Unclassified => 'Einstufung ausstehend — Klassifizierung durchführen.',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()])
            ->values()
            ->toArray();
    }
}
