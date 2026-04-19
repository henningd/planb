<?php

namespace App\Models;

use App\Enums\ReportingObligation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['incident_report_id', 'obligation', 'reported_at', 'note'])]
class IncidentReportObligation extends Model
{
    use HasUuids;

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
            'obligation' => ReportingObligation::class,
            'reported_at' => 'datetime',
        ];
    }
}
