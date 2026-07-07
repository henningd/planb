<?php

use App\Models\ApiToken;
use App\Models\Company;
use App\Models\MobileDevice;
use App\Models\Scenario;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('e2e: a prometheus auto-alarm actually reaches the push sender (sync queue, no fakes)', function () {
    config(['queue.default' => 'sync']);

    $sent = [];
    app()->bind(PushSender::class, function () use (&$sent) {
        return new class($sent) implements PushSender
        {
            public function __construct(private array &$sent) {}

            public function send(array $tokens, array $data, ?string $title = null, ?string $body = null): array
            {
                $this->sent[] = ['tokens' => $tokens, 'data' => $data, 'title' => $title];

                return [];
            }
        };
    });

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'IT-Totalausfall',
    ]);
    System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Notfallhandbuch Server',
        'category' => 'geschaeftsbetrieb',
        'monitoring_keys' => ['srv-prod-01'],
        'emergency_scenario_id' => $scenario->id,
    ]);
    MobileDevice::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'fcm_token' => 'token-abc',
        'platform' => 'ios',
    ]);

    $issued = ApiToken::issue($company->id, 'Prometheus', ['monitoring.write'], $user->id);

    $this->postJson('/api/v1/webhooks/prometheus', [
        'alerts' => [[
            'fingerprint' => 'fp-e2e-1',
            'status' => 'firing',
            'labels' => ['alertname' => 'Down', 'instance' => 'srv-prod-01:9100', 'severity' => 'critical'],
            'annotations' => ['summary' => 'srv-prod-01 down'],
        ]],
    ], ['Authorization' => 'Bearer '.$issued['token']])->assertStatus(202);

    $incidentPushes = collect($sent)->filter(fn ($p) => ($p['data']['type'] ?? null) === 'incident');
    expect($incidentPushes)->toHaveCount(1)
        ->and($incidentPushes->first()['tokens'])->toBe(['token-abc']);
});
