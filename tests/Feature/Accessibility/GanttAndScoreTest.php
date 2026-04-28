<?php

use App\Models\Company;
use App\Models\ComplianceScoreSnapshot;
use App\Models\EmergencyLevel;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Graph\RecoveryTimelineBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * WCAG 2.1 AA, Erfolgskriterium 1.4.1 (Use of Color):
 * Information darf nicht ausschließlich durch Farbe vermittelt werden.
 *
 * Diese Tests sichern, dass Recovery-Gantt-Balken und Compliance-Score
 * zusätzlich zur Farbe ein Icon und/oder eine Text-Beschriftung tragen.
 */
test('gantt rendert je Balken ein Status-Icon zusätzlich zur Farbe', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $critical = EmergencyLevel::factory()->for($company)->create(['name' => 'Kritisch', 'sort' => 1]);
    $important = EmergencyLevel::factory()->for($company)->create(['name' => 'Wichtig', 'sort' => 2]);
    $medium = EmergencyLevel::factory()->for($company)->create(['name' => 'Mittel', 'sort' => 3]);
    $low = EmergencyLevel::factory()->for($company)->create(['name' => 'Gering', 'sort' => 4]);

    System::factory()->for($company)->create([
        'name' => 'Webshop',
        'rto_minutes' => 30,
        'emergency_level_id' => $critical->id,
    ]);
    System::factory()->for($company)->create([
        'name' => 'Buchhaltung',
        'rto_minutes' => 60,
        'emergency_level_id' => $important->id,
    ]);
    System::factory()->for($company)->create([
        'name' => 'Wiki',
        'rto_minutes' => 120,
        'emergency_level_id' => $medium->id,
    ]);
    System::factory()->for($company)->create([
        'name' => 'Archiv',
        'rto_minutes' => 240,
        'emergency_level_id' => $low->id,
    ]);

    $response = $this->actingAs($user->fresh())
        ->get(route('recovery-gantt.index', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
    $html = $response->getContent();

    // Mindestens ein Heroicon je Balken-Reihe (Sort 1..4) — sichtbar durch
    // die data-Marker, die direkt am Icon-Wrapper hängen.
    expect($html)->toContain('data-gantt-icon="shield-exclamation"');
    expect($html)->toContain('data-gantt-icon="exclamation-triangle"');
    expect($html)->toContain('data-gantt-icon="shield-check"');
    expect($html)->toContain('data-gantt-icon="check-circle"');

    // Jede Balken-Zeile trägt zusätzlich ihren Stufen-Sort und ein Text-Label.
    expect($html)->toContain('data-level-sort="1"');
    expect($html)->toContain('data-level-sort="4"');
    expect($html)->toContain('data-level-label="Stufe 1 (kritisch)"');
});

test('gantt-Balken ohne RTO trägt ein Clock-Icon (Default-Anzeige)', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    System::factory()->for($company)->create([
        'name' => 'OhneRto',
        'rto_minutes' => null,
    ]);

    $response = $this->actingAs($user->fresh())
        ->get(route('recovery-gantt.index', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
    $html = $response->getContent();
    // Bei fehlender RTO erscheint in der Balken-Mitte das Clock-Icon plus
    // die Default-60-min-Text-Note in der Legende.
    expect($html)->toContain('data-bar-icon="clock"');
    expect($html)->toContain('data-rto-missing="1"');
    expect($html)->toContain('RTO-Vorgabe (60 min angenommen)');
});

test('gantt-Legende zeigt Farbe + Icon + Text-Label kombiniert', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $level = EmergencyLevel::factory()->for($company)->create(['name' => 'Kritisch', 'sort' => 1]);
    System::factory()->for($company)->create([
        'name' => 'Hauptsystem',
        'rto_minutes' => 30,
        'emergency_level_id' => $level->id,
    ]);

    $response = $this->actingAs($user->fresh())
        ->get(route('recovery-gantt.index', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk()
        ->assertSeeText('Stufe 1 (kritisch)')
        ->assertSeeText('Stufe 2 (wichtig)')
        ->assertSeeText('Stufe 3 (mittel)')
        ->assertSeeText('Stufe 4 (gering)');

    $html = $response->getContent();
    // Legende kombiniert für jede Stufe Farbe + Icon + Text-Label.
    expect($html)->toContain('data-testid="gantt-legend"');
    expect($html)->toContain('data-legend-icon="shield-exclamation"');
    expect($html)->toContain('data-legend-icon="exclamation-triangle"');
    expect($html)->toContain('data-legend-icon="shield-check"');
    expect($html)->toContain('data-legend-icon="check-circle"');
    expect($html)->toContain('data-legend-icon="clock"');
    // Farb-Hex-Werte als zweite Codierung pro Stufe.
    expect($html)->toContain('#f43f5e');
    expect($html)->toContain('#10b981');
});

test('TimelineBuilder liefert Icon und Label je Eintrag', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    $level = EmergencyLevel::factory()->for($company)->create(['name' => 'Kritisch', 'sort' => 1]);
    System::factory()->for($company)->create([
        'name' => 'KernSystem',
        'rto_minutes' => 30,
        'emergency_level_id' => $level->id,
    ]);

    $timeline = RecoveryTimelineBuilder::build($company);

    expect($timeline['entries'])->toHaveCount(1);
    expect($timeline['entries'][0])->toHaveKeys(['level_color', 'level_icon', 'level_label']);
    expect($timeline['entries'][0]['level_icon'])->toBe('shield-exclamation');
    expect($timeline['entries'][0]['level_label'])->toBe('Stufe 1 (kritisch)');
});

test('Compliance-Score zeigt Klassifizierungs-Wort-Label zusätzlich zur Zahl', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $response = $this->actingAs($user->fresh())
        ->get(route('compliance.index', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk()
        // Reifegrad-Block zeigt Score (Zahl) ...
        ->assertSee('/ 100', false)
        // ... und das Klassifizierungs-Wort-Label direkt darunter.
        ->assertSee('data-testid="readiness-label"', false)
        // Eine leere Company landet bei 'Nicht vorbereitet' (Score < 25).
        ->assertSeeText('Nicht vorbereitet');
});

test('Compliance-Trend zeigt Pfeil-Icon und Vorzeichen-Text bei steigender Tendenz', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'snapshot_date' => today()->subDays(7)->toDateString(),
        'score' => 30,
        'breakdown' => [['key' => 'demo', 'status' => 'fail', 'score' => 0]],
    ]);
    ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'snapshot_date' => today()->toDateString(),
        'score' => 55,
        'breakdown' => [['key' => 'demo', 'status' => 'partial', 'score' => 50]],
    ]);

    $response = $this->actingAs($user->fresh())
        ->get(route('compliance.index', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
    $html = $response->getContent();

    // Pfeil-Icon (Heroicon) für eine Aufwärts-Tendenz
    expect($html)->toContain('data-trend-icon="arrow-trending-up"');
    // Vorzeichen-Text mit Punkt-Einheit
    expect($html)->toContain('+25');
    expect($html)->toContain('Pkt.');
    // Test-Hook für die Wochen-Delta-Komponente
    expect($html)->toContain('data-testid="week-delta"');
});

