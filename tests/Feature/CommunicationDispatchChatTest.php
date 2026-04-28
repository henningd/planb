<?php

use App\Enums\CommunicationAudience;
use App\Enums\CommunicationChannel;
use App\Models\CommunicationDispatch;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\User;
use App\Support\Settings\CompanySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function chatSetup(CommunicationChannel $channel): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $template = CommunicationTemplate::create([
        'company_id' => $company->id,
        'name' => 'Lage-Update',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => $channel->value,
        'subject' => 'Lage bei {{firma}}',
        'body' => 'Wir haben einen aktiven Vorfall bei {{firma}}.',
        'sort' => 1,
    ]);

    return [$user, $company, $template];
}

it('posts a Slack template to the configured webhook URL', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    [$user, $company, $template] = chatSetup(CommunicationChannel::Slack);
    CompanySetting::for($company)->set('slack_webhook_url', 'https://hooks.slack.com/services/T0/B0/abc');

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('sendChat', $template->id);

    Http::assertSent(function ($request) use ($company) {
        $payload = $request->data();

        return str_starts_with((string) $request->url(), 'https://hooks.slack.com/')
            && isset($payload['blocks'])
            && str_contains((string) $payload['blocks'][0]['text']['text'], $company->name);
    });

    $dispatch = CommunicationDispatch::first();
    expect($dispatch->channel)->toBe('slack');
    expect($dispatch->success_count)->toBe(1);
    expect($dispatch->recipients->first()->status)->toBe('sent');
});

it('posts a Teams template using a MessageCard payload', function () {
    Http::fake([
        '*.webhook.office.com/*' => Http::response('1', 200),
    ]);

    [$user, $company, $template] = chatSetup(CommunicationChannel::Teams);
    CompanySetting::for($company)->set('teams_webhook_url', 'https://example.webhook.office.com/webhookb2/abc');

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('sendChat', $template->id);

    Http::assertSent(function ($request) use ($company) {
        $payload = $request->data();

        return str_contains((string) $request->url(), '.webhook.office.com')
            && ($payload['@type'] ?? null) === 'MessageCard'
            && str_contains((string) ($payload['text'] ?? ''), $company->name);
    });

    expect(CommunicationDispatch::first()->channel)->toBe('teams');
});

it('warns if the webhook URL is not configured for the channel', function () {
    Http::fake();

    [$user, , $template] = chatSetup(CommunicationChannel::Slack);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('sendChat', $template->id);

    Http::assertNothingSent();
    expect(CommunicationDispatch::count())->toBe(0);
});

it('persists a failed dispatch when the webhook returns 4xx', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('invalid_payload', 400),
    ]);

    [$user, $company, $template] = chatSetup(CommunicationChannel::Slack);
    CompanySetting::for($company)->set('slack_webhook_url', 'https://hooks.slack.com/services/T0/B0/abc');

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('sendChat', $template->id);

    $dispatch = CommunicationDispatch::first();
    expect($dispatch)->not->toBeNull();
    expect($dispatch->failed_count)->toBe(1);
    expect($dispatch->success_count)->toBe(0);
    expect($dispatch->recipients->first()->status)->toBe('failed');
    expect($dispatch->recipients->first()->error_message)->toContain('400');
});

it('rejects a non-chat template through sendChat', function () {
    Http::fake();

    [$user] = chatSetup(CommunicationChannel::Slack);
    $smsTemplate = CommunicationTemplate::create([
        'company_id' => Company::first()->id,
        'name' => 'X',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => CommunicationChannel::Sms->value,
        'subject' => null,
        'body' => 'kurz',
        'sort' => 1,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('sendChat', $smsTemplate->id);

    Http::assertNothingSent();
    expect(CommunicationDispatch::count())->toBe(0);
});
