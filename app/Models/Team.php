<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\TeamRole;
use App\Support\Audit\AccountAudit;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

#[Fillable(['name', 'slug', 'is_personal'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use Billable, GeneratesUniqueTeamSlugs, HasFactory, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = static::generateUniqueTeamSlug($team->name);
            }
        });

        static::updating(function (Team $team) {
            if ($team->isDirty('name')) {
                $team->slug = static::generateUniqueTeamSlug($team->name, $team->id);
            }
        });

        static::updated(function (Team $team) {
            if ($team->wasChanged('name')) {
                AccountAudit::record(
                    action: 'updated',
                    entityType: 'Team',
                    entityId: $team->id,
                    entityLabel: $team->name,
                    companyId: $team->company?->id,
                    changes: ['name' => ['old' => $team->getOriginal('name'), 'new' => $team->name]],
                );
            }
        });
    }

    /**
     * Get the team owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get the company profile for this team.
     *
     * @return HasOne<Company, $this>
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<Model, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role', 'disabled_at'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * E-Mail-Adresse, die Stripe als Kunden-Kontakt nutzt — wir nehmen den
     * Team-Owner.
     */
    public function stripeEmail(): ?string
    {
        return $this->owner()?->email;
    }

    /**
     * Anzeigename des Stripe-Kunden — Mandanten-Name.
     */
    public function stripeName(): ?string
    {
        return $this->name;
    }

    /**
     * Liefert den aktiven Plan-Schlüssel (starter/advanced/enterprise) oder
     * null, wenn kein Abo aktiv ist (auch nicht im Trial).
     */
    public function activePlanKey(): ?string
    {
        $subscription = $this->subscription('default');

        if ($subscription && $subscription->valid()) {
            $priceId = $subscription->stripe_price;

            foreach (config('billing.plans') as $key => $plan) {
                if ($plan['monthly_price_id'] === $priceId || $plan['yearly_price_id'] === $priceId) {
                    return $key;
                }
            }
        }

        // Ohne Stripe-Abo: Generic-Trial (Trial ohne Kreditkarte) zählt als
        // Trial-Plan, damit Feature-Gates (`onPlan('advanced')`) während
        // des Test-Zeitraums durchlassen.
        if ($this->onGenericTrial()) {
            return config('billing.trial_plan');
        }

        return null;
    }

    /**
     * Prüft, ob der Mandant mindestens den angegebenen Plan-Tier hat
     * (Starter < Advanced < Enterprise). Nützlich für Feature-Gates.
     */
    public function onPlan(string $minimum): bool
    {
        $tiers = ['starter' => 1, 'advanced' => 2, 'enterprise' => 3];
        $current = $this->activePlanKey();

        if ($current === null) {
            return false;
        }

        return ($tiers[$current] ?? 0) >= ($tiers[$minimum] ?? 0);
    }

    /**
     * Mandant ist eingefroren — Trial abgelaufen, kein gültiges Abo.
     */
    public function isFrozen(): bool
    {
        if (! config('billing.freeze_after_trial')) {
            return false;
        }

        if ($this->subscribed('default')) {
            return false;
        }

        // Kein Abo, kein Trial → eingefroren, sobald jemals ein Trial lief.
        if ($this->trial_ends_at !== null && $this->trial_ends_at->isPast()) {
            return true;
        }

        return false;
    }
}
