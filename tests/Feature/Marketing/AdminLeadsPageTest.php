<?php

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('non super admins are blocked from the leads page', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user->fresh())
        ->get(route('admin.leads.index'))
        ->assertForbidden();
});

test('super admin sees the collected leads', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    Lead::factory()->confirmed()->create(['email' => 'alpha@firma.de']);
    Lead::factory()->create(['email' => 'beta@firma.de']);

    $this->actingAs($admin->fresh())
        ->get(route('admin.leads.index'))
        ->assertOk()
        ->assertSee('alpha@firma.de')
        ->assertSee('beta@firma.de');
});

test('the leads list can be searched by email', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    Lead::factory()->create(['email' => 'gesucht@firma.de']);
    Lead::factory()->create(['email' => 'anders@firma.de']);

    Livewire::actingAs($admin->fresh())->test('pages::admin.leads.index')
        ->set('search', 'gesucht')
        ->assertSee('gesucht@firma.de')
        ->assertDontSee('anders@firma.de');
});

test('a lead can be deleted for a GDPR erasure request', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $lead = Lead::factory()->create();

    Livewire::actingAs($admin->fresh())->test('pages::admin.leads.index')
        ->call('confirmDelete', $lead->id)
        ->call('delete');

    expect(Lead::query()->count())->toBe(0);
});
