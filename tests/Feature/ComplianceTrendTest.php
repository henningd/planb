<?php

use App\Models\Company;
use App\Models\ComplianceScoreSnapshot;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Compliance\Snapshotter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('snapshot creates one row per day per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $snapshot = Snapshotter::snapshot($company);

    expect($snapshot->company_id)->toBe($company->id);
    expect($snapshot->snapshot_date->toDateString())->toBe(today()->toDateString());
    expect($snapshot->score)->toBeInt()->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);
    expect($snapshot->breakdown)->toBeArray()->not->toBeEmpty();
    expect($snapshot->breakdown[0])->toHaveKeys(['key', 'status', 'score']);

    expect(
        ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->count()
    )->toBe(1);
});

test('second snapshot on same day updates the existing row', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $first = Snapshotter::snapshot($company);
    $second = Snapshotter::snapshot($company);

    expect($second->id)->toBe($first->id);
    expect(
        ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->count()
    )->toBe(1);
});

test('snapshotAll persists for every company', function () {
    $user1 = User::factory()->create();
    $companyA = Company::factory()->for($user1->currentTeam)->create();

    $user2 = User::factory()->create();
    $companyB = Company::factory()->for($user2->currentTeam)->create();

    $user3 = User::factory()->create();
    $companyC = Company::factory()->for($user3->currentTeam)->create();

    $count = Snapshotter::snapshotAll();

    expect($count)->toBeGreaterThanOrEqual(3);

    foreach ([$companyA, $companyB, $companyC] as $company) {
        expect(
            ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)
                ->where('company_id', $company->id)
                ->whereDate('snapshot_date', today())
                ->exists()
        )->toBeTrue("Snapshot fehlt für Company {$company->id}");
    }
});

test('page shows hint when less than two snapshots exist', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('compliance.index', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()
        ->assertSeeText('Verlauf der letzten 30 Tage')
        ->assertSeeText('Noch zu wenig Daten für einen Trend');
});

test('page renders chart when 2+ snapshots exist', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'snapshot_date' => today()->subDays(2)->toDateString(),
        'score' => 30,
        'breakdown' => [['key' => 'roles.system.coverage', 'status' => 'fail', 'score' => 0]],
    ]);
    ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'snapshot_date' => today()->toDateString(),
        'score' => 55,
        'breakdown' => [['key' => 'roles.system.coverage', 'status' => 'partial', 'score' => 50]],
    ]);

    $response = $this->actingAs($user->fresh())
        ->get(route('compliance.index', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk()
        ->assertSeeText('Verlauf der letzten 30 Tage')
        ->assertDontSeeText('Noch zu wenig Daten für einen Trend')
        ->assertSeeText('Heute')
        ->assertSee('<svg', false)
        ->assertSee('<path', false);
});
