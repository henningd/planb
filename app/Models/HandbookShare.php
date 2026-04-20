<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['company_id', 'created_by_user_id', 'token', 'label', 'expires_at', 'revoked_at', 'last_accessed_at', 'access_count'])]
class HandbookShare extends Model
{
    use BelongsToCurrentCompany, HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    public function status(): string
    {
        if ($this->revoked_at !== null) {
            return 'revoked';
        }

        return $this->expires_at->isFuture() ? 'active' : 'expired';
    }

    public static function generateToken(): string
    {
        do {
            $token = bin2hex(random_bytes(24));
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'access_count' => 'integer',
        ];
    }

    /**
     * Shortcut used by the share management view.
     */
    public function defaultExpiry(): Carbon
    {
        return Carbon::now()->addDays(14);
    }
}
