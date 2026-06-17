<?php

use App\Models\Company;
use App\Models\User;
use App\Support\Backup\Importer;
use Database\Seeders\IndustryTemplates\Contract;
use Database\Seeders\IndustryTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Liefert alle im Seeder registrierten Template-Klassen als Dataset, damit
 * jedes Branchen-Template einzeln gegen einen frischen Mandanten angewendet
 * wird. Neue Templates werden automatisch mitgetestet, sobald sie im Seeder
 * eingetragen sind.
 */
dataset('registered_templates', function () {
    $seeder = new IndustryTemplatesSeeder;
    $property = new ReflectionProperty($seeder, 'templates');
    $property->setAccessible(true);

    /** @var list<class-string<Contract>> $classes */
    $classes = $property->getValue($seeder);

    return collect($classes)
        ->filter(fn (string $class): bool => class_exists($class))
        ->mapWithKeys(fn (string $class): array => [class_basename($class) => [$class]])
        ->all();
});

test('template payload applies cleanly to a fresh company', function (string $class) {
    /** @var Contract $template */
    $template = new $class;
    $payload = $template->payload();

    expect($payload['areas'] ?? null)->toBeArray()->not->toBeEmpty();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Importer::import(
        $company,
        $payload,
        array_keys($payload['areas']),
        regenerateIds: true,
    );

    expect(DB::table('locations')->where('company_id', $company->id)->count())->toBeGreaterThan(0)
        ->and(DB::table('systems')->where('company_id', $company->id)->count())->toBeGreaterThan(0)
        ->and(DB::table('employees')->where('company_id', $company->id)->count())->toBeGreaterThan(0);
})->with('registered_templates');

test('template declares a name and a rich description', function (string $class) {
    /** @var Contract $template */
    $template = new $class;

    expect($template->name())->toBeString()->not->toBe('')
        ->and($template->description())->toBeString()
        ->and(mb_strlen($template->description()))->toBeGreaterThan(40)
        ->and($template->sort())->toBeInt();
})->with('registered_templates');
