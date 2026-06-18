<?php

use App\Models\Company;
use App\Models\User;
use App\Support\Backup\Importer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function importCompanyPayload(Company $company, string $mode): void
{
    Importer::import(
        $company,
        ['areas' => ['company' => [['name' => 'Template GmbH', 'review_cycle_months' => 99]]]],
        ['company'],
        regenerateIds: false,
        companyMode: $mode,
    );
}

function companyName(Company $company): string
{
    return (string) DB::table('companies')->where('id', $company->id)->value('name');
}

function companyReviewCycle(Company $company): int
{
    return (int) DB::table('companies')->where('id', $company->id)->value('review_cycle_months');
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->for($this->user->currentTeam)->create([
        'name' => 'Echter Kunde GmbH',
        'review_cycle_months' => 12,
    ]);
});

test('keep_name applies default fields but preserves the customer name', function () {
    importCompanyPayload($this->company, 'keep_name');

    expect(companyName($this->company))->toBe('Echter Kunde GmbH')
        ->and(companyReviewCycle($this->company))->toBe(99);
});

test('skip leaves the company profile completely untouched', function () {
    importCompanyPayload($this->company, 'skip');

    expect(companyName($this->company))->toBe('Echter Kunde GmbH')
        ->and(companyReviewCycle($this->company))->toBe(12);
});

test('overwrite replaces the company name', function () {
    importCompanyPayload($this->company, 'overwrite');

    expect(companyName($this->company))->toBe('Template GmbH')
        ->and(companyReviewCycle($this->company))->toBe(99);
});
