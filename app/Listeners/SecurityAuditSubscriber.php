<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\Audit\AccountAudit;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

/**
 * Records two-factor authentication changes into the shared audit log, scoped
 * to the user's current company. Mirrors AuthActivitySubscriber: the handle*
 * methods are auto-registered via Laravel's listener discovery (app/Listeners),
 * keyed by the type-hinted Fortify event.
 */
class SecurityAuditSubscriber
{
    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->record($event->user, 'security.2fa_enabled');
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->record($event->user, 'security.2fa_disabled');
    }

    protected function record(mixed $user, string $action): void
    {
        if (! $user instanceof User) {
            return;
        }

        AccountAudit::record(
            action: $action,
            entityType: 'User',
            entityId: $user->getKey(),
            entityLabel: $user->name,
            companyId: $user->currentCompany()?->id,
            actorId: $user->getKey(),
        );
    }
}
