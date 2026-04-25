<?php

use App\Enums\HandbookTestInterval;
use App\Enums\HandbookTestType;
use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\HandbookTest;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create a handbook test', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::handbook-tests.index')
        ->set('type', HandbookTestType::Tabletop->value)
        ->set('interval', HandbookTestInterval::Yearly->value)
        ->set('next_due_at', '2027-04-25')
        ->call('save')
        ->assertHasNoErrors();

    expect(HandbookTest::count())->toBe(1);
});

test('non-admin cannot reach the route', function () {
    $owner = User::factory()->create();
    Company::factory()->for($owner->currentTeam)->create();

    $member = User::factory()->create();
    $owner->currentTeam->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($owner->currentTeam);

    $this->actingAs($member->fresh())
        ->get(route('handbook-tests.index'))
        ->assertForbidden();
});

test('mark executed sets last_executed_at and recomputes next_due_at', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $test = HandbookTest::factory()->for($company)->create([
        'type' => HandbookTestType::BackupRestore,
        'interval' => HandbookTestInterval::Yearly,
        'last_executed_at' => null,
        'next_due_at' => '2026-04-25',
    ]);

    $test->markExecuted(now()->setDate(2026, 4, 26));

    expect($test->fresh()->last_executed_at->toDateString())->toBe('2026-04-26')
        ->and($test->fresh()->next_due_at->toDateString())->toBe('2027-04-26');
});

test('isOverdue is true for past due dates', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $past = HandbookTest::factory()->for($company)->create(['next_due_at' => now()->subDay()->toDateString()]);
    $future = HandbookTest::factory()->for($company)->create(['next_due_at' => now()->addDay()->toDateString()]);

    expect($past->fresh()->isOverdue())->toBeTrue()
        ->and($future->fresh()->isOverdue())->toBeFalse();
});

test('tests are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $other = Company::factory()->for(Team::factory())->create();

    HandbookTest::factory()->for($company)->create(['name' => 'eigen']);
    HandbookTest::factory()->for($other)->create(['name' => 'fremd']);

    $this->actingAs($user->fresh());

    expect(HandbookTest::pluck('name')->all())->toBe(['eigen']);
    expect(HandbookTest::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(2);
});
