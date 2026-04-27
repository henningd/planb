<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'company_id',
    'snapshot_date',
    'score',
    'breakdown',
])]
class ComplianceScoreSnapshot extends Model
{
    use BelongsToCurrentCompany, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'breakdown' => 'array',
            'score' => 'integer',
        ];
    }
}
