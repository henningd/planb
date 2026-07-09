<?php

use App\Enums\OpenItemConversion;
use App\Enums\OpenItemStatus;
use App\Models\Company;
use App\Models\OpenItem;
use App\Models\Risk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function openItemsActingUser(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

test('the open items page lists items of the current company', function () {
    [$user, $company] = openItemsActingUser();

    OpenItem::factory()->create([
        'company_id' => $company->id,
        'title' => 'Alarmkette nachts klären',
    ]);

    $this->actingAs($user)
        ->get(route('open-items.index'))
        ->assertOk()
        ->assertSee('Alarmkette nachts klären');
});

test('an open item can be created with the full clarification fields', function () {
    [$user, $company] = openItemsActingUser();
    $risk = Risk::factory()->create(['company_id' => $company->id]);

    Livewire::actingAs($user)
        ->test('pages::open-items.index')
        ->set('title', 'FORDEC-Protokoll noch nicht freigegeben')
        ->set('relevance', 'Ohne Freigabe fehlt die verbindliche Entscheidungslogik im Krisenstab.')
        ->set('risk_id', $risk->id)
        ->set('due_at', '2026-09-01')
        ->set('review_at', '2026-12-01')
        ->set('status', 'open')
        ->call('save')
        ->assertHasNoErrors();

    $item = OpenItem::firstWhere('title', 'FORDEC-Protokoll noch nicht freigegeben');

    expect($item)->not->toBeNull()
        ->and($item->company_id)->toBe($company->id)
        ->and($item->risk_id)->toBe($risk->id)
        ->and($item->status)->toBe(OpenItemStatus::Open)
        ->and($item->resolved_at)->toBeNull();
});

test('marking an item resolved records the conversion and resolved timestamp', function () {
    [$user, $company] = openItemsActingUser();
    $item = OpenItem::factory()->create([
        'company_id' => $company->id,
        'title' => 'Papier-Notbetrieb testen',
    ]);

    Livewire::actingAs($user)
        ->test('pages::open-items.index')
        ->call('openEdit', $item->id)
        ->set('status', 'resolved')
        ->set('conversion', 'test')
        ->call('save')
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->status)->toBe(OpenItemStatus::Resolved)
        ->and($item->conversion)->toBe(OpenItemConversion::Test)
        ->and($item->resolved_at)->not->toBeNull();
});

test('reopening a resolved item clears its conversion and resolved timestamp', function () {
    [$user, $company] = openItemsActingUser();
    $item = OpenItem::factory()->resolved(OpenItemConversion::Measure)->create([
        'company_id' => $company->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::open-items.index')
        ->call('openEdit', $item->id)
        ->set('status', 'in_progress')
        ->call('save')
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->status)->toBe(OpenItemStatus::InProgress)
        ->and($item->conversion)->toBeNull()
        ->and($item->resolved_at)->toBeNull();
});

test('open items are scoped to the current company', function () {
    [$user] = openItemsActingUser();

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    OpenItem::factory()->create([
        'company_id' => $otherCompany->id,
        'title' => 'Fremdes Thema',
    ]);

    $this->actingAs($user)
        ->get(route('open-items.index', $user->currentTeam))
        ->assertOk()
        ->assertDontSee('Fremdes Thema');
});
