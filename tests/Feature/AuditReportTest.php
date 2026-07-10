<?php

use App\Enums\ProcessCriticality;
use App\Models\BusinessProcess;
use App\Models\Company;
use App\Models\OpenItem;
use App\Models\PreventiveMeasure;
use App\Models\Risk;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the audit report renders processes with their linked governance items', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Pflegedokumentation',
        'category' => 'geschaeftsbetrieb',
    ]);

    $process = BusinessProcess::factory()->create([
        'company_id' => $company->id,
        'name' => 'Medikamentengabe',
        'criticality' => ProcessCriticality::Existenzkritisch,
        'rto_minutes' => 240,
        'next_review_at' => '2026-12-01',
    ]);
    $process->systems()->attach($system->id);

    Risk::factory()->create([
        'company_id' => $company->id,
        'business_process_id' => $process->id,
        'title' => 'Ransomware-Befall Pflegeserver',
    ]);
    PreventiveMeasure::factory()->create([
        'company_id' => $company->id,
        'system_id' => $system->id,
        'business_process_id' => $process->id,
        'title' => 'Backup-Restore-Test',
    ]);
    OpenItem::factory()->create([
        'company_id' => $company->id,
        'business_process_id' => $process->id,
        'title' => 'Break-Glass-Zugang ungetestet',
    ]);

    // Nicht zugeordnetes Risiko landet im Anhang.
    Risk::factory()->create([
        'company_id' => $company->id,
        'title' => 'Freistehendes Risiko',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('audit-report.print'))
        ->assertOk()
        ->assertSee('Audit-/Governance-Bericht')
        ->assertSee('Medikamentengabe')
        ->assertSee('Pflegedokumentation')
        ->assertSee('Ransomware-Befall Pflegeserver')
        ->assertSee('Backup-Restore-Test')
        ->assertSee('Break-Glass-Zugang ungetestet')
        ->assertSee('Anhang: Nicht zugeordnet')
        ->assertSee('Freistehendes Risiko');
});

test('the audit report requires a company', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('audit-report.print'))
        ->assertNotFound();
});
