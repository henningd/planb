<?php

namespace App\Enums;

enum LegalForm: string
{
    case Sonstiges = 'sonstiges';
    case Einzelunternehmen = 'einzelunternehmen';
    case EinzelKaufmann = 'e_k';
    case GbR = 'gbr';
    case OHG = 'ohg';
    case KG = 'kg';
    case GmbH = 'gmbh';
    case UG = 'ug';
    case AG = 'ag';
    case Genossenschaft = 'eg';
    case Verein = 'verein';
    case Stiftung = 'stiftung';
    case AoeR = 'aoer';
    case KdoeR = 'kdoer';
    case StiftungOeR = 'stiftung_oer';

    public function label(): string
    {
        return match ($this) {
            self::Sonstiges => 'Sonstige',
            self::Einzelunternehmen => 'Einzelunternehmen',
            self::EinzelKaufmann => 'Eingetragener Kaufmann (e.K.)',
            self::GbR => 'GbR',
            self::OHG => 'OHG',
            self::KG => 'KG',
            self::GmbH => 'GmbH',
            self::UG => 'UG (haftungsbeschränkt)',
            self::AG => 'AG',
            self::Genossenschaft => 'Genossenschaft (eG)',
            self::Verein => 'Verein (e.V.)',
            self::Stiftung => 'Stiftung',
            self::AoeR => 'Anstalt des öffentlichen Rechts (AöR)',
            self::KdoeR => 'Körperschaft des öffentlichen Rechts (KdöR)',
            self::StiftungOeR => 'Stiftung des öffentlichen Rechts',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        $collator = class_exists(\Collator::class) ? new \Collator('de_DE') : null;

        return collect(self::cases())
            ->map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()])
            ->sort(fn (array $a, array $b) => $collator
                ? $collator->compare($a['label'], $b['label'])
                : strcmp($a['label'], $b['label']))
            ->values()
            ->toArray();
    }
}
