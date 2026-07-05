<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Database\Factories\AppNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Firmenweiter Benachrichtigungs-Feed (Notfall gemeldet/beendet …), den die
 * Notfall-App als „Benachrichtigungen"-Verlauf anzeigt. Bewusst NICHT
 * mandantengebunden ({@see BelongsToCurrentCompany} fehlt) —
 * Erzeugung und Auslieferung laufen über den API-Kontext ohne Web-Session; die
 * Firmenzugehörigkeit wird explizit über `company_id` geführt. Der Gelesen-Status
 * liegt lokal je Gerät, nicht hier.
 */
#[Fillable([
    'company_id',
    'type',
    'title',
    'body',
    'triggered_by_name',
    'severity',
    'scenario_run_id',
])]
class AppNotification extends Model
{
    /** @use HasFactory<AppNotificationFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
