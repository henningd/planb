<?php

use App\Events\ScenarioRunMessagePosted;
use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunMessage;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Mobile\MobileSyncBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: ScenarioRun}
 */
function runForChat(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Cyberangriff',
        'mode' => 'real',
        'started_at' => now(),
    ]);

    return [$user->fresh(), $company, $run];
}

test('a coordination message can be posted and is broadcast live', function () {
    [$user, $company, $run] = runForChat();
    Event::fake([ScenarioRunMessagePosted::class]);

    Livewire::actingAs($user)
        ->test('pages::scenario-runs.show', ['run' => $run])
        ->set('newMessage', 'Feuerwehr ist eingetroffen')
        ->call('postMessage')
        ->assertHasNoErrors()
        ->assertSet('newMessage', '');

    $message = ScenarioRunMessage::firstWhere('scenario_run_id', $run->id);
    expect($message)->not->toBeNull()
        ->and($message->body)->toBe('Feuerwehr ist eingetroffen')
        ->and($message->company_id)->toBe($company->id)
        ->and($message->user_id)->toBe($user->id);

    Event::assertDispatched(ScenarioRunMessagePosted::class);
});

test('empty messages are ignored', function () {
    [$user, , $run] = runForChat();

    Livewire::actingAs($user)
        ->test('pages::scenario-runs.show', ['run' => $run])
        ->set('newMessage', '   ')
        ->call('postMessage');

    expect(ScenarioRunMessage::count())->toBe(0);
});

test('the mobile sync bundle carries the coordination messages', function () {
    [, $company, $run] = runForChat();
    ScenarioRunMessage::create([
        'company_id' => $company->id,
        'scenario_run_id' => $run->id,
        'author_name' => 'Anna',
        'body' => 'Server-Raum gesperrt',
    ]);

    $bundle = MobileSyncBundle::for($company);
    $activeRuns = $bundle['active_runs'];

    expect($activeRuns)->toHaveCount(1)
        ->and($activeRuns[0]['messages'][0]['body'])->toBe('Server-Raum gesperrt')
        ->and($activeRuns[0]['messages'][0]['author'])->toBe('Anna');
});
