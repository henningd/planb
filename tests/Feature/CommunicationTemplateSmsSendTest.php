<?php

use App\Enums\CommunicationAudience;
use App\Enums\CommunicationChannel;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\Sms\SmsGatewayContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function smsTestSetup(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Demo GmbH']);

    $with = Employee::factory()->for($company)->create([
        'first_name' => 'Anna',
        'last_name' => 'Beispiel',
        'mobile_phone' => '+491701234567',
    ]);
    Employee::factory()->for($company)->create([
        'first_name' => 'Bernd',
        'last_name' => 'OhneNummer',
        'mobile_phone' => null,
    ]);

    $template = CommunicationTemplate::create([
        'company_id' => $company->id,
        'name' => 'Erstmeldung',
        'audience' => CommunicationAudience::Employees,
        'channel' => CommunicationChannel::Sms,
        'subject' => null,
        'body' => 'Achtung: Vorfall bei {{ firma }}.',
        'fallback' => null,
        'sort' => 0,
    ]);

    return [$user->fresh(), $company, $template, $with];
}

test('opening sms send modal prefills only employees with a mobile number', function () {
    [$user, , $template, $withMobile] = smsTestSetup();

    $component = Livewire\Livewire::actingAs($user)
        ->test('pages::communication-templates.index')
        ->call('openSmsSend', $template->id);

    expect($component->get('smsRecipients'))->toEqual([$withMobile->id]);
});

test('sendSms calls gateway for each recipient and resolves placeholders', function () {
    [$user, , $template, $withMobile] = smsTestSetup();

    Http::fake([
        'gateway.seven.io/*' => Http::response(['success' => '100', 'messages' => [['id' => 'm1']]], 200),
    ]);
    config(['services.sevenio.key' => 'key-test', 'services.sevenio.sender' => 'PlanB']);
    app()->forgetInstance(SmsGatewayContract::class);

    Livewire\Livewire::actingAs($user)
        ->test('pages::communication-templates.index')
        ->call('openSmsSend', $template->id)
        ->call('sendSms')
        ->assertHasNoErrors();

    Http::assertSent(function ($request) {
        return $request['to'] === '+491701234567'
            && str_contains((string) $request['text'], 'Demo GmbH');
    });
});

test('sendSms records an audit log entry with sent counts', function () {
    [$user, $company, $template] = smsTestSetup();

    Http::fake([
        'gateway.seven.io/*' => Http::response(['success' => '100', 'messages' => [['id' => 'm1']]], 200),
    ]);
    config(['services.sevenio.key' => 'key-test']);
    app()->forgetInstance(SmsGatewayContract::class);

    Livewire\Livewire::actingAs($user)
        ->test('pages::communication-templates.index')
        ->call('openSmsSend', $template->id)
        ->call('sendSms');

    $entry = DB::table('audit_log_entries')
        ->where('action', 'sms.sent')
        ->where('entity_id', $template->id)
        ->first();

    expect($entry)->not->toBeNull();
    expect($entry->company_id)->toBe($company->id);
    expect(json_decode($entry->changes, true)['sent'])->toBe(1);
});

test('sendSms surfaces provider failures per recipient', function () {
    [$user, , $template] = smsTestSetup();

    Http::fake([
        'gateway.seven.io/*' => Http::response(['success' => '301'], 200),
    ]);
    config(['services.sevenio.key' => 'key-test']);
    app()->forgetInstance(SmsGatewayContract::class);

    $component = Livewire\Livewire::actingAs($user)
        ->test('pages::communication-templates.index')
        ->call('openSmsSend', $template->id)
        ->call('sendSms');

    $results = $component->get('smsResults');
    expect($results)->toHaveCount(1);
    expect($results[0]['success'])->toBeFalse();
    expect($results[0]['error'])->toContain('301');
});

test('sendSms refuses non-sms templates', function () {
    [$user, , $smsTemplate] = smsTestSetup();

    $email = CommunicationTemplate::create([
        'company_id' => $smsTemplate->company_id,
        'name' => 'Mail-Vorlage',
        'audience' => CommunicationAudience::Customers,
        'channel' => CommunicationChannel::Email,
        'body' => 'Nur Mail',
        'sort' => 0,
    ]);

    Http::fake();
    config(['services.sevenio.key' => 'key-test']);
    app()->forgetInstance(SmsGatewayContract::class);

    Livewire\Livewire::actingAs($user)
        ->test('pages::communication-templates.index')
        ->set('smsTemplateId', $email->id)
        ->set('smsRecipients', [Employee::query()->whereNotNull('mobile_phone')->first()->id])
        ->call('sendSms');

    Http::assertNothingSent();
});
