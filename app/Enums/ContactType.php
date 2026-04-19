<?php

namespace App\Enums;

enum ContactType: string
{
    case Internal = 'intern';
    case External = 'extern';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Intern',
            self::External => 'Extern',
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
