<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * SMS-Versand über seven.io (https://gateway.seven.io/api/sms).
 * Auth via X-Api-Key Header, Antworten werden als JSON angefordert.
 */
class SevenIoGateway implements SmsGatewayContract
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $defaultSender,
        private readonly string $endpoint = 'https://gateway.seven.io/api/sms',
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function send(string $to, string $text, ?string $from = null): SmsResult
    {
        if (! $this->isConfigured()) {
            return SmsResult::fail($to, 'SMS-Gateway ist nicht konfiguriert (SEVENIO_API_KEY).');
        }

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'SentWith' => 'PlanB',
                ])
                ->timeout(15)
                ->post($this->endpoint, array_filter([
                    'to' => $to,
                    'text' => $text,
                    'from' => $from ?? $this->defaultSender,
                    'json' => 1,
                ], fn ($v) => $v !== null && $v !== ''));
        } catch (Throwable $e) {
            return SmsResult::fail($to, 'HTTP-Fehler: '.$e->getMessage());
        }

        if (! $response->ok()) {
            return SmsResult::fail($to, "Provider-HTTP-Status {$response->status()}");
        }

        $body = $response->json();
        // seven.io: success.code "100" = Akzeptiert; alles andere ist Fehler.
        $code = (string) ($body['success'] ?? $body['code'] ?? '');
        $messages = $body['messages'][0] ?? null;
        $messageId = $messages['id'] ?? null;

        if ($code === '100') {
            return SmsResult::ok($to, $messageId !== null ? (string) $messageId : null);
        }

        return SmsResult::fail($to, "Provider-Antwort: code={$code}");
    }
}
