<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Enums\SystemCategory;
use Database\Factories\SystemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['company_id', 'name', 'description', 'category', 'system_priority_id', 'rto_minutes', 'rpo_minutes'])]
class System extends Model
{
    /** @use HasFactory<SystemFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids;

    /**
     * @return BelongsTo<SystemPriority, $this>
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(SystemPriority::class, 'system_priority_id');
    }

    /**
     * @return BelongsToMany<ServiceProvider, $this>
     */
    public function serviceProviders(): BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => SystemCategory::class,
            'rto_minutes' => 'integer',
            'rpo_minutes' => 'integer',
        ];
    }
}
