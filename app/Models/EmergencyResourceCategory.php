<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\EmergencyResourceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mandanteneigene, frei konfigurierbare Kategorie für Notfallressourcen
 * (Sofortmittel). Ersetzt die feste Enum-Liste durch pro Firma pflegbare
 * Kategorien; die Standardkategorien werden aus EmergencyResourceType
 * abgeleitet und bei Firmengründung angelegt.
 */
#[Fillable([
    'company_id',
    'name',
    'sort',
])]
class EmergencyResourceCategory extends Model
{
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->name;
    }

    /**
     * @return HasMany<EmergencyResource, $this>
     */
    public function emergencyResources(): HasMany
    {
        return $this->hasMany(EmergencyResource::class, 'category_id');
    }

    /**
     * Standard-Kategorienamen (aus dem Enum abgeleitet), in Anzeige-Reihenfolge.
     *
     * @return list<string>
     */
    public static function defaultNames(): array
    {
        return array_map(fn (EmergencyResourceType $type) => $type->label(), EmergencyResourceType::cases());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }
}
