<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\BcmsStage;
use Database\Factories\MaturityAssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Ergebnis eines Reifegrad-Self-Assessments nach dem BSI-200-4-Stufenmodell.
 *
 * Hält die einzelnen Antworten, die erreichte Punktzahl und die daraus
 * abgeleitete BCMS-Stufe (Reaktiv-, Aufbau- oder Standard-BCMS) zum
 * Zeitpunkt der Auswertung fest.
 */
#[Fillable([
    'company_id',
    'answers',
    'score',
    'stage',
    'assessed_at',
    'notes',
])]
class MaturityAssessment extends Model
{
    /** @use HasFactory<MaturityAssessmentFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return 'Reifegrad-Self-Assessment';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'stage' => BcmsStage::class,
            'assessed_at' => 'date',
            'score' => 'integer',
        ];
    }
}
