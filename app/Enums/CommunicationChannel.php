<?php

namespace App\Enums;

enum CommunicationChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Phone = 'phone';
    case Messenger = 'messenger';
    case Notice = 'notice';
    case Intranet = 'intranet';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'E-Mail',
            self::Sms => 'SMS',
            self::Phone => 'Telefon',
            self::Messenger => 'Messenger (WhatsApp/Signal)',
            self::Notice => 'Aushang',
            self::Intranet => 'Intranet',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Email => 'envelope',
            self::Sms => 'device-phone-mobile',
            self::Phone => 'phone',
            self::Messenger => 'chat-bubble-left-right',
            self::Notice => 'document-text',
            self::Intranet => 'globe-alt',
        };
    }

    public function hasSubject(): bool
    {
        return match ($this) {
            self::Email, self::Intranet, self::Notice => true,
            default => false,
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
