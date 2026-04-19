<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Database\Factories\SystemPriorityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'description', 'sort'])]
class SystemPriority extends Model
{
    /** @use HasFactory<SystemPriorityFactory> */
    use BelongsToCurrentCompany, HasFactory;

    /**
     * @return HasMany<System, $this>
     */
    public function systems(): HasMany
    {
        return $this->hasMany(System::class);
    }
}
