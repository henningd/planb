<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records authentication events (login, logout, failed login) per company so
 * admins can see who signed in when. Pruned per tenant by app:cleanup-audit-log.
 */
#[Fillable(['company_id', 'user_id', 'email', 'event', 'ip_address', 'user_agent'])]
class AuthActivity extends Model
{
    use BelongsToCurrentCompany, HasUuids;

    protected $table = 'auth_activity_log';

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
