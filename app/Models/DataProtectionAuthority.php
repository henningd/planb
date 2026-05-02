<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'key',
    'name',
    'short_name',
    'state',
    'street',
    'postal_code',
    'city',
    'phone',
    'email',
    'website',
    'breach_notification_url',
    'notes',
    'sort',
])]
class DataProtectionAuthority extends Model
{
    use HasUuids;

    /**
     * @return HasMany<DataProtectionAuthorityPostalCodeRange, $this>
     */
    public function postalCodeRanges(): HasMany
    {
        return $this->hasMany(DataProtectionAuthorityPostalCodeRange::class);
    }
}
