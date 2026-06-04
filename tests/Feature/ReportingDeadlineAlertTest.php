<?php

use App\Enums\IncidentType;
use App\Enums\ReportingObligation;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\IncidentReportObligation;
use App\Models\User;
use App\Notifications\ReportingDeadlineApproaching;
use App\Scopes\CurrentCompanyScope;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 */
function makeObligation(Company $company, ReportingObligation $obligation, ?CarbonInterface $occurredAt, array $overrides = []): IncidentReportObligation
{
    $report = IncidentReport::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Sicherheitsvorfall',
        'type' => IncidentType::CyberAttack,
        'occurred_at' => $occurredAt,
    ]);

    return IncidentReportObligation::create(array_merge([
        'incident_report_id' => $report->id,
        'obligation' => $obligation,
        'reported_at' => null,
    ], $overrides));
}

test('alerts admins when a fixed deadline is within the warning window and marks it alerted', function () {
    Notification::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    // DSGVO = 72h, occurred 69h ago → deadline in ~3h (< 6h window).
    $obligation = makeObligation($company, ReportingObligation::DsgvoNotification, now()->subHours(69));

    $this->artisan('app:check-reporting-deadlines')->assertExitCode(0);

    Notification::assertSentTo($user, ReportingDeadlineApproaching::class);
    Notification::assertSentTimes(ReportingDeadlineApproaching::class, 1);

    expect($obligation->fresh()->deadline_alerted_at)->not->toBeNull();

    // Second run must not send again.
    $this->artisan('app:check-reporting-deadlines')->assertExitCode(0);
    Notification::assertSentTimes(ReportingDeadlineApproaching::class, 1);
});

test('does not alert when the obligation is already reported', function () {
    Notification::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    makeObligation($company, ReportingObligation::DsgvoNotification, now()->subHours(69), [
        'reported_at' => now(),
    ]);

    $this->artisan('app:check-reporting-deadlines')->assertExitCode(0);

    Notification::assertNothingSent();
});

test('does not alert for obligations without a fixed deadline', function () {
    Notification::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    makeObligation($company, ReportingObligation::CyberInsurance, now()->subHours(69));

    $this->artisan('app:check-reporting-deadlines')->assertExitCode(0);

    Notification::assertNothingSent();
});

test('does not alert when the deadline is far in the future', function () {
    Notification::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    // occurred now, DSGVO deadline +72h → well outside the 6h window.
    makeObligation($company, ReportingObligation::DsgvoNotification, now());

    $this->artisan('app:check-reporting-deadlines')->assertExitCode(0);

    Notification::assertNothingSent();
});
