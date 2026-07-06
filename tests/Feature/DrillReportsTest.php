<?php

use App\Enums\ScenarioRunMode;
use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\ScenarioRunAcknowledgement;
use App\Models\User;
use App\Support\Reports\DrillReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company}
 */
function drillReportActor(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

function drillReportRun(Company $company, array $attributes = []): ScenarioRun
{
    return ScenarioRun::factory()->create(array_merge([
        'company_id' => $company->id,
        'mode' => ScenarioRunMode::Drill,
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
    ], $attributes));
}

test('drill reports list shows only completed drill runs of the own company', function () {
    [$user, $company] = drillReportActor();

    $completedDrill = drillReportRun($company, ['title' => 'Eigene beendete Übung']);
    $abortedDrill = drillReportRun($company, [
        'title' => 'Eigene abgebrochene Übung',
        'ended_at' => null,
        'aborted_at' => now()->subHour(),
    ]);
    drillReportRun($company, [
        'title' => 'Noch laufende Übung',
        'ended_at' => null,
    ]);
    drillReportRun($company, [
        'title' => 'Beendeter Ernstfall',
        'mode' => ScenarioRunMode::Real,
    ]);

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    drillReportRun($otherCompany, ['title' => 'Fremde Übung']);

    $this->actingAs($user)
        ->get(route('drill-reports.index', ['current_team' => $user->currentTeam->slug]))
        ->assertOk();

    // Ohne Seiten-Layout testen: der Incident-Banner im App-Chrome zeigt
    // aktive Runs ebenfalls an und würde assertDontSee verfälschen.
    Livewire\Livewire::actingAs($user)
        ->test('pages::drill-reports.index')
        ->assertSee('Eigene beendete Übung')
        ->assertSee('Eigene abgebrochene Übung')
        ->assertDontSee('Noch laufende Übung')
        ->assertDontSee('Beendeter Ernstfall')
        ->assertDontSee('Fremde Übung');

    expect($completedDrill->fresh())->not->toBeNull()
        ->and($abortedDrill->aborted_at)->not->toBeNull();
});

test('drill report detail computes the metrics for a constructed run', function () {
    [$user, $company] = drillReportActor();
    $helper = User::factory()->create(['name' => 'Helga Helferin']);

    $start = now()->subDay()->setTime(10, 0);

    $run = drillReportRun($company, [
        'title' => 'Kennzahlen-Übung',
        'started_by_user_id' => $user->id,
        'started_at' => $start,
        'ended_at' => $start->copy()->addMinutes(90),
        'escalated_at' => $start->copy()->addMinutes(4),
    ]);

    $run->steps()->create([
        'sort' => 1,
        'title' => 'Server isolieren',
        'checked_at' => $start->copy()->addMinutes(10),
        'checked_by_user_id' => $user->id,
    ]);
    $run->steps()->create([
        'sort' => 2,
        'title' => 'Geschäftsführung informieren',
        'checked_at' => $start->copy()->addMinutes(20),
        'checked_by_user_id' => $helper->id,
    ]);
    $run->steps()->create([
        'sort' => 3,
        'title' => 'Presse-Statement vorbereiten',
    ]);

    ScenarioRunAcknowledgement::factory()->create([
        'scenario_run_id' => $run->id,
        'user_id' => $helper->id,
        'status' => ScenarioRunAcknowledgement::STATUS_SEEN,
        'acknowledged_at' => $start->copy()->addSeconds(120),
    ]);
    ScenarioRunAcknowledgement::factory()->create([
        'scenario_run_id' => $run->id,
        'user_id' => $user->id,
        'status' => ScenarioRunAcknowledgement::STATUS_TAKING_OVER,
        'acknowledged_at' => $start->copy()->addSeconds(300),
    ]);

    $report = DrillReport::for($run->fresh());

    expect($report->durationSeconds())->toBe(90 * 60)
        ->and($report->secondsToFirstAcknowledgement())->toBe(120)
        ->and($report->secondsToTakeover())->toBe(300)
        ->and($report->stepsTotal())->toBe(3)
        ->and($report->stepsDone())->toBe(2)
        ->and($report->stepsOpen())->toBe(1)
        ->and($report->wasEscalated())->toBeTrue()
        ->and($report->wasAborted())->toBeFalse()
        ->and($report->outcomeLabel())->toBe('Beendet')
        ->and($report->participantNames()->all())->toContain($user->name, 'Helga Helferin')
        ->and($report->acknowledgedUserCount())->toBe(2)
        ->and($report->gaps())->toContain('1 Schritt blieb offen und wurde nicht abgehakt.')
        ->and($report->gaps())->toContain('Die Eskalation wurde ausgelöst, weil zunächst keine Reaktion erfolgte.');

    $this->actingAs($user)
        ->get(route('drill-reports.show', [
            'current_team' => $user->currentTeam->slug,
            'run' => $run,
        ]))
        ->assertOk()
        ->assertSee('Kennzahlen-Übung')
        ->assertSee('1 Std. 30 Min.')
        ->assertSee('2 Min.')
        ->assertSee('5 Min.')
        ->assertSee('Presse-Statement vorbereiten')
        ->assertSee('Helga Helferin')
        ->assertSee('Festgestellte Lücken');
});

test('drill report detail is not available for real or active runs', function () {
    [$user, $company] = drillReportActor();

    $realRun = drillReportRun($company, ['mode' => ScenarioRunMode::Real]);
    $activeRun = drillReportRun($company, ['ended_at' => null]);

    $this->actingAs($user);

    $this->get(route('drill-reports.show', $realRun))->assertNotFound();
    $this->get(route('drill-reports.show', $activeRun))->assertNotFound();
});

test('drill report detail blocks access for other tenants', function () {
    [$user] = drillReportActor();

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $foreignRun = drillReportRun($otherCompany);

    // Das gescopte Route-Binding filtert fremde IDs auf 404.
    $this->actingAs($user)
        ->get(route('drill-reports.show', [
            'current_team' => $user->currentTeam->slug,
            'run' => $foreignRun,
        ]))
        ->assertNotFound();
});

test('drill report PDF export returns a PDF for the current tenant', function () {
    [$user, $company] = drillReportActor();

    $run = drillReportRun($company, [
        'title' => 'PDF-Übung',
        'started_by_user_id' => $user->id,
    ]);
    $run->steps()->create([
        'sort' => 1,
        'title' => 'Notfallkontakte prüfen',
        'checked_at' => now()->subMinutes(90),
        'checked_by_user_id' => $user->id,
    ]);
    ScenarioRunAcknowledgement::factory()->create([
        'scenario_run_id' => $run->id,
        'user_id' => $user->id,
        'status' => ScenarioRunAcknowledgement::STATUS_TAKING_OVER,
        'acknowledged_at' => now()->subMinutes(110),
    ]);

    $response = $this->actingAs($user)->get(route('drill-reports.pdf', ['run' => $run]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('drill report PDF export is 404 for foreign or active runs', function () {
    [$user, $company] = drillReportActor();

    $activeRun = drillReportRun($company, ['ended_at' => null]);

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $foreignRun = drillReportRun($otherCompany);

    $this->actingAs($user);

    $this->get(route('drill-reports.pdf', [
        'current_team' => $user->currentTeam->slug,
        'run' => $activeRun,
    ]))->assertNotFound();
    $this->get(route('drill-reports.pdf', [
        'current_team' => $user->currentTeam->slug,
        'run' => $foreignRun,
    ]))->assertNotFound();
});
