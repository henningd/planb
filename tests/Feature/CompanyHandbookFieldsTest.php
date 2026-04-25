<?php

use App\Enums\Industry;
use App\Enums\KritisRelevance;
use App\Enums\LegalForm;
use App\Enums\Nis2Classification;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('company edit page saves all handbook fields', function () {
    $user = User::factory()->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->set('name', 'Acme GmbH')
        ->set('industry', Industry::Sonstiges->value)
        ->set('legal_form', LegalForm::GmbH->value)
        ->set('kritis_relevant', KritisRelevance::Pending->value)
        ->set('nis2_classification', Nis2Classification::Important->value)
        ->set('valid_from', '2026-01-01')
        ->set('employee_count', 42)
        ->set('locations_count', 3)
        ->set('cyber_insurance_deductible', '1.500 €')
        ->set('budget_it_lead', '500')
        ->set('budget_emergency_officer', '2000')
        ->set('budget_management', '20000')
        ->set('data_protection_authority_name', 'LfDI BW')
        ->set('data_protection_authority_phone', '0711 0000000')
        ->set('data_protection_authority_website', 'https://www.baden-wuerttemberg.datenschutz.de')
        ->call('save')
        ->assertHasNoErrors();

    $company = Company::where('team_id', $user->currentTeam->id)->firstOrFail();

    expect($company->legal_form)->toBe(LegalForm::GmbH)
        ->and($company->kritis_relevant)->toBe(KritisRelevance::Pending)
        ->and($company->nis2_classification)->toBe(Nis2Classification::Important)
        ->and($company->valid_from->toDateString())->toBe('2026-01-01')
        ->and($company->cyber_insurance_deductible)->toBe('1.500 €')
        ->and((float) $company->budget_it_lead)->toBe(500.0)
        ->and((float) $company->budget_emergency_officer)->toBe(2000.0)
        ->and((float) $company->budget_management)->toBe(20000.0)
        ->and($company->data_protection_authority_name)->toBe('LfDI BW')
        ->and($company->data_protection_authority_phone)->toBe('0711 0000000')
        ->and($company->data_protection_authority_website)->toBe('https://www.baden-wuerttemberg.datenschutz.de');
});

test('legal form invalid value is rejected', function () {
    $user = User::factory()->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->set('name', 'Acme')
        ->set('industry', Industry::Sonstiges->value)
        ->set('legal_form', 'unsinn')
        ->call('save')
        ->assertHasErrors(['legal_form']);
});
