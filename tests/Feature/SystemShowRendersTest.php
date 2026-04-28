<?php

use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\System;
use App\Models\SystemPriority;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the system show page when priority and emergency_level are set', function () {
    // Regression: @php(...) mit nested parens — @php($priorityIcon =
    // SeverityIndicator::systemPriorityIcon((int) $system->priority->sort)) —
    // hat Blade-Kompilierung ab dieser Zeile kaputt gemacht. Ergebnis war ein
    // „Undefined variable $level"-Fehler weiter unten in derselben Datei,
    // weil @php / @endphp dahinter nicht mehr verarbeitet wurden.
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $priority = SystemPriority::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->orderBy('sort')
        ->first();
    $level = EmergencyLevel::factory()->for($company)->create(['name' => 'Kritisch', 'sort' => 1]);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Büro-Server',
        'category' => 'basisbetrieb',
        'system_priority_id' => $priority->id,
        'emergency_level_id' => $level->id,
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::systems.show', ['system' => $system])
        ->assertSee('Büro-Server')
        ->assertSee('Kritisch');
});
