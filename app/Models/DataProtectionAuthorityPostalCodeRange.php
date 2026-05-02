<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'data_protection_authority_id',
    'plz_from',
    'plz_to',
    'notes',
])]
class DataProtectionAuthorityPostalCodeRange extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<DataProtectionAuthority, $this>
     */
    public function authority(): BelongsTo
    {
        return $this->belongsTo(DataProtectionAuthority::class, 'data_protection_authority_id');
    }
}
