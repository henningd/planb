<?php

use App\Models\Company;
use App\Models\HandbookShare;
use App\Models\Location;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function aushangSetup(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $location = Location::factory()->for($company)->create(['name' => 'Rathaus']);

    return [$user->fresh(), $company, $location];
}

test('the aushang page renders the offline payload the apps expect', function () {
    [$user, , $location] = aushangSetup();

    $response = $this->actingAs($user)
        ->get(route('locations.aushang', ['current_team' => $user->currentTeam->slug, 'location' => $location->id]))
        ->assertOk()
        ->assertSee('IM NOTFALL')
        ->assertSee('Rathaus');

    $payload = json_decode($response->viewData('payloadJson'), true);
    expect($payload['planb'])->toBe('aushang')
        ->and($payload['location'])->toBe($location->id)
        ->and($payload)->not->toHaveKey('scenario');
});

test('a scenario can be pinned via query parameter', function () {
    [$user, $company, $location] = aushangSetup();
    $scenario = Scenario::factory()->for($company)->create(['name' => 'Stromausfall']);

    $response = $this->actingAs($user)
        ->get(route('locations.aushang', [
            'current_team' => $user->currentTeam->slug,
            'location' => $location->id,
            'scenario' => $scenario->id,
        ]))
        ->assertOk()
        ->assertSee('Stromausfall');

    $payload = json_decode($response->viewData('payloadJson'), true);
    expect($payload['scenario'])->toBe($scenario->id);
});

test('an active handbook share becomes the plain-text fallback url', function () {
    [$user, $company, $location] = aushangSetup();
    $share = HandbookShare::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $user->id,
        'token' => HandbookShare::generateToken(),
        'label' => 'Aushang-Fallback',
        'expires_at' => now()->addYear(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('locations.aushang', ['current_team' => $user->currentTeam->slug, 'location' => $location->id]))
        ->assertOk();

    $payload = json_decode($response->viewData('payloadJson'), true);
    expect($payload['url'])->toContain('/shared-handbook/'.$share->token);
});

test('a foreign location is never rendered', function () {
    [$user] = aushangSetup();

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $foreign = Location::factory()->for($otherCompany)->create();

    $this->actingAs($user)
        ->get(route('locations.aushang', ['current_team' => $user->currentTeam->slug, 'location' => $foreign->id]))
        ->assertNotFound();
});
