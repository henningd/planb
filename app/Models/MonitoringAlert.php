<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'api_token_id',
    'system_id',
    'incident_report_id',
    'source',
    'idempotency_key',
    'severity',
    'status',
    'host',
    'subject',
    'payload',
    'handling',
    'note',
    'received_at',
])]
class MonitoringAlert extends Model
{
    use BelongsToCurrentCompany, HasUuids;

    /**
     * @return BelongsTo<ApiToken, $this>
     */
    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class);
    }

    /**
     * @return BelongsTo<System, $this>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    /**
     * @return BelongsTo<IncidentReport, $this>
     */
    public function incidentReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
        ];
    }
}
