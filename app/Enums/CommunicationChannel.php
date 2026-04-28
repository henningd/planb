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
    case Slack = 'slack';
    case Teams = 'teams';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'E-Mail',
            self::Sms => 'SMS',
            self::Phone => 'Telefon',
            self::Messenger => 'Messenger (WhatsApp/Signal)',
            self::Notice => 'Aushang',
            self::Intranet => 'Intranet',
            self::Slack => 'Slack',
            self::Teams => 'Microsoft Teams',
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
            self::Slack => 'hashtag',
            self::Teams => 'hashtag',
        };
    }

    public function hasSubject(): bool
    {
        return match ($this) {
            self::Email, self::Intranet, self::Notice, self::Slack, self::Teams => true,
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
