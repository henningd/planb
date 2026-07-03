<?php

use App\Models\Company;
use App\Models\Location;
use App\Models\MobileAccessCode;
use App\Models\Scenario;
use App\Models\ScenarioStep;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: string}
 */
function syncSession(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $user = $user->fresh();

    $issued = MobileAccessCode::issue($user, $company);
    $token = test()->postJson('/api/mobile/login', [
        'email' => $user->email,
        'code' => $issued['code'],
    ])->json('token');

    return [$user, $company, $token];
}

test('the sync bundle returns the mandant data', function () {
    [$user, $company, $token] = syncSession();

    $scenario = Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);
    ScenarioStep::factory()->for($scenario)->create(['sort' => 1, 'title' => 'Notstrom prüfen']);
    Location::factory()->for($company)->create(['name' => 'Hauptsitz', 'is_headquarters' => true]);
    ServiceProvider::factory()->for($company)->create(['name' => 'IT-Dienstleister', 'hotline' => '0800-123']);

    test()->withToken($token)->getJson('/api/mobile/sync')
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'synced_at', 'handbook', 'company' => ['id', 'name'],
            'locations', 'crisis_roles', 'service_providers',
            'emergency_resources', 'recovery_order', 'scenarios', 'aushang_codes',
        ]])
        ->assertJsonPath('data.company.id', $company->id)
        ->assertJsonFragment(['title' => 'Stromausfall'])
        ->assertJsonFragment(['name' => 'Hauptsitz', 'is_headquarters' => true])
        ->assertJsonFragment(['name' => 'IT-Dienstleister', 'emergency_phone' => '0800-123'])
        ->assertJsonFragment(['scenario_id' => $scenario->id, 'location_id' => null]);
});

test('the sync bundle is strictly scoped to the token mandant', function () {
    [$user, $company, $token] = syncSession();

    $other = Company::factory()->create();
    Scenario::factory()->for($other)->create(['name' => 'FremdSzenario']);

    test()->withToken($token)->getJson('/api/mobile/sync')
        ->assertOk()
        ->assertJsonMissing(['title' => 'FremdSzenario']);
});

test('sync requires a bearer token', function () {
    test()->getJson('/api/mobile/sync')->assertStatus(401);
});

test('the handbook pdf endpoint rejects a foreign or unknown version', function () {
    [$user, $company, $token] = syncSession();

    test()->withToken($token)
        ->get('/api/mobile/handbook/'.Str::uuid()->toString().'/pdf')
        ->assertNotFound();
});
