<?php

use App\Enums\RiskCategory;
use App\Enums\RiskMitigationStatus;
use App\Enums\RiskStatus;
use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Risk;
use App\Models\RiskMitigation;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('lists risks of current company only', function () {
    $user = User::factory()->create();
    $own = Company::factory()->for($user->currentTeam)->create();
    $other = Company::factory()->for(Team::factory())->create();

    Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $own->id,
        'title' => 'Eigenes Risiko',
        'probability' => 3,
        'impact' => 4,
    ]);
    Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $other->id,
        'title' => 'Fremdes Risiko',
        'probability' => 2,
        'impact' => 2,
    ]);

    $this->actingAs($user->fresh());

    expect(Risk::pluck('title')->all())->toBe(['Eigenes Risiko']);
});

it('computes score and severity correctly', function (int $probability, int $impact, int $expectedScore, string $expectedLevel) {
    $risk = Risk::factory()->make([
        'probability' => $probability,
        'impact' => $impact,
    ]);

    expect($risk->score())->toBe($expectedScore);
    expect($risk->severityLevel())->toBe($expectedLevel);
})->with([
    [1, 1, 1, 'low'],
    [2, 2, 4, 'low'],
    [2, 3, 6, 'medium'],
    [3, 3, 9, 'medium'],
    [4, 3, 12, 'high'],
    [5, 3, 15, 'critical'],
    [5, 5, 25, 'critical'],
]);

it('returns null residual score until both fields set', function () {
    $risk = Risk::factory()->make([
        'probability' => 4,
        'impact' => 4,
        'residual_probability' => null,
        'residual_impact' => null,
    ]);

    expect($risk->residualScore())->toBeNull();

    $risk->residual_probability = 2;
    expect($risk->residualScore())->toBeNull();

    $risk->residual_impact = 1;
    expect($risk->residualScore())->toBe(2);
});

it('creates a risk via the create page', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Warenwirtschaft',
        'category' => 'geschaeftsbetrieb',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.create')
        ->set('title', 'Stromausfall Standort')
        ->set('description', 'Stromausfall länger als 2 Stunden')
        ->set('category', RiskCategory::Operational->value)
        ->set('probability', 3)
        ->set('impact', 4)
        ->set('status', RiskStatus::Identified->value)
        ->set('system_ids', [$system->id])
        ->call('addMitigation')
        ->set('mitigations.0.title', 'USV-Anlage prüfen')
        ->set('mitigations.0.description', 'Quartalsweise testen')
        ->set('mitigations.0.status', RiskMitigationStatus::Planned->value)
        ->call('save');

    $risk = Risk::first();
    expect($risk)->not->toBeNull();
    expect($risk->title)->toBe('Stromausfall Standort');
    expect($risk->score())->toBe(12);
    expect($risk->systems)->toHaveCount(1);
    expect($risk->systems->first()->id)->toBe($system->id);
    expect($risk->mitigations)->toHaveCount(1);
    expect($risk->mitigations->first()->title)->toBe('USV-Anlage prüfen');
});

it('skips empty mitigations on create', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.create')
        ->set('title', 'Ohne Maßnahmen')
        ->set('probability', 2)
        ->set('impact', 2)
        ->call('addMitigation')
        ->call('save');

    $risk = Risk::first();
    expect($risk)->not->toBeNull();
    expect($risk->mitigations)->toHaveCount(0);
});

it('cycles mitigation status', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $risk = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'R',
        'category' => RiskCategory::Operational,
        'probability' => 3,
        'impact' => 3,
        'status' => RiskStatus::Identified,
    ]);
    $mitigation = RiskMitigation::create([
        'risk_id' => $risk->id,
        'title' => 'Maßnahme',
        'status' => RiskMitigationStatus::Planned,
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.show', ['risk' => $risk->fresh()]);

    $component->call('cycleMitigationStatus', $mitigation->id);
    expect($mitigation->fresh()->status)->toBe(RiskMitigationStatus::InProgress);

    $component->call('cycleMitigationStatus', $mitigation->id);
    $mitigation->refresh();
    expect($mitigation->status)->toBe(RiskMitigationStatus::Implemented);
    expect($mitigation->implemented_at)->not->toBeNull();
});

it('cascades mitigations when the risk is deleted', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $risk = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'R',
        'probability' => 3,
        'impact' => 3,
    ]);
    RiskMitigation::create([
        'risk_id' => $risk->id,
        'title' => 'M',
    ]);

    $risk->delete();

    expect(RiskMitigation::count())->toBe(0);
});

