<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'trigger', 'is_active', 'sort'])]
class GlobalScenario extends Model
{
    use HasUuids;

    /**
     * @return HasMany<GlobalScenarioStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(GlobalScenarioStep::class)->orderBy('sort');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
