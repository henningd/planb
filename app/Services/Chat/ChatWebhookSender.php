<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Postet eine Krisen-Nachricht an einen Slack-Incoming-Webhook oder einen
 * Microsoft-Teams-Channel-Webhook. Beide Anbieter erwarten eine
 * öffentliche HTTPS-URL, die der Mandant in den Settings hinterlegt.
 *
 * Slack: Block-Kit-Payload mit Header + Section-Block.
 * Teams: Legacy MessageCard (gleiche URL-Form, breit kompatibel).
 */
class ChatWebhookSender
{
    public function sendSlack(string $webhookUrl, string $title, string $body): ChatWebhookResult
    {
        $payload = [
            'blocks' => [
                ['type' => 'header', 'text' => ['type' => 'plain_text', 'text' => mb_substr($title, 0, 150)]],
                ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => mb_substr($body, 0, 2900)]],
            ],
        ];

        return $this->post($webhookUrl, $payload);
    }

    public function sendTeams(string $webhookUrl, string $title, string $body): ChatWebhookResult
    {
        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'summary' => mb_substr($title, 0, 150),
            'themeColor' => 'D9534F',
            'title' => mb_substr($title, 0, 150),
            'text' => str_replace("\n", "  \n", mb_substr($body, 0, 4000)),
        ];

        return $this->post($webhookUrl, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $url, array $payload): ChatWebhookResult
    {
        try {
            $response = Http::asJson()->timeout(8)->post($url, $payload);
            if ($response->successful()) {
                return ChatWebhookResult::ok($response->status());
            }

            return ChatWebhookResult::fail(
                'HTTP '.$response->status().': '.mb_substr((string) $response->body(), 0, 250),
                $response->status(),
            );
        } catch (Throwable $e) {
            return ChatWebhookResult::fail($e->getMessage());
        }
    }
}
