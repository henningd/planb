<?php

namespace App\Models;

use App\Enums\ReportingObligation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['incident_report_id', 'obligation', 'reported_at', 'deadline_alerted_at', 'note'])]
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
     * Limit the query to obligations that have not been reported yet.
     *
     * @param  Builder<IncidentReportObligation>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('reported_at');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'obligation' => ReportingObligation::class,
            'reported_at' => 'datetime',
            'deadline_alerted_at' => 'datetime',
        ];
    }
}
