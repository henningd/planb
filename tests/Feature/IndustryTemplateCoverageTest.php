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
