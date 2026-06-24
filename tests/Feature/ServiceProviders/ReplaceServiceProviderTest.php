<?php

use App\Actions\ServiceProviders\ReplaceServiceProvider;
use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\Team;
use App\Models\User;
use App\Support\AssignmentSync;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function providerTenant(): Company
{
    $team = Team::factory()->create();
    $company = Company::factory()->for($team)->create();
    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->forceFill(['current_team_id' => $team->id])->save();

    test()->actingAs($owner->fresh());
    URL::defaults(['current_team' => $team->slug]);

    return $company;
}

test('replacing a provider moves its system and task assignments to the new provider', function () {
    $company = providerTenant();
    $cid = ['company_id' => $company->id];

    $a = ServiceProvider::factory()->create($cid);
    $b = ServiceProvider::factory()->create($cid);
    $system = System::factory()->create($cid);
    $task = SystemTask::factory()->create($cid + ['system_id' => $system->id]);

    AssignmentSync::attach($a, $a->systems(), $system->id, ['raci_role' => 'R', 'ownership_kind' => 'owner', 'is_deputy' => false]);
    AssignmentSync::attach($a, $a->tasks(), $task->id, ['raci_role' => 'R', 'is_deputy' => false]);

    $summary = app(ReplaceServiceProvider::class)->handle($a, $b);

    expect($summary)->toMatchArray(['systems' => 1, 'tasks' => 1]);

    expect($a->systems()->count())->toBe(0)
        ->and($a->tasks()->count())->toBe(0)
        ->and($b->systems()->whereKey($system->id)->exists())->toBeTrue()
        ->and($b->tasks()->whereKey($task->id)->exists())->toBeTrue();
});

test('replacing a provider does not overwrite what B already has', function () {
    $company = providerTenant();
    $cid = ['company_id' => $company->id];

    $a = ServiceProvider::factory()->create($cid);
    $b = ServiceProvider::factory()->create($cid);
    $system = System::factory()->create($cid);

    AssignmentSync::attach($a, $a->systems(), $system->id, ['raci_role' => 'C', 'ownership_kind' => 'contact', 'is_deputy' => false]);
    AssignmentSync::attach($b, $b->systems(), $system->id, ['raci_role' => 'R', 'ownership_kind' => 'owner', 'is_deputy' => false]);

    app(ReplaceServiceProvider::class)->handle($a, $b);

    expect($a->systems()->count())->toBe(0)
        ->and($b->systems()->count())->toBe(1)
        ->and($b->systems()->first()->pivot->raci_role)->toBe('R');
});

test('replace provider refuses different companies and the same provider', function () {
    $company = providerTenant();
    $a = ServiceProvider::factory()->create(['company_id' => $company->id]);

    expect(fn () => app(ReplaceServiceProvider::class)->handle($a, $a))->toThrow(InvalidArgumentException::class);

    $other = ServiceProvider::factory()->create(['company_id' => Company::factory()->create()->id]);
    expect(fn () => app(ReplaceServiceProvider::class)->handle($a, $other))->toThrow(InvalidArgumentException::class);
});

test('the provider page replace action transfers and redirects to the new provider', function () {
    $company = providerTenant();
    $cid = ['company_id' => $company->id];

    $a = ServiceProvider::factory()->create($cid);
    $b = ServiceProvider::factory()->create($cid);
    $system = System::factory()->create($cid);
    AssignmentSync::attach($a, $a->systems(), $system->id, ['raci_role' => 'R', 'ownership_kind' => 'owner', 'is_deputy' => false]);

    Livewire::test('pages::service-providers.show', ['provider' => $a])
        ->set('replaceTargetId', $b->id)
        ->call('replaceProvider')
        ->assertHasNoErrors()
        ->assertRedirect(route('service-providers.show', $b));

    expect($a->systems()->count())->toBe(0)
        ->and($b->systems()->whereKey($system->id)->exists())->toBeTrue();
});
