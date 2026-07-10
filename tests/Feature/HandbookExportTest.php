<?php

use App\Models\Company;
use App\Models\User;
use App\Support\HandbookData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company}
 */
function exportActingUser(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

test('all three export types render as pdf downloads', function () {
    [$user] = exportActingUser();

    foreach (['handbook-export.ernstfall', 'handbook-export.audit', 'handbook-export.full'] as $name) {
        $response = $this->actingAs($user)->get(route($name));

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('application/pdf');
        expect($response->headers->get('content-disposition'))->toContain('attachment');
        expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
    }
});

test('the ernstfall export omits the governance chapters', function () {
    [, $company] = exportActingUser();

    $base = HandbookData::forCompany($company);
    $base['version'] = $company->currentHandbookVersion();
    $base['showPdfHashFooter'] = false;
    $base['isPdf'] = true;

    $full = view('handbook-print', [...$base, 'exportMode' => 'full'])->render();
    $ernstfall = view('handbook-print', [...$base, 'exportMode' => 'ernstfall'])->render();

    // Kapitel 13 (Pflege/Testplan) rendert immer — nur der Modus entscheidet.
    expect($full)->toContain('13. Pflege und Testplan')
        ->and($ernstfall)->not->toContain('13. Pflege und Testplan')
        ->and($ernstfall)->not->toContain('14. Offene Punkte');
});
