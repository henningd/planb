<?php

use App\Models\Company;
use App\Models\MobileAccessCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function mobileAccessUser(): User
{
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    return $user->fresh();
}

test('the mobile-access settings page renders', function () {
    $user = mobileAccessUser();

    $this->actingAs($user)
        ->get(route('settings.mobile-access'))
        ->assertOk()
        ->assertSee('Notfall-App');
});

test('generating a code binds it to the user and their company', function () {
    $user = mobileAccessUser();
    $company = $user->currentCompany();

    Livewire::actingAs($user)->test('pages::settings.mobile-access')
        ->call('generate')
        ->assertSet('generatedCode', fn ($code) => is_string($code) && strlen($code) === 8);

    $code = MobileAccessCode::query()->first();

    expect($code)->not->toBeNull()
        ->and($code->user_id)->toBe($user->id)
        ->and($code->company_id)->toBe($company->id)
        ->and($code->email)->toBe($user->email)
        ->and($code->isActive())->toBeTrue();
});

test('a code can be revoked from the page', function () {
    $user = mobileAccessUser();
    $issued = MobileAccessCode::issue($user, $user->currentCompany());

    Livewire::actingAs($user)->test('pages::settings.mobile-access')
        ->call('revoke', $issued['model']->id);

    expect($issued['model']->fresh()->status())->toBe('revoked');
});
