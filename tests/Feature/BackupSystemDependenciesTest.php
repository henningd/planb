<?php

use App\Models\Company;
use App\Models\System;
use App\Models\User;
use App\Support\Backup\Exporter;
use App\Support\Backup\Importer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('importing system dependencies never selects the non-existent id column', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    $a = System::create(['name' => 'A', 'category' => 'basisbetrieb']);
    $b = System::create(['name' => 'B', 'category' => 'basisbetrieb']);
    $a->dependencies()->attach($b->id, ['sort' => 0]);

    $payload = Exporter::export($company, ['systems', 'system_dependencies']);

    DB::table('system_dependencies')->delete();

    // SQLite toleriert „select id" auf einer Tabelle ohne id-Spalte; MySQL nicht
    // (SQLSTATE 42S22). Statt auf die DB zu vertrauen, prüfen wir die abgesetzten
    // Queries: Es darf kein SELECT der id-Spalte auf system_dependencies geben.
    DB::flushQueryLog();
    DB::enableQueryLog();

    Importer::import($company, $payload, ['system_dependencies']);

    $log = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    $selectsIdFromPivot = $log->contains(function (string $query): bool {
        $q = strtolower($query);

        return str_starts_with(ltrim($q), 'select')
            && str_contains($q, 'system_dependencies')
            && str_contains($q, 'id');
    });

    expect($selectsIdFromPivot)->toBeFalse()
        ->and(
            DB::table('system_dependencies')
                ->where('system_id', $a->id)
                ->where('depends_on_system_id', $b->id)
                ->exists()
        )->toBeTrue();
});
