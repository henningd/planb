<?php

namespace App\Concerns;

use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adds multi-tenant scoping by company_id:
 *  - Queries are automatically filtered to the authenticated user's company.
 *  - New records receive company_id automatically when not provided.
 *
 * Use `Model::withoutGlobalScope(CurrentCompanyScope::class)` to bypass
 * the filter in console, seeders, or cross-tenant admin contexts.
 */
trait BelongsToCurrentCompany
{
    public static function bootBelongsToCurrentCompany(): void
    {
        static::addGlobalScope(new CurrentCompanyScope);

        static::creating(function ($model) {
            if (empty($model->company_id) && ($companyId = CurrentCompany::id()) !== null) {
                $model->company_id = $companyId;
            }
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
