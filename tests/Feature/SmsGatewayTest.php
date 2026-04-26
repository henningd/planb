<?php

use App\Services\Sms\NullSmsGateway;
use App\Services\Sms\SevenIoGateway;
use App\Services\Sms\SmsGatewayContract;
use Illuminate\Support\Facades\Http;

test('NullSmsGateway is used when no api key is configured', function () {
    config(['services.sevenio.key' => null]);
    $this->app->forgetInstance(SmsGatewayContract::class);

    $gateway = app(SmsGatewayContract::class);

    expect($gateway)->toBeInstanceOf(NullSmsGateway::class);
    expect($gateway->isConfigured())->toBeFalse();
    expect($gateway->send('+491701234567', 'Test')->success)->toBeTrue();
});

test('SevenIoGateway is used when api key is configured', function () {
    config(['services.sevenio.key' => 'key-test', 'services.sevenio.sender' => 'PlanB']);
    $this->app->forgetInstance(SmsGatewayContract::class);

    expect(app(SmsGatewayContract::class))->toBeInstanceOf(SevenIoGateway::class);
});

test('SevenIoGateway returns success for accepted provider response', function () {
    Http::fake([
        'gateway.seven.io/*' => Http::response([
            'success' => '100',
            'messages' => [
                ['id' => 'msg-12345'],
            ],
        ], 200),
    ]);

    $gateway = new SevenIoGateway('key-test', 'PlanB');
    $result = $gateway->send('+491701234567', 'Hallo Welt');

    expect($result->success)->toBeTrue();
    expect($result->providerMessageId)->toBe('msg-12345');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://gateway.seven.io/api/sms'
            && $request->method() === 'POST'
            && $request->header('X-Api-Key')[0] === 'key-test'
            && $request['to'] === '+491701234567'
            && $request['text'] === 'Hallo Welt'
            && $request['from'] === 'PlanB';
    });
});

test('SevenIoGateway returns failure for non-100 success code', function () {
    Http::fake([
        'gateway.seven.io/*' => Http::response(['success' => '301'], 200),
    ]);

    $gateway = new SevenIoGateway('key-test', null);
    $result = $gateway->send('+491701234567', 'Hi');

    expect($result->success)->toBeFalse();
    expect($result->errorMessage)->toContain('301');
});

test('SevenIoGateway returns failure for HTTP error', function () {
    Http::fake([
        'gateway.seven.io/*' => Http::response('upstream', 502),
    ]);

    $gateway = new SevenIoGateway('key-test', null);
    $result = $gateway->send('+491701234567', 'Hi');

    expect($result->success)->toBeFalse();
    expect($result->errorMessage)->toContain('502');
});

test('SevenIoGateway refuses to send when api key is missing', function () {
    $gateway = new SevenIoGateway(null, null);

    expect($gateway->isConfigured())->toBeFalse();

    $result = $gateway->send('+491701234567', 'Hi');
    expect($result->success)->toBeFalse();
    expect($result->errorMessage)->toContain('SEVENIO_API_KEY');
});
