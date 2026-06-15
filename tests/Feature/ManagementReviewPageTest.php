<?php

use App\Models\Company;
use App\Models\ManagementReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function reviewActingUser(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

test('the management review page lists reviews of the current company', function () {
    [$user, $company] = reviewActingUser();

    ManagementReview::factory()->create([
        'company_id' => $company->id,
        'title' => 'BCMS-Leitungsbewertung 2026/H1',
    ]);

    $this->actingAs($user)
        ->get(route('management-reviews.index'))
        ->assertOk()
        ->assertSee('BCMS-Leitungsbewertung 2026/H1');
});

test('a review can be created through the Livewire component', function () {
    [$user, $company] = reviewActingUser();

    Livewire::actingAs($user)
        ->test('pages::management-reviews.index')
        ->set('title', 'Jahres-Review BCMS')
        ->set('review_date', now()->toDateString())
        ->set('participants', 'Geschäftsführung, BCM-Beauftragte')
        ->set('summary', 'Kennzahlen und Übungsergebnisse bewertet.')
        ->set('decisions', 'Budget für zusätzliche Übung freigegeben.')
        ->set('next_review_at', now()->addYear()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $review = ManagementReview::firstWhere('title', 'Jahres-Review BCMS');

    expect($review)->not->toBeNull()
        ->and($review->company_id)->toBe($company->id)
        ->and($review->conducted_by)->toBe($user->name);
});

test('a review with a past follow-up date is marked as overdue', function () {
    [$user, $company] = reviewActingUser();

    $review = ManagementReview::factory()->followUpOverdue()->create([
        'company_id' => $company->id,
        'title' => 'Überfälliger Folge-Review',
    ]);

    expect($review->isFollowUpOverdue())->toBeTrue();

    Livewire::actingAs($user)
        ->test('pages::management-reviews.index')
        ->assertSee('Überfälliger Folge-Review')
        ->assertSee(__('Überfällig'));
});
