<?php

namespace App\Jobs;

use App\Models\MobileDevice;
use App\Support\Push\PushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Stellt einen Push je Firma asynchron zu, damit das Auslösen eines Notfalls
 * nicht auf N sequentielle FCM-HTTP-Calls warten muss. Löst die Geräte-Tokens
 * erst im Job auf und räumt von FCM als ungültig gemeldete Tokens auf.
 */
class SendCompanyPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $data  Data-Payload (z. B. ['type' => 'sync']).
     * @param  int|null  $excludeUserId  Geräte dieses Users ausschließen (z. B. der Auslöser
     *                                   eines sichtbaren Alarms). Nur für sichtbare Pushes.
     */
    public function __construct(
        private readonly string $companyId,
        private readonly array $data,
        private readonly ?string $title = null,
        private readonly ?string $body = null,
        private readonly ?int $excludeUserId = null,
    ) {}

    public function handle(PushSender $sender): void
    {
        $tokens = MobileDevice::query()
            ->where('company_id', $this->companyId)
            ->when($this->excludeUserId !== null, fn ($query) => $query->where('user_id', '!=', $this->excludeUserId))
            ->pluck('fcm_token')
            ->all();

        if ($tokens === []) {
            return;
        }

        $dead = $sender->send($tokens, $this->data, $this->title, $this->body);

        if ($dead !== []) {
            MobileDevice::query()->whereIn('fcm_token', $dead)->delete();
        }
    }
}
