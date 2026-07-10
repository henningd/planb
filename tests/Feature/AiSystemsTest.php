<?php

use App\Enums\AiRiskClass;
use App\Enums\AiSystemRole;
use App\Models\AiSystem;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function aiActingUser(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

test('the ai governance page lists systems of the current company', function () {
    [$user, $company] = aiActingUser();
    AiSystem::factory()->create(['company_id' => $company->id, 'name' => 'Bewerber-Vorauswahl']);

    $this->actingAs($user)
        ->get(route('ai-systems.index'))
        ->assertOk()
        ->assertSee('Bewerber-Vorauswahl');
});

test('an ai system can be created with role and risk class', function () {
    [$user, $company] = aiActingUser();

    Livewire::actingAs($user)
        ->test('pages::ai-systems.index')
        ->set('name', 'Chatbot Kundenservice')
        ->set('purpose', 'Beantwortet Kundenanfragen automatisiert.')
        ->set('provider_name', 'OpenAI')
        ->set('role', AiSystemRole::Deployer->value)
        ->set('risk_class', AiRiskClass::Limited->value)
        ->set('next_review_at', '2026-12-01')
        ->call('save')
        ->assertHasNoErrors();

    $system = AiSystem::firstWhere('name', 'Chatbot Kundenservice');
    expect($system)->not->toBeNull()
        ->and($system->company_id)->toBe($company->id)
        ->and($system->role)->toBe(AiSystemRole::Deployer)
        ->and($system->risk_class)->toBe(AiRiskClass::Limited);
});

test('the risk class filter narrows the list', function () {
    [$user, $company] = aiActingUser();
    AiSystem::factory()->highRisk()->create(['company_id' => $company->id, 'name' => 'HR-Screening']);
    AiSystem::factory()->create(['company_id' => $company->id, 'name' => 'Rechtschreibhilfe', 'risk_class' => AiRiskClass::Minimal]);

    Livewire::actingAs($user)
        ->test('pages::ai-systems.index')
        ->set('filterRiskClass', AiRiskClass::High->value)
        ->assertSee('HR-Screening')
        ->assertDontSee('Rechtschreibhilfe');
});

test('an overdue review is flagged and the obligation hint reflects the class', function () {
    [$user, $company] = aiActingUser();
    $system = AiSystem::factory()->highRisk()->create([
        'company_id' => $company->id,
        'name' => 'Kreditscoring',
        'next_review_at' => now()->subWeek()->toDateString(),
    ]);

    expect($system->isReviewOverdue())->toBeTrue()
        ->and($system->isHighRisk())->toBeTrue();

    $this->actingAs($user)
        ->get(route('ai-systems.index'))
        ->assertSee('Prüfung überfällig')
        ->assertSee('Risikomanagement');
});

test('ai systems are scoped to the current company', function () {
    [$user] = aiActingUser();

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    AiSystem::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Fremdes KI-System']);

    $this->actingAs($user)
        ->get(route('ai-systems.index', $user->currentTeam))
        ->assertOk()
        ->assertDontSee('Fremdes KI-System');
});
