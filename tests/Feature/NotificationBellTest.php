<?php

use App\Models\AppNotification;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the bell shows the unread count for the current company feed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    AppNotification::factory()->count(3)->for($company)->create();

    Livewire::actingAs($user->fresh())
        ->test('notification-bell')
        ->assertSet('companyId', $company->id)
        ->assertSee('3');
});

test('notifications created before the seen-at timestamp do not count as unread', function () {
    $user = User::factory()->create(['notifications_seen_at' => now()]);
    $company = Company::factory()->for($user->currentTeam)->create();

    // Older than seen-at: already read.
    AppNotification::factory()->count(2)->for($company)->create([
        'created_at' => now()->subDay(),
    ]);
    // Newer than seen-at: unread.
    AppNotification::factory()->for($company)->create([
        'created_at' => now()->addMinute(),
    ]);

    $component = Livewire::actingAs($user->fresh())
        ->test('notification-bell');

    expect($component->instance()->unreadCount())->toBe(1);
});

test('notifications of other companies are never counted', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $otherCompany = Company::factory()->create();

    AppNotification::factory()->count(2)->for($company)->create();
    AppNotification::factory()->count(5)->for($otherCompany)->create();

    $component = Livewire::actingAs($user->fresh())
        ->test('notification-bell');

    expect($component->instance()->unreadCount())->toBe(2);
});

test('marking all as read sets the timestamp and resets the counter to zero', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    AppNotification::factory()->count(4)->for($company)->create();

    $component = Livewire::actingAs($user->fresh())
        ->test('notification-bell');

    expect($component->instance()->unreadCount())->toBe(4);

    $component->call('markAllAsRead');

    expect($user->fresh()->notifications_seen_at)->not->toBeNull();
    expect($component->instance()->unreadCount())->toBe(0);
});
