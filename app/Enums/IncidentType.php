<?php

namespace App\Enums;

enum IncidentType: string
{
    case DataBreach = 'data_breach';
    case CyberAttack = 'cyber_attack';
    case Outage = 'outage';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DataBreach => 'Datenpanne / Datenleck',
            self::CyberAttack => 'Cyberangriff / Ransomware',
            self::Outage => 'Systemausfall',
            self::Other => 'Sonstiges',
        };
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()])
            ->values()
            ->toArray();
    }
}
