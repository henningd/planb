<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'company_id',
    'name',
    'token_hash',
    'prefix',
    'scopes',
    'created_by_user_id',
    'last_used_at',
    'revoked_at',
])]
class ApiToken extends Model
{
    use BelongsToCurrentCompany, HasUuids, LogsAudit;

    /**
     * Erzeugt einen neuen Token, persistiert dessen Hash und gibt das Klartext-
     * Token einmalig zurück. Der Klartext wird nie wieder geloggt oder
     * angezeigt.
     *
     * @param  array<int, string>  $scopes
     * @return array{token: string, model: ApiToken}
     */
    public static function issue(string $companyId, string $name, array $scopes, ?int $userId): array
    {
        $plain = 'planb_'.Str::random(40);
        $prefix = substr($plain, 0, 12);

        $model = self::create([
            'company_id' => $companyId,
            'name' => $name,
            'token_hash' => hash('sha256', $plain),
            'prefix' => $prefix,
            'scopes' => $scopes,
            'created_by_user_id' => $userId,
        ]);

        return ['token' => $plain, 'model' => $model];
    }

    public static function findActiveByPlainToken(string $plain): ?self
    {
        return self::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('token_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->first();
    }

    public function hasScope(string $scope): bool
    {
        $scopes = $this->scopes ?? [];

        return in_array('*', $scopes, true) || in_array($scope, $scopes, true);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function auditLabel(): string
    {
        return $this->name.' ('.$this->prefix.'…)';
    }

    /**
     * @return array<int, string>
     */
    public function auditExcluded(): array
    {
        return ['created_at', 'updated_at', 'id', 'company_id', 'token_hash', 'last_used_at'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
