<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\CommunicationAudience;
use App\Enums\CommunicationChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'scenario_id', 'name', 'audience', 'channel', 'subject', 'body', 'fallback', 'sort'])]
class CommunicationTemplate extends Model
{
    use BelongsToCurrentCompany, HasUuids, LogsAudit;

    /**
     * @return BelongsTo<Scenario, $this>
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => CommunicationAudience::class,
            'channel' => CommunicationChannel::class,
        ];
    }
}
