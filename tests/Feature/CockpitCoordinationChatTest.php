<?php

use App\Events\ScenarioRunMessagePosted;
use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

/**
 * @return array{0: User, 1: Company, 2: ScenarioRun}
 */
function cockpitActiveRun(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::factory()->for($company)->create([
        'started_at' => now(),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    return [$user->fresh(), $company, $run];
}

test('a coordination message posted from the cockpit is stored and broadcast', function () {
    [$user, $company, $run] = cockpitActiveRun();
    Event::fake([ScenarioRunMessagePosted::class]);

    Livewire::actingAs($user)
        ->test('pages::incident-mode.index')
        ->set('newCoordinationMessage', 'Feuerwehr ist eingetroffen')
        ->call('postCoordinationMessage')
        ->assertHasNoErrors()
        ->assertSet('newCoordinationMessage', '');

    $message = ScenarioRunMessage::firstWhere('scenario_run_id', $run->id);
    expect($message)->not->toBeNull()
        ->and($message->body)->toBe('Feuerwehr ist eingetroffen')
        ->and($message->company_id)->toBe($company->id)
        ->and($message->user_id)->toBe($user->id)
        ->and($message->author_name)->toBe($user->name);

    Event::assertDispatched(ScenarioRunMessagePosted::class);
});

test('the cockpit shows coordination messages from the app', function () {
    [$user, $company, $run] = cockpitActiveRun();
    ScenarioRunMessage::create([
        'company_id' => $company->id,
        'scenario_run_id' => $run->id,
        'author_name' => 'Anna (App)',
        'body' => 'Server-Raum gesperrt',
    ]);

    Livewire::actingAs($user)
        ->test('pages::incident-mode.index')
        ->assertSee('Server-Raum gesperrt')
        ->assertSee('Anna (App)');
});

test('empty coordination messages are ignored', function () {
    [$user] = cockpitActiveRun();

    Livewire::actingAs($user)
        ->test('pages::incident-mode.index')
        ->set('newCoordinationMessage', '   ')
        ->call('postCoordinationMessage');

    expect(ScenarioRunMessage::count())->toBe(0);
});

test('no coordination message is stored without an active run', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->set('newCoordinationMessage', 'Ins Leere geschrieben')
        ->call('postCoordinationMessage');

    expect(ScenarioRunMessage::count())->toBe(0);
});
