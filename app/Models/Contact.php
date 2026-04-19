<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Enums\ContactType;
use App\Observers\ContactObserver;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'name', 'role', 'phone', 'email', 'type', 'is_primary'])]
#[ObservedBy([ContactObserver::class])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use BelongsToCurrentCompany, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
            'is_primary' => 'boolean',
        ];
    }
}
