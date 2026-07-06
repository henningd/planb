<?php

use App\Jobs\GeocodeLocation;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use App\Services\Geocoding\NominatimGeocoder;
use App\Support\Mobile\MobileSyncBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function geocodeUser(): User
{
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    return $user->fresh();
}

/**
 * @return array<string, mixed> Nominatim-Erfolgsantwort (jsonv2, limit=1).
 */
function nominatimHit(float $lat = 52.5170365, float $lon = 13.3888599): array
{
    return [['lat' => (string) $lat, 'lon' => (string) $lon, 'display_name' => 'Berlin']];
}

test('the geocode job sets lat/lng on a nominatim hit', function () {
    Http::fake([NominatimGeocoder::ENDPOINT.'*' => Http::response(nominatimHit())]);

    $location = Location::factory()->create();

    (new GeocodeLocation($location->id))->handle(new NominatimGeocoder);

    $location->refresh();
    expect($location->lat)->toBe(52.5170365)
        ->and($location->lng)->toBe(13.3888599);

    Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', NominatimGeocoder::USER_AGENT)
        && str_contains($request->url(), 'format=jsonv2')
        && str_contains($request->url(), 'limit=1'));
});

test('coordinates stay null when nominatim finds nothing', function () {
    Http::fake([NominatimGeocoder::ENDPOINT.'*' => Http::response([])]);

    $location = Location::factory()->create();
    (new GeocodeLocation($location->id))->handle(new NominatimGeocoder);

    expect($location->refresh()->hasCoordinates())->toBeFalse();
});

test('coordinates stay null on server errors and timeouts without throwing', function () {
    Http::fake([NominatimGeocoder::ENDPOINT.'*' => Http::response('nope', 500)]);

    $location = Location::factory()->create();
    (new GeocodeLocation($location->id))->handle(new NominatimGeocoder);

    expect($location->refresh()->hasCoordinates())->toBeFalse();

    Http::fake([NominatimGeocoder::ENDPOINT.'*' => fn () => throw new ConnectionException('timeout')]);

    (new GeocodeLocation($location->id))->handle(new NominatimGeocoder);
    expect($location->refresh()->hasCoordinates())->toBeFalse();
});

test('creating a location via the page dispatches the geocode job', function () {
    Queue::fake();
    $user = geocodeUser();

    Livewire::actingAs($user)->test('pages::locations.index')
        ->call('openCreate')
        ->set('name', 'Hauptsitz')
        ->set('street', 'Unter den Linden 1')
        ->set('postal_code', '10117')
        ->set('city', 'Berlin')
        ->call('save');

    $location = Location::query()->first();

    expect($location)->not->toBeNull();
    Queue::assertPushed(GeocodeLocation::class, 1);
});

test('changing the address re-dispatches the job and clears stale coordinates', function () {
    $user = geocodeUser();
    $location = Location::factory()->create([
        'company_id' => $user->currentCompany()->id,
        'lat' => 50.0,
        'lng' => 8.0,
    ]);

    Queue::fake();

    Livewire::actingAs($user)->test('pages::locations.index')
        ->call('openEdit', $location->id)
        ->set('city', 'München')
        ->call('save');

    Queue::assertPushed(GeocodeLocation::class, 1);
    expect($location->refresh()->hasCoordinates())->toBeFalse();
});

test('changing non-address fields does not trigger geocoding', function () {
    $user = geocodeUser();
    $location = Location::factory()->create([
        'company_id' => $user->currentCompany()->id,
        'lat' => 50.0,
        'lng' => 8.0,
    ]);

    Queue::fake();

    Livewire::actingAs($user)->test('pages::locations.index')
        ->call('openEdit', $location->id)
        ->set('name', 'Neuer Name')
        ->set('notes', 'Nur eine Notiz')
        ->set('sort', 5)
        ->call('save');

    Queue::assertNotPushed(GeocodeLocation::class);
    expect($location->refresh()->lat)->toBe(50.0);
});

test('coordinates can be re-resolved manually from the edit modal', function () {
    Queue::fake();
    $user = geocodeUser();
    $location = Location::factory()->create(['company_id' => $user->currentCompany()->id]);

    Livewire::actingAs($user)->test('pages::locations.index')
        ->call('openEdit', $location->id)
        ->call('refreshCoordinates');

    Queue::assertPushed(GeocodeLocation::class, 1);
});

test('planb:geocode-locations resolves only locations without coordinates, throttled', function () {
    Http::fake([NominatimGeocoder::ENDPOINT.'*' => Http::response(nominatimHit(48.1371079, 11.5753822))]);
    Sleep::fake();

    $done = Location::factory()->create(['lat' => 50.0, 'lng' => 8.0]);
    $pendingA = Location::factory()->create();
    $pendingB = Location::factory()->create();

    $this->artisan('planb:geocode-locations')
        ->expectsOutputToContain('2 von 2 Standorten geokodiert.')
        ->assertSuccessful();

    expect($pendingA->refresh()->lat)->toBe(48.1371079)
        ->and($pendingB->refresh()->lng)->toBe(11.5753822)
        ->and($done->refresh()->lat)->toBe(50.0);

    Http::assertSentCount(2);
    // 1 Request/Sekunde: zwischen den beiden Requests genau eine Sekunde Pause.
    Sleep::assertSleptTimes(1);
});

test('the mobile sync bundle exposes lat/lng per location', function () {
    $user = geocodeUser();
    $company = $user->currentCompany();

    Location::factory()->create([
        'company_id' => $company->id,
        'lat' => 52.52,
        'lng' => 13.405,
        'sort' => 0,
    ]);
    Location::factory()->create(['company_id' => $company->id, 'sort' => 1]);

    $bundle = MobileSyncBundle::for($company);

    expect($bundle['locations'][0]['lat'])->toBe(52.52)
        ->and($bundle['locations'][0]['lng'])->toBe(13.405)
        ->and($bundle['locations'][1]['lat'])->toBeNull()
        ->and($bundle['locations'][1]['lng'])->toBeNull();
});
