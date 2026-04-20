<?php

use App\Models\Company;
use App\Models\User;
use App\Notifications\ReviewDueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('reviewDueAt falls back to created_at plus cycle when never reviewed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $company->forceFill(['created_at' => Carbon::now()->subMonths(7), 'review_cycle_months' => 6])->save();
    $company->refresh();

    expect($company->isReviewDue())->toBeTrue()
        ->and((int) round($company->reviewDueAt()->diffInMonths($company->created_at, true)))->toBe(6);
});

test('reviewDueAt uses last_reviewed_at when present', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'review_cycle_months' => 6,
        'last_reviewed_at' => Carbon::now()->subMonths(7),
    ]);

    expect($company->isReviewDue())->toBeTrue();

    $company->forceFill(['last_reviewed_at' => Carbon::now()->subMonths(3)])->save();
    $company->refresh();

    expect($company->isReviewDue())->toBeFalse();
});

test('reviews:send-due sends notifications to team members of overdue companies', function () {
    Notification::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'review_cycle_months' => 6,
        'last_reviewed_at' => Carbon::now()->subMonths(7),
    ]);

    // Add user as team member (factory already ensures this but be explicit).
    $this->artisan('reviews:send-due')->assertExitCode(0);

    Notification::assertSentTo($user->currentTeam->members()->first(), ReviewDueNotification::class);

    expect($company->fresh()->last_reminder_sent_at)->not->toBeNull();
});

test('reviews:send-due skips companies that are not due', function () {
    Notification::fake();

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create([
        'review_cycle_months' => 6,
        'last_reviewed_at' => Carbon::now()->subMonths(1),
    ]);

    $this->artisan('reviews:send-due')->assertExitCode(0);

    Notification::assertNothingSent();
});

test('reviews:send-due respects the cooldown and does not spam', function () {
    Notification::fake();

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create([
        'review_cycle_months' => 6,
        'last_reviewed_at' => Carbon::now()->subMonths(7),
        'last_reminder_sent_at' => Carbon::now()->subDays(3),
    ]);

    $this->artisan('reviews:send-due')->assertExitCode(0);

    Notification::assertNothingSent();
});

test('confirming the review on the dashboard updates last_reviewed_at', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'review_cycle_months' => 6,
        'last_reviewed_at' => Carbon::now()->subMonths(7),
        'last_reminder_sent_at' => Carbon::now()->subDay(),
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::dashboard')
        ->call('confirmReview')
        ->assertHasNoErrors();

    $fresh = $company->fresh();
    expect($fresh->last_reviewed_at)->not->toBeNull()
        ->and($fresh->last_reviewed_at->isToday())->toBeTrue()
        ->and($fresh->last_reminder_sent_at)->toBeNull()
        ->and($fresh->isReviewDue())->toBeFalse();
});

test('dashboard shows the review banner when overdue', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create([
        'review_cycle_months' => 6,
        'last_reviewed_at' => Carbon::now()->subMonths(7),
    ]);

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Review überfällig')
        ->assertSee('Jetzt bestätigen');
});
