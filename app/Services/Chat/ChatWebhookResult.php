<?php

namespace App\Services\Chat;

class ChatWebhookResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $errorMessage = null,
        public readonly ?int $statusCode = null,
    ) {}

    public static function ok(int $statusCode): self
    {
        return new self(true, null, $statusCode);
    }

    public static function fail(string $message, ?int $statusCode = null): self
    {
        return new self(false, $message, $statusCode);
    }
}
