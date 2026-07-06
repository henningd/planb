<?php

namespace App\Jobs;

use App\Services\Chat\ChatWebhookResult;
use App\Services\Chat\ChatWebhookSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Postet eine Alarm-Karte (Notfall-Start, Eskalation, Entwarnung) asynchron
 * an die konfigurierten Chat-Webhooks der Firma, damit das Auslösen eines
 * Alarms nie auf Slack-/Teams-HTTP-Calls warten muss (Muster
 * {@see SendCompanyPush}). Strikt best-effort: Fehler werden nur geloggt,
 * der Job schlägt nie fehl ({@see ChatWebhookSender} wirft nicht).
 */
class SendAlarmChatPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly ?string $slackWebhookUrl,
        private readonly ?string $teamsWebhookUrl,
        private readonly string $title,
        private readonly string $body,
    ) {}

    public function handle(ChatWebhookSender $sender): void
    {
        if (filled($this->slackWebhookUrl)) {
            $this->logFailure('Slack', $sender->sendSlack($this->slackWebhookUrl, $this->title, $this->body));
        }

        if (filled($this->teamsWebhookUrl)) {
            $this->logFailure('Teams', $sender->sendTeams($this->teamsWebhookUrl, $this->title, $this->body));
        }
    }

    private function logFailure(string $channel, ChatWebhookResult $result): void
    {
        if (! $result->success) {
            Log::warning("Alarm-Chat-Post an {$channel} fehlgeschlagen: ".($result->errorMessage ?? 'unbekannter Fehler'));
        }
    }
}
