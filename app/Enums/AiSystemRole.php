<?php

namespace App\Enums;

/**
 * Rolle des Unternehmens in Bezug auf ein KI-System nach der EU-KI-Verordnung
 * — die Rolle bestimmt, welche Pflichten gelten.
 */
enum AiSystemRole: string
{
    case Provider = 'provider';
    case Deployer = 'deployer';
    case Importer = 'importer';
    case Distributor = 'distributor';

    public function label(): string
    {
        return match ($this) {
            self::Provider => 'Anbieter',
            self::Deployer => 'Betreiber',
            self::Importer => 'Importeur',
            self::Distributor => 'Händler',
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
