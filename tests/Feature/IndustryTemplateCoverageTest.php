<?php

use App\Enums\Industry;
use App\Support\IndustryTemplates;

test('every industry has a non-empty system template', function () {
    $catalog = IndustryTemplates::catalog();

    foreach (Industry::cases() as $industry) {
        expect($catalog)->toHaveKey($industry->value)
            ->and(IndustryTemplates::systemsFor($industry->value))->not->toBeEmpty();
    }
});

test('defaultFor maps each industry to its own template and null to the generic one', function () {
    foreach (Industry::cases() as $industry) {
        expect(IndustryTemplates::defaultFor($industry))->toBe($industry->value);
    }

    expect(IndustryTemplates::defaultFor(null))->toBe(Industry::Sonstiges->value);
});

test('all template systems use a valid category and a recognised priority', function () {
    $validPriorities = ['Kritisch', 'Hoch', 'Normal'];

    foreach (IndustryTemplates::TEMPLATES as $key => $tpl) {
        foreach ($tpl['systems'] as $system) {
            expect(IndustryTemplates::categoryIsValid($system['category']))->toBeTrue("[$key] {$system['name']}: ungültige Kategorie")
                ->and($validPriorities)->toContain($system['priority'])
                ->and($system['name'])->toBeString()->not->toBe('');
        }
    }
});

test('the new public-sector and generic templates are reasonably rich', function () {
    $public = IndustryTemplates::systemsFor(Industry::OeffentlicheEinrichtung->value);
    $generic = IndustryTemplates::systemsFor(Industry::Sonstiges->value);

    expect(count($public))->toBeGreaterThanOrEqual(12)
        ->and(count($generic))->toBeGreaterThanOrEqual(12)
        ->and(collect($public)->pluck('name'))->toContain('Fachverfahren')
        ->and(collect($public)->pluck('name'))->toContain('Bürgerportal / Online-Dienste (OZG)');
});

test('the public-sector template ships the five municipal scenarios', function () {
    $scenarios = IndustryTemplates::scenariosFor(Industry::OeffentlicheEinrichtung->value);

    expect($scenarios)->toHaveCount(5)
        ->and(collect($scenarios)->pluck('name')->all())->toBe([
            'Ransomware / Cyberangriff auf die Verwaltung',
            'Stromausfall im Rathaus',
            'Ausfall Fachverfahren / Notbetrieb Bürgerbüro',
            'Hochwasser / Unwetter am Verwaltungsstandort',
            'Evakuierung eines Verwaltungsgebäudes',
        ]);
});

test('municipal template scenarios are actionable playbooks with roles', function () {
    foreach (IndustryTemplates::scenariosFor(Industry::OeffentlicheEinrichtung->value) as $scenario) {
        expect($scenario['description'])->toBeString()->not->toBe('')
            ->and($scenario['trigger'])->toBeString()->not->toBe('')
            ->and(count($scenario['steps']))->toBeGreaterThanOrEqual(6, "{$scenario['name']}: zu wenige Schritte")
            ->and(count($scenario['steps']))->toBeLessThanOrEqual(10, "{$scenario['name']}: zu viele Schritte");

        foreach ($scenario['steps'] as $step) {
            expect($step['title'])->toBeString()->not->toBe('')
                ->and($step['description'])->toBeString()->not->toBe('')
                ->and($step['responsible'])->toBeString()->not->toBe('');
        }

        $roles = collect($scenario['steps'])->pluck('responsible')->unique();
        expect($roles->count())->toBeGreaterThanOrEqual(2, "{$scenario['name']}: nur eine Rolle");
    }

    $allRoles = collect(IndustryTemplates::scenariosFor(Industry::OeffentlicheEinrichtung->value))
        ->flatMap(fn (array $scenario) => collect($scenario['steps'])->pluck('responsible'))
        ->unique();

    expect($allRoles)->toContain('Leitung')
        ->toContain('IT')
        ->toContain('Kommunikation');
});

test('scenariosFor returns an empty list for templates without scenarios and the catalog counts them', function () {
    expect(IndustryTemplates::scenariosFor(Industry::Handwerk->value))->toBe([])
        ->and(IndustryTemplates::scenariosFor('unbekannt'))->toBe([]);

    $catalog = IndustryTemplates::catalog();

    expect($catalog[Industry::OeffentlicheEinrichtung->value]['scenario_count'])->toBe(5)
        ->and($catalog[Industry::Handwerk->value]['scenario_count'])->toBe(0);
});
