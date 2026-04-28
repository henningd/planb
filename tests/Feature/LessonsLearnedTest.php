<?php

use App\Enums\LessonLearnedActionItemStatus;
use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\Employee;
use App\Models\HandbookVersion;
use App\Models\IncidentReport;
use App\Models\LessonLearned;
use App\Models\LessonLearnedActionItem;
use App\Models\ScenarioRun;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('lists lessons of current company only', function () {
    $user = User::factory()->create();
    $own = Company::factory()->for($user->currentTeam)->create();

    $other = Company::factory()->for(Team::factory())->create();

    LessonLearned::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $own->id,
        'title' => 'Eigene Auswertung',
    ]);
    LessonLearned::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $other->id,
        'title' => 'Fremde Auswertung',
    ]);

    $this->actingAs($user->fresh());

    expect(LessonLearned::pluck('title')->all())->toBe(['Eigene Auswertung']);
});

it('creates a lesson for an incident report', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $report = IncidentReport::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Phishing-Welle',
        'occurred_at' => now(),
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::lessons-learned.create', ['incident' => $report->id])
        ->assertSet('bind_kind', 'incident')
        ->assertSet('incident_report_id', $report->id)
        ->set('title', 'Auswertung Phishing-Welle')
        ->set('root_cause', 'Schulung lückenhaft')
        ->set('what_went_well', 'Schnelle Erkennung')
        ->set('what_went_poorly', 'Nicht alle informiert')
        ->call('save');

    $lesson = LessonLearned::first();
    expect($lesson)->not->toBeNull();
    expect($lesson->title)->toBe('Auswertung Phishing-Welle');
    expect($lesson->incident_report_id)->toBe($report->id);
    expect($lesson->scenario_run_id)->toBeNull();
    expect($lesson->author_user_id)->toBe($user->id);
});

it('creates a lesson for a scenario run', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Tabletop Ransomware',
        'mode' => 'drill',
        'started_at' => now(),
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::lessons-learned.create', ['run' => $run->id])
        ->assertSet('bind_kind', 'run')
        ->assertSet('scenario_run_id', $run->id)
        ->set('title', 'Auswertung Tabletop')
        ->call('save');

    $lesson = LessonLearned::first();
    expect($lesson->scenario_run_id)->toBe($run->id);
    expect($lesson->incident_report_id)->toBeNull();
});

it('rejects bind_kind incident without selecting an incident', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::lessons-learned.create')
        ->set('title', 'Etwas')
        ->set('bind_kind', 'incident')
        ->call('save')
        ->assertHasErrors(['incident_report_id']);

    expect(LessonLearned::count())->toBe(0);
});

it('adds, cycles, and deletes action items', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Anna',
        'last_name' => 'Müller',
    ]);
    $lesson = LessonLearned::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Lesson',
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::lessons-learned.show', ['lesson' => $lesson])
        ->call('openAddAction')
        ->set('new_action_description', 'Schulung wiederholen')
        ->set('new_action_responsible', $employee->id)
        ->set('new_action_due_date', now()->addDays(14)->toDateString())
        ->call('addAction');

    $action = LessonLearnedActionItem::first();
    expect($action)->not->toBeNull();
    expect($action->status)->toBe(LessonLearnedActionItemStatus::Open);

    $component->call('cycleStatus', $action->id);
    expect($action->fresh()->status)->toBe(LessonLearnedActionItemStatus::InProgress);

    $component->call('cycleStatus', $action->id);
    $action->refresh();
    expect($action->status)->toBe(LessonLearnedActionItemStatus::Done);
    expect($action->completed_at)->not->toBeNull();

    $component->call('deleteAction', $action->id);
    expect(LessonLearnedActionItem::count())->toBe(0);
});

it('cascades action items when the lesson is deleted', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $lesson = LessonLearned::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Lesson',
    ]);
    LessonLearnedActionItem::create([
        'lesson_learned_id' => $lesson->id,
        'description' => 'Maßnahme',
    ]);

    $lesson->delete();

    expect(LessonLearnedActionItem::count())->toBe(0);
});

it('flags action items as overdue', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $lesson = LessonLearned::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Lesson',
    ]);

    $action = LessonLearnedActionItem::create([
        'lesson_learned_id' => $lesson->id,
        'description' => 'Überfällig',
        'due_date' => now()->subDay()->toDateString(),
        'status' => LessonLearnedActionItemStatus::Open,
    ]);

    $done = LessonLearnedActionItem::create([
        'lesson_learned_id' => $lesson->id,
        'description' => 'Schon fertig',
        'due_date' => now()->subDay()->toDateString(),
        'status' => LessonLearnedActionItemStatus::Done,
    ]);

    expect($action->isOverdue())->toBeTrue();
    expect($done->isOverdue())->toBeFalse();
});

it('logs audit entries on create and update', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $lesson = LessonLearned::create(['title' => 'Audit-Test']);
    $lesson->update(['root_cause' => 'Wirklich?']);

    $entries = AuditLogEntry::where('entity_type', 'LessonLearned')->get();
    expect($entries->pluck('action')->all())->toBe(['created', 'updated']);
});

it('toggles finalized state', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $lesson = LessonLearned::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Lesson',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::lessons-learned.show', ['lesson' => $lesson])
        ->call('toggleFinalized');

    expect($lesson->fresh()->finalized_at)->not->toBeNull();
});

it('aborts show when accessed cross-tenant', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $other = Company::factory()->for(Team::factory())->create();
    $foreignLesson = LessonLearned::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $other->id,
        'title' => 'Fremd',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::lessons-learned.show', ['lesson' => $foreignLesson])
        ->assertStatus(403);
});

it('links a lesson to a handbook version', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $version = HandbookVersion::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'version' => '1.2',
        'changed_at' => now(),
        'change_reason' => 'Test',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::lessons-learned.create')
        ->set('title', 'Auswertung mit Versions-Bezug')
        ->set('handbook_version_id', $version->id)
        ->call('save');

    $lesson = LessonLearned::first();
    expect($lesson->handbook_version_id)->toBe($version->id);
    expect($lesson->handbookVersion->is($version))->toBeTrue();
    expect($version->fresh()->lessonsLearned->pluck('id')->all())->toBe([$lesson->id]);
});

it('nulls handbook_version_id when the version is deleted', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $version = HandbookVersion::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'version' => '1.0',
        'changed_at' => now(),
        'change_reason' => 'Test',
    ]);
    $lesson = LessonLearned::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Lesson',
        'handbook_version_id' => $version->id,
    ]);

    $version->delete();

    expect($lesson->fresh()->handbook_version_id)->toBeNull();
});

it('hides the route when feature flag is disabled', function () {
    config(['features.lessons_learned' => false]);

    expect(Route::has('lessons-learned.index'))->toBeFalse();
})->skip('Routes are registered at boot; flag-flip mid-test is not supported.');
