<?php

use App\Enums\Industry;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('an invalid stored industry value falls back to Sonstiges instead of crashing', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    // Müll-/Legacy-Wert direkt in die DB schreiben (umgeht den Cast).
    DB::table('companies')->where('id', $company->id)->update(['industry' => 'Software / IT-Dienstleistungen']);

    expect(Company::find($company->id)->industry)->toBe(Industry::Sonstiges);
});

test('a valid industry value is still cast normally', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['industry' => Industry::Handwerk]);

    expect($company->fresh()->industry)->toBe(Industry::Handwerk);
});

test('setting an Industry enum stores its backing value', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $company->industry = Industry::Produktion;
    $company->save();

    expect(DB::table('companies')->where('id', $company->id)->value('industry'))->toBe('produktion');
});
