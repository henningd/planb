<?php

use App\Events\ScenarioRunNoteUpdated;
use App\Events\ScenarioRunStepCompleted;
use App\Events\ScenarioRunStepReopened;
use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('broadcasts a step completed event when a step is checked', function () {
    Event::fake([ScenarioRunStepCompleted::class]);

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::factory()->create(['company_id' => $company->id]);
    $step = ScenarioRunStep::factory()->create(['scenario_run_id' => $run->id]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::scenario-runs.show', ['run' => $run])
        ->call('toggleStep', $step->id)
        ->assertHasNoErrors();

    Event::assertDispatched(ScenarioRunStepCompleted::class, function ($event) use ($step, $user) {
        return $event->step->id === $step->id
            && $event->userName === $user->name
            && $event->completedAt !== null;
    });
});

it('broadcasts a step reopened event when a checked step is unchecked', function () {
    Event::fake([ScenarioRunStepReopened::class]);

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::factory()->create(['company_id' => $company->id]);
    $step = ScenarioRunStep::factory()->create([
        'scenario_run_id' => $run->id,
        'checked_at' => now(),
        'checked_by_user_id' => $user->id,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::scenario-runs.show', ['run' => $run])
        ->call('toggleStep', $step->id)
        ->assertHasNoErrors();

    Event::assertDispatched(ScenarioRunStepReopened::class, function ($event) use ($step, $user) {
        return $event->step->id === $step->id && $event->userName === $user->name;
    });
});

it('broadcasts a note updated event when a note is saved', function () {
    Event::fake([ScenarioRunNoteUpdated::class]);

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::factory()->create(['company_id' => $company->id]);
    $step = ScenarioRunStep::factory()->create(['scenario_run_id' => $run->id]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::scenario-runs.show', ['run' => $run])
        ->set("notes.{$step->id}", 'Live-Notiz aus dem War-Room')
        ->call('saveNote', $step->id)
        ->assertHasNoErrors();

    Event::assertDispatched(ScenarioRunNoteUpdated::class, function ($event) use ($step) {
        return $event->step->id === $step->id
            && $event->note === 'Live-Notiz aus dem War-Room';
    });
});

/**
 * Helper: extract a registered channel-auth closure by pattern from the
 * default broadcaster, so we can test routes/channels.php authorization
 * logic directly without depending on a configured driver.
 */
function warRoomChannelClosure(string $pattern): Closure
{
    $broadcaster = Broadcast::driver();
    $reflection = new ReflectionClass($broadcaster);
    $prop = $reflection->getProperty('channels');
    $prop->setAccessible(true);
    $channels = $prop->getValue($broadcaster);

    if (! isset($channels[$pattern])) {
        throw new RuntimeException("Channel pattern not registered: {$pattern}");
    }

    $callback = $channels[$pattern];

    return $callback instanceof Closure ? $callback : Closure::fromCallable($callback);
}

it('authorizes the private channel for users in the same company team', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::factory()->create(['company_id' => $company->id]);

    $callback = warRoomChannelClosure('scenario-run.{run}');
    $result = $callback($user->fresh(), $run->id);

    expect($result)->toBeTrue();
});

it('rejects the private channel for users not on the company team', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->for($owner->currentTeam)->create();
    $run = ScenarioRun::factory()->create(['company_id' => $company->id]);
    $outsider = User::factory()->create();

    $callback = warRoomChannelClosure('scenario-run.{run}');
    $result = $callback($outsider->fresh(), $run->id);

    expect($result)->toBeFalse();
});

it('authorizes the presence channel and returns user info', function () {
    $user = User::factory()->create(['name' => 'Anna Test']);
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::factory()->create(['company_id' => $company->id]);

    $callback = warRoomChannelClosure('scenario-run.{run}.presence');
    $result = $callback($user->fresh(), $run->id);

    expect($result)->toBeArray()
        ->and($result['id'])->toBe($user->id)
        ->and($result['name'])->toBe('Anna Test')
        ->and($result['initials'])->toBeString();
});

it('rejects the presence channel for outsiders', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->for($owner->currentTeam)->create();
    $run = ScenarioRun::factory()->create(['company_id' => $company->id]);
    $outsider = User::factory()->create();

    $callback = warRoomChannelClosure('scenario-run.{run}.presence');
    $result = $callback($outsider->fresh(), $run->id);

    expect($result)->toBeNull();
});
