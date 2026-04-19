<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Database\Factories\EmergencyLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'name', 'description', 'reaction', 'sort'])]
class EmergencyLevel extends Model
{
    /** @use HasFactory<EmergencyLevelFactory> */
    use BelongsToCurrentCompany, HasFactory;
}
