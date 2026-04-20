<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\InsuranceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'type', 'insurer', 'policy_number', 'hotline', 'email', 'reporting_window', 'contact_name', 'notes'])]
class InsurancePolicy extends Model
{
    use BelongsToCurrentCompany, HasUuids, LogsAudit;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InsuranceType::class,
        ];
    }
}
