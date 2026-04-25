<?php

use App\Enums\CommunicationAudience;
use App\Enums\CommunicationChannel;
use App\Enums\CrisisRole;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\TemplatePlaceholders;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('placeholder resolver replaces known tokens and keeps unknown ones', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Musterfirma GmbH']);

    Employee::factory()->for($company)->withCrisisRole(CrisisRole::Management)->create([
        'first_name' => 'Erika', 'last_name' => 'Meier',
    ]);

    $resolved = TemplatePlaceholders::resolve(
        'Hallo, {{ firma }} meldet einen Vorfall. Kontakt: {{ ansprechpartner }}. {{ unbekannt }}',
        $company,
    );

    expect($resolved)
        ->toContain('Musterfirma GmbH')
        ->toContain('Erika Meier')
        ->toContain('{{ unbekannt }}');
});

test('overrides beat company defaults', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Musterfirma GmbH']);

    $resolved = TemplatePlaceholders::resolve(
        'Vorfall: {{ vorfall }}',
        $company,
        ['vorfall' => 'Ransomware auf Server #3'],
    );

    expect($resolved)->toBe('Vorfall: Ransomware auf Server #3');
});

test('creating a communication template via the livewire page stores it scoped to company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->set('name', 'Mitarbeiter-Erstmeldung SMS')
        ->set('audience', CommunicationAudience::Employees->value)
        ->set('channel', CommunicationChannel::Sms->value)
        ->set('body', 'Bei {{ firma }} Vorfall – bitte Bürostart verschieben.')
        ->call('save')
        ->assertHasNoErrors();

    $template = CommunicationTemplate::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->first();

    expect($template)->not->toBeNull()
        ->and($template->name)->toBe('Mitarbeiter-Erstmeldung SMS')
        ->and($template->audience)->toBe(CommunicationAudience::Employees)
        ->and($template->channel)->toBe(CommunicationChannel::Sms);
});

test('communication templates are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $other = Company::factory()->for(Team::factory())->create();

    CommunicationTemplate::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Eigene',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => CommunicationChannel::Email->value,
        'body' => 'Text',
    ]);

    CommunicationTemplate::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $other->id,
        'name' => 'Fremde',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => CommunicationChannel::Email->value,
        'body' => 'Fremd',
    ]);

    $this->actingAs($user->fresh());

    expect(CommunicationTemplate::pluck('name')->all())->toBe(['Eigene']);
});

test('templates page renders grouped by audience', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    CommunicationTemplate::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Kunden-Hinweis',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => CommunicationChannel::Email->value,
        'subject' => 'Kurzfristige Einschränkung',
        'body' => 'Sehr geehrte Kunden, bei {{ firma }} …',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('communication-templates.index'))
        ->assertOk()
        ->assertSee('Kommunikations-Vorlagen')
        ->assertSee('Kunden-Hinweis')
        ->assertSee('Mitarbeiter')
        ->assertSee('Kunden')
        ->assertSee('Presse');
});

test('preview resolves placeholders using the current company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Bäckerei Beispiel']);

    $template = CommunicationTemplate::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Aushang',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => CommunicationChannel::Notice->value,
        'body' => '{{ firma }} ist am {{ datum }} vorübergehend geschlossen.',
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('openPreview', $template->id);

    $preview = $component->instance()->preview;

    expect($preview)->not->toBeNull()
        ->and($preview['body'])->toContain('Bäckerei Beispiel')
        ->and($preview['body'])->not->toContain('{{ firma }}');
});
