<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein für Push registriertes Endgerät der Notfall-App (FCM/APNs).
 *
 * Gebunden an User + Firma (Mandant); über `fcm_token` eindeutig je Gerät.
 * Grundlage für die Alarmierung und das „bitte jetzt synchronisieren"-Signal.
 * Bewusst NICHT mandantengebunden ({@see BelongsToCurrentCompany}
 * fehlt) — Registrierung/Versand laufen ohne Web-Session.
 */
#[Fillable([
    'fcm_token',
    'user_id',
    'company_id',
    'api_token_id',
    'platform',
    'app_version',
    'last_seen_at',
])]
class MobileDevice extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }
}
