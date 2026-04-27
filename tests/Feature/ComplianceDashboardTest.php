<?php

use App\Enums\CommunicationAudience;
use App\Enums\CommunicationChannel;
use App\Enums\ComplianceCategory;
use App\Enums\TeamRole;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\HandbookTest;
use App\Models\HandbookVersion;
use App\Models\Location;
use App\Models\User;
use App\Support\Compliance\Catalog;
use App\Support\Compliance\Evaluator;
use App\Support\Compliance\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('catalog returns all checks across the four categories', function () {
    $checks = Catalog::all();

    expect($checks)->not->toBeEmpty();

    $categories = collect($checks)->pluck('category')->unique();
    foreach (ComplianceCategory::ordered() as $cat) {
        expect($categories->contains($cat))->toBeTrue("Kategorie {$cat->value} fehlt im Katalog");
    }
});

test('empty company gets a low score and fail status everywhere', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $report = Evaluator::for($company);

    expect($report->score())->toBeLessThan(20);
    expect($report->readinessLabel())->toBe('Nicht vorbereitet');

    $statuses = collect($report->items)->pluck('result.status');
    expect($statuses->contains(Status::Fail))->toBeTrue();
});

test('filling out the handbook lifts the score', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $emptyScore = Evaluator::for($company)->score();

    Location::factory()->for($company)->create(['is_headquarters' => true]);
    HandbookVersion::factory()->for($company)->create([
        'approved_at' => now(),
    ]);
    HandbookTest::factory()->for($company)->create([
        'last_executed_at' => now()->toDateString(),
        'next_due_at' => now()->addMonths(6)->toDateString(),
    ]);
    EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->addMonths(6)->toDateString(),
    ]);
    foreach (range(1, 3) as $i) {
        CommunicationTemplate::create([
            'company_id' => $company->id,
            'name' => "Vorlage {$i}",
            'audience' => CommunicationAudience::Customers->value,
            'channel' => CommunicationChannel::Email->value,
            'subject' => 'Test',
            'body' => 'Test-Inhalt',
            'sort' => $i,
        ]);
    }

    expect(Evaluator::for($company)->score())->toBeGreaterThan($emptyScore);
});

test('top actions surface the highest impact issues', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $actions = Evaluator::for($company)->topActions(3);

    expect($actions)->toHaveCount(3);
    expect($actions[0]['gain'])->toBeGreaterThanOrEqual($actions[1]['gain']);
    expect($actions[1]['gain'])->toBeGreaterThanOrEqual($actions[2]['gain']);
});

test('compliance page renders for an admin user', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('compliance.index', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()
        ->assertSeeText('Compliance')
        ->assertSeeText('Reifegrad');
});

test('compliance page is forbidden for non-admin users', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;
    Company::factory()->for($team)->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member->fresh())
        ->get(route('compliance.index', ['current_team' => $team->slug]))
        ->assertForbidden();
});
