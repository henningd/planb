<?php

use App\Enums\BcmPolicyStatus;
use App\Models\BcmPolicy;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function policyActingUser(): User
{
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    return $user->fresh();
}

test('the bcm policy page renders for the current company', function () {
    $user = policyActingUser();

    $this->actingAs($user)
        ->get(route('bcm-policy.index'))
        ->assertOk()
        ->assertSee('BCM-Leitlinie');
});

test('a policy can be created and saved through the Livewire component', function () {
    $user = policyActingUser();

    Livewire::actingAs($user)
        ->test('pages::bcm-policy.index')
        ->set('scope', 'Gesamte Organisation')
        ->set('content', 'Grundsätze des Business Continuity Managements.')
        ->set('version', '1.0')
        ->call('save')
        ->assertHasNoErrors();

    $policy = BcmPolicy::firstWhere('scope', 'Gesamte Organisation');

    expect($policy)->not->toBeNull()
        ->and($policy->company_id)->toBe($user->currentCompany()->id)
        ->and($policy->version)->toBe('1.0')
        ->and($policy->status)->toBe(BcmPolicyStatus::Draft);
});

test('approving a policy sets status, approver and review date', function () {
    $user = policyActingUser();

    BcmPolicy::factory()->create([
        'company_id' => $user->currentCompany()->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bcm-policy.index')
        ->call('approve');

    $policy = BcmPolicy::query()->latest('updated_at')->first();

    expect($policy->status)->toBe(BcmPolicyStatus::Approved)
        ->and($policy->approved_by)->toBe($user->name)
        ->and($policy->approved_at)->not->toBeNull()
        ->and($policy->review_due_at)->not->toBeNull()
        ->and($policy->review_due_at->isFuture())->toBeTrue();
});
