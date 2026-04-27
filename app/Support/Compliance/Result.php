<?php

namespace App\Support\Compliance;

/**
 * Ergebnis eines einzelnen Compliance-Checks für einen Mandanten.
 *
 * @phpstan-type ActionLink array{label: string, route: string, params?: array<string, mixed>}
 */
class Result
{
    /**
     * @param  int  $score  0–100, tatsächlicher Erfüllungsgrad
     * @param  list<string>  $details  optionale Stichpunkte (z. B. fehlende Items)
     * @param  ActionLink|null  $action  Direktlink zur Korrektur
     */
    public function __construct(
        public readonly Status $status,
        public readonly int $score,
        public readonly string $message,
        public readonly array $details = [],
        public readonly ?array $action = null,
    ) {}

    public static function pass(string $message, array $details = [], ?array $action = null): self
    {
        return new self(Status::Pass, 100, $message, $details, $action);
    }

    public static function partial(int $score, string $message, array $details = [], ?array $action = null): self
    {
        $clamped = max(1, min(99, $score));

        return new self(Status::Partial, $clamped, $message, $details, $action);
    }

    public static function fail(string $message, array $details = [], ?array $action = null): self
    {
        return new self(Status::Fail, 0, $message, $details, $action);
    }

    public static function notApplicable(string $message): self
    {
        return new self(Status::NotApplicable, 0, $message);
    }

    public function isCounted(): bool
    {
        return $this->status !== Status::NotApplicable;
    }
}
