<?php

use App\Enums\ScenarioRunMode;
use App\Jobs\SendAlarmChatPost;
use App\Models\Company;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Scenarios\CloseScenarioRun;
use App\Support\Scenarios\StartScenarioRun;
use App\Support\Settings\CompanySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

const CHAT_SLACK_URL = 'https://hooks.slack.com/services/T0/B0/alarm';
const CHAT_TEAMS_URL = 'https://example.webhook.office.com/webhookb2/alarm';

/**
 * @return array{0: User, 1: Company, 2: Scenario}
 */
function alarmChatSetup(bool $withUrls = true): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'IT-Totalausfall',
    ]);
    $scenario->steps()->create(['sort' => 1, 'title' => 'Server prüfen', 'responsible' => 'IT']);

    if ($withUrls) {
        CompanySetting::for($company)->set('slack_webhook_url', CHAT_SLACK_URL);
        CompanySetting::for($company)->set('teams_webhook_url', CHAT_TEAMS_URL);
    }

    return [$user, $company, $scenario];
}

function fakeChatWebhooks(int $status = 200): void
{
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', $status),
        '*.webhook.office.com/*' => Http::response('1', $status),
    ]);
}

it('posts an alarm card to both slack and teams when an emergency starts', function () {
    fakeChatWebhooks();
    [$user, , $scenario] = alarmChatSetup();

    app(StartScenarioRun::class)->handle($scenario, $user->id);

    Http::assertSent(function ($request) use ($user) {
        $payload = $request->data();

        return str_starts_with((string) $request->url(), 'https://hooks.slack.com/')
            && ($payload['blocks'][0]['text']['text'] ?? '') === 'Notfall gemeldet'
            && str_contains((string) ($payload['blocks'][1]['text']['text'] ?? ''), 'IT-Totalausfall')
            && str_contains((string) ($payload['blocks'][1]['text']['text'] ?? ''), 'Ausgelöst von: '.$user->name);
    });

    Http::assertSent(function ($request) {
        $payload = $request->data();

        return str_contains((string) $request->url(), '.webhook.office.com')
            && ($payload['@type'] ?? null) === 'MessageCard'
            && ($payload['title'] ?? null) === 'Notfall gemeldet'
            && str_contains((string) ($payload['text'] ?? ''), 'IT-Totalausfall');
    });
});

it('prefixes the chat card with ÜBUNG for drill runs', function () {
    fakeChatWebhooks();
    [$user, , $scenario] = alarmChatSetup();

    app(StartScenarioRun::class)->handle($scenario, $user->id, ScenarioRunMode::Drill);

    Http::assertSent(function ($request) {
        $payload = $request->data();

        return str_starts_with((string) $request->url(), 'https://hooks.slack.com/')
            && ($payload['blocks'][0]['text']['text'] ?? '') === 'ÜBUNG: Notfall gemeldet';
    });
});

it('posts an escalation card when a run stays unacknowledged', function () {
    fakeChatWebhooks();
    [, $company] = alarmChatSetup();

    $run = ScenarioRun::factory()->create([
        'company_id' => $company->id,
        'mode' => ScenarioRunMode::Real,
        'started_at' => now()->subMinutes(15),
    ]);

    $this->artisan('planb:escalate-unacknowledged-runs')->assertSuccessful();

    Http::assertSent(function ($request) use ($run) {
        $payload = $request->data();

        return str_starts_with((string) $request->url(), 'https://hooks.slack.com/')
            && ($payload['blocks'][0]['text']['text'] ?? '') === 'Notfall unbestätigt!'
            && str_contains((string) ($payload['blocks'][1]['text']['text'] ?? ''), 'Noch niemand hat den Notfall übernommen: '.$run->title);
    });

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), '.webhook.office.com')
        && ($request->data()['title'] ?? null) === 'Notfall unbestätigt!');
});

it('posts the outcome card when a run is closed', function (string $outcome, string $heading) {
    fakeChatWebhooks();
    [$user, $company] = alarmChatSetup();

    $run = ScenarioRun::factory()->create([
        'company_id' => $company->id,
        'mode' => ScenarioRunMode::Real,
        'title' => 'IT-Totalausfall · 06.07.2026 10:00',
        'started_at' => now()->subHour(),
    ]);

    app(CloseScenarioRun::class)->handle($run, $outcome, $user->id);

    Http::assertSent(function ($request) use ($heading, $run) {
        $payload = $request->data();

        return str_starts_with((string) $request->url(), 'https://hooks.slack.com/')
            && ($payload['blocks'][0]['text']['text'] ?? '') === $heading
            && str_contains((string) ($payload['blocks'][1]['text']['text'] ?? ''), $run->title);
    });
})->with([
    'beendet' => ['completed', 'Notfall beendet'],
    'abgebrochen' => ['aborted', 'Notfall abgebrochen'],
]);

it('does not post when the chat_alarm_posts_enabled setting is off', function () {
    fakeChatWebhooks();
    [$user, $company, $scenario] = alarmChatSetup();
    CompanySetting::for($company)->set('chat_alarm_posts_enabled', false);

    app(StartScenarioRun::class)->handle($scenario, $user->id);

    Http::assertNothingSent();
});

it('does not post when no webhook url is configured', function () {
    fakeChatWebhooks();
    [$user, , $scenario] = alarmChatSetup(withUrls: false);

    app(StartScenarioRun::class)->handle($scenario, $user->id);

    Http::assertNothingSent();
});

it('never blocks the alarm when the webhooks fail', function () {
    fakeChatWebhooks(status: 500);
    [$user, $company, $scenario] = alarmChatSetup();

    $run = app(StartScenarioRun::class)->handle($scenario, $user->id);

    expect($run->exists)->toBeTrue()
        ->and(ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->count())->toBe(1);
});

it('sends the chat post as a queued job', function () {
    Queue::fake();
    [$user, , $scenario] = alarmChatSetup();

    app(StartScenarioRun::class)->handle($scenario, $user->id);

    Queue::assertPushed(SendAlarmChatPost::class, 1);
});
