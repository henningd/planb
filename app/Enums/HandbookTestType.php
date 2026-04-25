<?php

namespace App\Enums;

enum HandbookTestType: string
{
    case ContactCheck = 'contact_check';
    case Tabletop = 'tabletop';
    case BackupRestore = 'backup_restore';
    case Communication = 'communication';
    case Recovery = 'recovery';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ContactCheck => 'Kontaktlisten-Check',
            self::Tabletop => 'Tischübung (Tabletop)',
            self::BackupRestore => 'Backup-Restore-Test',
            self::Communication => 'Kommunikationstest',
            self::Recovery => 'Wiederanlauf-Test',
            self::Other => 'Sonstiger Test',
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