it('detaches systems when the risk is deleted', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'S',
        'category' => 'geschaeftsbetrieb',
    ]);
    $risk = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'R',
        'probability' => 3,
        'impact' => 3,
    ]);
    $risk->systems()->attach($system->id);

    expect(DB::table('risk_system')->count())->toBe(1);

    $risk->delete();

    expect(DB::table('risk_system')->count())->toBe(0);
});

it('flags review as overdue', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $overdue = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Überfällig',
        'probability' => 3,
        'impact' => 3,
        'review_due_at' => now()->subDay()->toDateString(),
    ]);
    $future = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Zukunft',
        'probability' => 3,
        'impact' => 3,
        'review_due_at' => now()->addDay()->toDateString(),
    ]);

    expect($overdue->isOverdue())->toBeTrue();
    expect($future->isOverdue())->toBeFalse();
});

it('filters by category, status, and only critical', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $tech = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Technisch',
        'probability' => 5,
        'impact' => 5,
        'category' => RiskCategory::Technical,
        'status' => RiskStatus::Identified,
    ]);
    $org = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Organisatorisch',
        'probability' => 2,
        'impact' => 2,
        'category' => RiskCategory::Organizational,
        'status' => RiskStatus::Mitigated,
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.index')
        ->set('category', RiskCategory::Technical->value);

    expect($component->get('risks')->pluck('title')->all())->toBe(['Technisch']);

    $component->set('category', '')
        ->set('only_critical', true);

    expect($component->get('risks')->pluck('title')->all())->toBe(['Technisch']);
});

it('materializes a mitigation as a system task', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Warenwirtschaft',
        'category' => 'geschaeftsbetrieb',
    ]);
    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Anna',
        'last_name' => 'Müller',
    ]);
    $risk = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'R',
        'category' => RiskCategory::Operational,
        'probability' => 4,
        'impact' => 4,
        'status' => RiskStatus::Identified,
    ]);
    $risk->systems()->attach($system->id);
    $mitigation = RiskMitigation::create([
        'risk_id' => $risk->id,
        'title' => 'USV-Anlage prüfen',
        'description' => 'Quartalsweise',
        'status' => RiskMitigationStatus::Planned,
        'target_date' => now()->addDays(30)->toDateString(),
        'responsible_employee_id' => $employee->id,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.show', ['risk' => $risk->fresh()])
        ->call('materializeAsTask', $mitigation->id);

    $mitigation->refresh();
    expect($mitigation->system_task_id)->not->toBeNull();

    $task = SystemTask::find($mitigation->system_task_id);
    expect($task)->not->toBeNull();
    expect($task->title)->toBe('USV-Anlage prüfen');
    expect($task->system_id)->toBe($system->id);
    expect($task->due_date->toDateString())->toBe($mitigation->target_date->toDateString());
    expect($task->assignees->pluck('id')->all())->toContain($employee->id);
});

it('skips materialization when risk has no system', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $risk = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Kein System',
        'category' => RiskCategory::Operational,
        'probability' => 3,
        'impact' => 3,
        'status' => RiskStatus::Identified,
    ]);
    $mitigation = RiskMitigation::create([
        'risk_id' => $risk->id,
        'title' => 'Maßnahme',
        'status' => RiskMitigationStatus::Planned,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.show', ['risk' => $risk->fresh()])
        ->call('materializeAsTask', $mitigation->id);

    expect($mitigation->fresh()->system_task_id)->toBeNull();
    expect(SystemTask::count())->toBe(0);
});

it('does not double-materialize a mitigation', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'S',
        'category' => 'geschaeftsbetrieb',
    ]);
    $risk = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'R',
        'category' => RiskCategory::Operational,
        'probability' => 3,
        'impact' => 3,
        'status' => RiskStatus::Identified,
    ]);
    $risk->systems()->attach($system->id);
    $mitigation = RiskMitigation::create([
        'risk_id' => $risk->id,
        'title' => 'Maßnahme',
        'status' => RiskMitigationStatus::Planned,
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.show', ['risk' => $risk->fresh()]);

    $component->call('materializeAsTask', $mitigation->id);
    $component->call('materializeAsTask', $mitigation->id);

    expect(SystemTask::count())->toBe(1);
});

it('aborts cross-tenant show', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $other = Company::factory()->for(Team::factory())->create();
    $foreignRisk = Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $other->id,
        'title' => 'Fremd',
        'probability' => 1,
        'impact' => 1,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.show', ['risk' => $foreignRisk])
        ->assertStatus(403);
});

it('logs audit entries', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $risk = Risk::create([
        'title' => 'Audit',
        'probability' => 3,
        'impact' => 3,
    ]);
    $risk->update(['title' => 'Audit-Update']);

    $entries = AuditLogEntry::where('entity_type', 'Risk')->get();
    expect($entries->pluck('action')->all())->toBe(['created', 'updated']);
});
