<?php

namespace App\Support\Compliance;

enum Status: string
{
    case Pass = 'pass';
    case Partial = 'partial';
    case Fail = 'fail';
    case NotApplicable = 'na';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Erfüllt',
            self::Partial => 'Teilweise erfüllt',
            self::Fail => 'Nicht erfüllt',
            self::NotApplicable => 'Nicht relevant',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pass => 'emerald',
            self::Partial => 'amber',
            self::Fail => 'rose',
            self::NotApplicable => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pass => 'check-circle',
            self::Partial => 'exclamation-triangle',
            self::Fail => 'x-circle',
            self::NotApplicable => 'minus-circle',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::Pass => '#10b981',
            self::Partial => '#f59e0b',
            self::Fail => '#f43f5e',
            self::NotApplicable => '#a3a3a3',
        };
    }
}