test('RecoveryTimelineBuilder::position skaliert log und linear korrekt', function () {
    $max = 4320; // 72 h

    expect(RecoveryTimelineBuilder::position(0, $max, 'linear'))->toBe(0.0);
    expect(RecoveryTimelineBuilder::position($max, $max, 'linear'))->toBe(100.0);
    expect(RecoveryTimelineBuilder::position(0, $max, 'log'))->toBe(0.0);
    expect(RecoveryTimelineBuilder::position($max, $max, 'log'))->toBe(100.0);

    $shortLinear = RecoveryTimelineBuilder::position(15, $max, 'linear');
    $shortLog = RecoveryTimelineBuilder::position(15, $max, 'log');

    // Eine 15-min-Position muss im Log-Modus deutlich weiter rechts liegen
    // als im Linear-Modus — das ist der ganze Sinn der Skalierung,
    // damit schmale Bars sichtbar bleiben.
    expect($shortLog)->toBeGreaterThan($shortLinear * 10);
});

test('Recovery-Gantt rendert Skalen-Toggle (Logarithmisch / Linear)', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $level = EmergencyLevel::factory()->for($company)->create(['name' => 'Stufe 1', 'sort' => 1]);
    System::factory()->for($company)->create([
        'name' => 'Webshop', 'rto_minutes' => 30, 'emergency_level_id' => $level->id,
    ]);

    $response = $this->actingAs($user->fresh())
        ->get(route('recovery-gantt.index', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk()
        ->assertSeeText('Logarithmisch')
        ->assertSeeText('Linear')
        ->assertSee("wire:click=\"setScaleMode('log')\"", false)
        ->assertSee("wire:click=\"setScaleMode('linear')\"", false);
});

test('Compliance-Trend zeigt Abwärts-Pfeil bei sinkender Tendenz', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'snapshot_date' => today()->subDays(7)->toDateString(),
        'score' => 80,
        'breakdown' => [['key' => 'demo', 'status' => 'pass', 'score' => 100]],
    ]);
    ComplianceScoreSnapshot::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'snapshot_date' => today()->toDateString(),
        'score' => 60,
        'breakdown' => [['key' => 'demo', 'status' => 'partial', 'score' => 50]],
    ]);

    $response = $this->actingAs($user->fresh())
        ->get(route('compliance.index', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('data-trend-icon="arrow-trending-down"');
    expect($html)->toContain('-20');
});
