<?php

namespace App\Listeners;

use App\Models\AuthActivity;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

/**
 * Persists authentication events (login, logout, failed login) into
 * auth_activity_log, scoped to the user's current company (tenant). Events that
 * cannot be attributed to a company are skipped, since the per-tenant admin view
 * could not display them anyway.
 *
 * The handle* methods are auto-registered via Laravel's listener discovery
 * (app/Listeners), keyed by the type-hinted event in their first parameter.
 */
class AuthActivitySubscriber
{
    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->record($event->user, 'login');
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->record($event->user, 'logout');
        }
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $user = $event->user instanceof User
            ? $event->user
            : ($email !== null
                ? User::withoutGlobalScope(CurrentCompanyScope::class)->where('email', $email)->first()
                : null);

        if ($user === null) {
            return;
        }

        $this->record($user, 'failed', $email);
    }

    protected function record(User $user, string $event, ?string $email = null): void
    {
        $companyId = $user->currentCompany()?->id;

        if ($companyId === null) {
            return;
        }

        AuthActivity::create([
            'company_id' => $companyId,
            'user_id' => $user->getKey(),
            'email' => $email ?? $user->email,
            'event' => $event,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
        ]);
    }
}
