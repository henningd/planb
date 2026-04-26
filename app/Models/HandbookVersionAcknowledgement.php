<?php

namespace App\Models;

use Database\Factories\HandbookVersionAcknowledgementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'handbook_version_id',
    'employee_id',
    'acknowledged_at',
    'notes',
])]
class HandbookVersionAcknowledgement extends Model
{
    /** @use HasFactory<HandbookVersionAcknowledgementFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<HandbookVersion, $this>
     */
    public function handbookVersion(): BelongsTo
    {
        return $this->belongsTo(HandbookVersion::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }
}
