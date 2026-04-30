<?php

use App\Enums\ServiceProviderType;
use App\Models\Company;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('detail page lists contact, contract, systems and tasks of a provider', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $provider = ServiceProvider::factory()->for($company)->ofType(ServiceProviderType::ItMsp)->create([
        'name' => 'Acme IT-Services',
        'contact_name' => 'Maria Schmidt',
        'hotline' => '0800 123456',
        'email' => 'support@acme-it.example',
        'contract_number' => 'K-2025-AB',
        'sla' => '24/7',
        'direct_order_limit' => 1500.00,
        'notes' => 'Vor-Ort-Reaktion in 4 h zugesichert.',
    ]);

    $system = System::factory()->for($company)->create(['name' => 'Acme-System']);
    AssignmentSync::attach($provider, $provider->systems(), $system->id, [
        'ownership_kind' => 'operator', 'is_deputy' => false, 'note' => 'Operator für Patches',
    ]);

    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'system_id' => $system->id,
        'title' => 'Patch-Installation',
    ]);
    AssignmentSync::attach($task, $task->providerAssignees(), $provider->id, [
        'raci_role' => 'R', 'is_deputy' => false,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('service-providers.show', $provider))
        ->assertOk()
        ->assertSee('Acme IT-Services')
        ->assertSee('Maria Schmidt')
        ->assertSee('0800 123456')
        ->assertSee('support@acme-it.example')
        ->assertSee('K-2025-AB')
        ->assertSee('24/7')
        ->assertSee('1.500,00')
        ->assertSee('Vor-Ort-Reaktion')
        ->assertSee('Acme-System')
        ->assertSee('Operator')
        ->assertSee('Patch-Installation')
        ->assertSee('R · Durchführend');
});

test('delete from detail page removes the provider and redirects to the index', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $provider = ServiceProvider::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::service-providers.show', ['provider' => $provider])
        ->call('delete')
        ->assertRedirect(route('service-providers.index'));

    expect(ServiceProvider::withoutGlobalScope(CurrentCompanyScope::class)->find($provider->id))->toBeNull();
});

test('providers index dropdown links to the provider detail page', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $provider = ServiceProvider::factory()->for($company)->create([
        'name' => 'Index-Test-Anbieter',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('service-providers.index'))
        ->assertOk()
        ->assertSee(route('service-providers.show', $provider), false)
        ->assertSee('Details');
});
