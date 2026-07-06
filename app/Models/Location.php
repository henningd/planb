<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Jobs\GeocodeLocation;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'company_id',
    'name',
    'street',
    'postal_code',
    'city',
    'country',
    'lat',
    'lng',
    'is_headquarters',
    'phone',
    'notes',
    'sort',
])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    /**
     * Adressfelder, deren Änderung ein erneutes Geocoding erfordert
     * (siehe {@see GeocodeLocation}).
     *
     * @var list<string>
     */
    public const ADDRESS_FIELDS = ['street', 'postal_code', 'city', 'country'];

    public function auditLabel(): string
    {
        return $this->name;
    }

    /**
     * Freitext-Adresse für das Geocoding (Nominatim `q=`-Parameter).
     */
    public function geocodingQuery(): string
    {
        return collect([
            $this->street,
            trim(($this->postal_code ?? '').' '.($this->city ?? '')),
            $this->country,
        ])->filter()->implode(', ');
    }

    public function hasCoordinates(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    /**
     * @return BelongsToMany<Contract, $this>
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class)->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'is_headquarters' => 'boolean',
            'sort' => 'integer',
        ];
    }
}
