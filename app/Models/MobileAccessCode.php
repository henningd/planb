<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Einmaliger Kopplungs-Code, mit dem ein Mitarbeiter sein Smartphone mit der
 * PlanB-Notfall-App verbindet (Onboarding). Der Code ist an einen konkreten
 * User **und** dessen Firma (Mandant) gebunden — dadurch spricht das Gerät nach
 * dem Einlösen automatisch den richtigen Kunden an.
 *
 * Bewusst NICHT mandantengebunden ({@see BelongsToCurrentCompany}
 * fehlt absichtlich): Das Einlösen über `POST /api/mobile/login` passiert ohne
 * eingeloggte Web-Session, muss den Code also mandantenübergreifend finden.
 * Beim Einlösen wird ein {@see ApiToken} (Scope `mobile`) für die Firma
 * ausgestellt und der Code als verbraucht markiert.
 */
#[Fillable([
    'user_id',
    'company_id',
    'email',
    'code_hash',
    'expires_at',
    'consumed_at',
    'api_token_id',
    'created_by_user_id',
    'revoked_at',
])]
class MobileAccessCode extends Model
{
    use HasUuids;

    /** Gültigkeitsdauer eines frisch erzeugten Codes in Minuten (Einzel-Flow, Self-Service). */
    public const TTL_MINUTES = 60;

    /**
     * Gültigkeitsdauer für den Massen-Rollout in Tagen: Die Codes werden als
     * gedruckte Blätter verteilt und müssen deshalb deutlich länger gültig
     * sein als im Self-Service-Flow. Einmal-Verwendung und Widerrufbarkeit
     * bleiben unverändert.
     */
    public const ROLLOUT_TTL_DAYS = 14;

    /** Alphabet ohne verwechselbare Zeichen (kein O/0, I/1). */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 8;

    /**
     * Erzeugt einen neuen Kopplungs-Code für den User in seiner Firma,
     * persistiert dessen Hash und gibt den Klartext-Code einmalig zurück.
     *
     * `$expiresAt` erlaubt eine abweichende Gültigkeit (Massen-Rollout,
     * {@see self::ROLLOUT_TTL_DAYS}); ohne Angabe gilt der kurze
     * Self-Service-Standard ({@see self::TTL_MINUTES}).
     *
     * @return array{code: string, model: MobileAccessCode}
     */
    public static function issue(User $user, Company $company, ?int $createdByUserId = null, ?Carbon $expiresAt = null): array
    {
        $plain = self::generateCode();

        $model = self::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'email' => $user->email,
            'code_hash' => hash('sha256', $plain),
            'expires_at' => $expiresAt ?? Carbon::now()->addMinutes(self::TTL_MINUTES),
            'created_by_user_id' => $createdByUserId ?? $user->id,
        ]);

        return ['code' => $plain, 'model' => $model];
    }

    /**
     * Findet einen einlösbaren Code (passende E-Mail, nicht verbraucht, nicht
     * widerrufen, nicht abgelaufen). Vergleich case-insensitiv / normalisiert.
     */
    public static function findRedeemable(string $email, string $code): ?self
    {
        $normalized = self::normalize($code);
        if ($normalized === '') {
            return null;
        }

        return self::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->where('code_hash', hash('sha256', $normalized))
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Normalisiert eine Nutzereingabe auf das Code-Format (Großbuchstaben,
     * ohne Trenn-/Leerzeichen).
     */
    public static function normalize(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    /**
     * Markiert den Code als eingelöst und verknüpft den ausgestellten Token.
     */
    public function consume(ApiToken $token): void
    {
        $this->forceFill([
            'consumed_at' => Carbon::now(),
            'api_token_id' => $token->id,
        ])->save();
    }

    public function isActive(): bool
    {
        return $this->consumed_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    /**
     * Status-Kürzel für die Anzeige: active | consumed | revoked | expired.
     */
    public function status(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'revoked',
            $this->consumed_at !== null => 'consumed',
            $this->expires_at->isPast() => 'expired',
            default => 'active',
        };
    }

    private static function generateCode(): string
    {
        $code = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<ApiToken, $this>
     */
    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class, 'api_token_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
