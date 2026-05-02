<?php

use App\Enums\TeamRole;
use App\Http\Middleware\EnforceBillingState;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

beforeEach(function () {
    config(['features.billing' => true]);
});

function frozenTeamWithUser(): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'trial_ends_at' => now()->subDay(),
    ]);
    $team->memberships()->create([
        'user_id' => $user->id,
        'role' => TeamRole::Owner,
    ]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user, $team];
}

test('GET-Requests laufen für eingefrorene Mandanten weiter durch (Read-Only)', function () {
    [$user, $team] = frozenTeamWithUser();

    $response = $this->actingAs($user)->get(route('dashboard', ['current_team' => $team->slug]));

    expect($team->fresh()->isFrozen())->toBeTrue();
    $response->assertOk();
});

test('Middleware redirected POST/PATCH/DELETE eines eingefrorenen Mandanten auf billing.edit', function () {
    [, $team] = frozenTeamWithUser();

    $middleware = new EnforceBillingState;

    foreach (['POST', 'PATCH', 'DELETE'] as $method) {
        $request = Request::create('/whatever', $method);
        $request->setRouteResolver(function () use ($team) {
            $route = new Route(['POST'], 'whatever', fn () => null);
            $route->parameters = ['current_team' => $team->slug];

            return $route;
        });

        $response = $middleware->handle($request, fn () => response('next'));

        expect($response->isRedirect(route('billing.edit')))->toBeTrue("$method sollte umgeleitet werden");
    }
});

test('Middleware lässt GET- und HEAD-Requests durch — selbst bei eingefrorenem Mandanten', function () {
    [, $team] = frozenTeamWithUser();

    $middleware = new EnforceBillingState;

    foreach (['GET', 'HEAD'] as $method) {
        $request = Request::create('/whatever', $method);
        $request->setRouteResolver(function () use ($team) {
            $route = new Route([$method ?? 'GET'], 'whatever', fn () => null);
            $route->parameters = ['current_team' => $team->slug];

            return $route;
        });

        $response = $middleware->handle($request, fn () => response('next'));

        expect((string) $response->getContent())->toBe('next');
    }
});

test('Mandanten mit aktivem Trial werden nicht eingefroren', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'trial_ends_at' => now()->addDays(5),
    ]);
    $team->memberships()->create([
        'user_id' => $user->id,
        'role' => TeamRole::Owner,
    ]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    expect($team->isFrozen())->toBeFalse();

    $response = $this->actingAs($user)->get(route('dashboard', ['current_team' => $team->slug]));
    $response->assertOk();
});

test('Bei deaktiviertem Billing-Feature wird die Middleware komplett durchgewinkt', function () {
    config(['features.billing' => false]);
    [, $team] = frozenTeamWithUser();

    $middleware = new EnforceBillingState;
    $request = Request::create('/whatever', 'POST');
    $request->setRouteResolver(function () use ($team) {
        $route = new Route(['POST'], 'whatever', fn () => null);
        $route->parameters = ['current_team' => $team->slug];

        return $route;
    });

    $response = $middleware->handle($request, fn () => response('next'));

    expect((string) $response->getContent())->toBe('next');
});
