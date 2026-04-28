<?php

use App\Http\Controllers\Api\MonitoringWebhookController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

if (config('features.monitoring_api')) {
    Route::middleware([AuthenticateApiToken::class.':monitoring.write'])
        ->prefix('v1/webhooks')
        ->group(function () {
            Route::post('zabbix', [MonitoringWebhookController::class, 'zabbix'])->name('api.webhooks.zabbix');
            Route::post('prometheus', [MonitoringWebhookController::class, 'prometheus'])->name('api.webhooks.prometheus');
        });
}
