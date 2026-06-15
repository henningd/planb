<?php

use App\Enums\BcmsStage;
use App\Models\Company;
use App\Models\MaturityAssessment;
use App\Models\User;
use App\Support\Bcms\MaturityCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function maturityActingUser(): User
{
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    return $user->fresh();
}

test('the maturity page renders for an authenticated user', function () {
    $user = maturityActingUser();

    $this->actingAs($user)
        ->get(route('maturity.index'))
        ->assertOk()
        ->assertSee('Reifegrad');
});

test('answering everything with yes results in the standard stage', function () {
    $user = maturityActingUser();

    $answers = collect(MaturityCatalog::allKeys())
        ->mapWithKeys(fn (string $key) => [$key => 'yes'])
        ->all();

    $component = Livewire::actingAs($user)->test('pages::maturity.index');

    foreach ($answers as $key => $value) {
        $component->set("answers.{$key}", $value);
    }

    $component->call('evaluate')->assertHasNoErrors();

    $assessment = MaturityAssessment::query()->latest('created_at')->first();

    expect($assessment)->not->toBeNull()
        ->and($assessment->stage)->toBe(BcmsStage::Standard)
        ->and($assessment->score)->toBe(MaturityCatalog::maxScore())
        ->and($assessment->assessed_at->isToday())->toBeTrue();
});

test('answering everything with no results in the reaktiv stage', function () {
    $user = maturityActingUser();

    $component = Livewire::actingAs($user)->test('pages::maturity.index');

    foreach (MaturityCatalog::allKeys() as $key) {
        $component->set("answers.{$key}", 'no');
    }

    $component->call('evaluate')->assertHasNoErrors();

    $assessment = MaturityAssessment::query()->latest('created_at')->first();

    expect($assessment)->not->toBeNull()
        ->and($assessment->stage)->toBe(BcmsStage::Reaktiv)
        ->and($assessment->score)->toBe(0);
});

test('the gap list appears for questions answered with no', function () {
    $user = maturityActingUser();

    $component = Livewire::actingAs($user)->test('pages::maturity.index');

    foreach (MaturityCatalog::allKeys() as $key) {
        $component->set("answers.{$key}", 'no');
    }

    $firstQuestionText = MaturityCatalog::dimensions()[0]['questions'][0]['text'];

    $component->call('evaluate')
        ->assertSet('justSaved', true)
        ->assertSee('Lückenliste')
        ->assertSee($firstQuestionText);
});
