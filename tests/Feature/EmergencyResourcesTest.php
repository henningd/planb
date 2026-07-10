<?php

use App\Enums\EmergencyResourceType;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\EmergencyResourceCategory;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create an emergency resource', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    // Kategorie-Auswahl ist beim Mount bereits mit der ersten Standardkategorie
    // vorbelegt (Firmengründung seedet die Kategorien).
    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::emergency-resources.index')
        ->set('name', 'Hauptkasse')
        ->set('location', 'Empfang')
        ->set('access_holders', 'GF, Empfang')
        ->call('save')
        ->assertHasNoErrors();

    expect(EmergencyResource::count())->toBe(1)
        ->and(EmergencyResource::first()->category)->not->toBeNull();
});

test('creating a company seeds the default emergency resource categories', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $count = EmergencyResourceCategory::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->count();

    expect($count)->toBe(count(EmergencyResourceCategory::defaultNames()));
});

test('a custom category can be created and assigned to a resource', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::emergency-resource-categories.index')
        ->set('name', 'Drohnen / Sonderausstattung')
        ->call('save')
        ->assertHasNoErrors();

    $category = EmergencyResourceCategory::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('name', 'Drohnen / Sonderausstattung')
        ->firstOrFail();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::emergency-resources.index')
        ->set('category_id', $category->id)
        ->set('name', 'Inspektionsdrohne')
        ->call('save')
        ->assertHasNoErrors();

    $resource = EmergencyResource::firstWhere('name', 'Inspektionsdrohne');
    expect($resource->category_id)->toBe($category->id)
        ->and($resource->categoryLabel())->toBe('Drohnen / Sonderausstattung');

    $this->actingAs($user->fresh())
        ->get(route('emergency-resources.index'))
        ->assertSee('Drohnen / Sonderausstattung');
});

test('overdue check is flagged', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $overdue = EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->subDay()->toDateString(),
    ]);
    $fresh = EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->addDay()->toDateString(),
    ]);

    expect($overdue->fresh()->isOverdue())->toBeTrue()
        ->and($fresh->fresh()->isOverdue())->toBeFalse();
});

test('resources are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $other = Company::factory()->for(Team::factory())->create();

    EmergencyResource::factory()->for($company)->create(['name' => 'eigen']);
    EmergencyResource::factory()->for($other)->create(['name' => 'fremd']);

    $this->actingAs($user->fresh());

    expect(EmergencyResource::pluck('name')->all())->toBe(['eigen']);
    expect(EmergencyResource::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(2);
});

test('user can delete a resource', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $resource = EmergencyResource::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::emergency-resources.index')
        ->call('confirmDelete', $resource->id)
        ->call('delete');

    expect(EmergencyResource::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});

test('the default categories are broad and cross-industry', function () {
    $names = EmergencyResourceCategory::defaultNames();

    expect(count($names))->toBeGreaterThanOrEqual(16)
        ->and($names)->toContain('Schlüssel / Zutritt')
        ->and($names)->toContain('Notfallarbeitsplatz / Notebook-Pool')
        ->and(EmergencyResourceType::KeysAccess->label())->toBe('Schlüssel / Zutritt');
});
