<?php

use App\Enums\CommunicationAudience;
use App\Enums\CommunicationChannel;
use App\Mail\CommunicationTemplateMail;
use App\Models\CommunicationDispatch;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\Sms\SmsGatewayContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company, 2: CommunicationTemplate}
 */
function cockpitSendSetup(CommunicationChannel $channel): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Demo GmbH']);

    Employee::factory()->for($company)->create([
        'first_name' => 'Anna',
        'last_name' => 'Beispiel',
        'mobile_phone' => '+491701234567',
        'email' => 'anna@example.com',
    ]);

    $template = CommunicationTemplate::create([
        'company_id' => $company->id,
        'name' => 'Erstmeldung',
        'audience' => CommunicationAudience::Employees,
        'channel' => $channel,
        'subject' => $channel === CommunicationChannel::Email ? 'Vorfall bei {{ firma }}' : null,
        'body' => 'Achtung: Vorfall bei {{ firma }}.',
        'fallback' => null,
        'sort' => 0,
    ]);

    return [$user->fresh(), $company, $template];
}

test('the crisis cockpit can send an SMS template directly', function () {
    [$user, $company, $template] = cockpitSendSetup(CommunicationChannel::Sms);

    Http::fake(['gateway.seven.io/*' => Http::response(['success' => '100', 'messages' => [['id' => 'm1']]], 200)]);
    config(['services.sevenio.key' => 'key-test']);
    app()->forgetInstance(SmsGatewayContract::class);

    Livewire::actingAs($user)
        ->test('pages::incident-mode.index')
        ->call('openSmsSend', $template->id)
        ->call('sendSms')
        ->assertHasNoErrors();

    Http::assertSent(fn ($r) => $r['to'] === '+491701234567' && str_contains((string) $r['text'], 'Demo GmbH'));

    expect(DB::table('audit_log_entries')
        ->where('action', 'sms.sent')
        ->where('entity_id', $template->id)
        ->where('company_id', $company->id)
        ->exists())->toBeTrue();
});

test('the crisis cockpit can send an email template directly', function () {
    [$user, , $template] = cockpitSendSetup(CommunicationChannel::Email);

    Mail::fake();

    Livewire::actingAs($user)
        ->test('pages::incident-mode.index')
        ->call('openEmailSend', $template->id)
        ->call('sendEmail')
        ->assertHasNoErrors();

    Mail::assertSent(CommunicationTemplateMail::class);

    expect(CommunicationDispatch::where('communication_template_id', $template->id)->exists())->toBeTrue();
});
