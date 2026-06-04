<?php

namespace App\Listeners;

use App\Models\AuthActivity;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;
use Throwable;

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

    /**
     * Records a failed login attempt for a known user.
     *
     * Each attempt produces one row plus one user lookup; this is acceptable
     * because Fortify already throttles logins (default 5/min per email+IP), so
     * the volume of failed events is bounded. No additional dedupe/throttle is
     * applied here on purpose.
     */
    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $user = $event->user instanceof User
            ? $event->user
            : ($email !== null
                ? User::where('email', $email)->first()
                : null);

        if ($user === null) {
            return;
        }

        $this->record($user, 'failed', $email);
    }

    /**
     * Writes a single auth activity row for the user's current company.
     *
     * Logging must never break authentication: any persistence failure (e.g. a
     * transient DB error) is reported and swallowed so login/logout still
     * succeed for the user.
     *
     * Note: Request::ip() returns the correct client IP only when running
     * directly. Behind a reverse proxy or load balancer (e.g. Laravel Cloud),
     * TrustProxies must be configured in bootstrap/app.php
     * ($middleware->trustProxies(...)) for the real client IP to be captured;
     * otherwise the recorded ip_address may be the proxy's address.
     */
    protected function record(User $user, string $event, ?string $email = null): void
    {
        $companyId = $user->currentCompany()?->id;

        if ($companyId === null) {
            return;
        }

        try {
            AuthActivity::create([
                'company_id' => $companyId,
                'user_id' => $user->getKey(),
                'email' => $email ?? $user->email,
                'event' => $event,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 255),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
