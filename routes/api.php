<?php

use App\Http\Controllers\Api\MonitoringWebhookController;
use App\Http\Controllers\Api\PortalProfileController;
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

// Stub-Endpoint für planb-portal-Integration (Phase 4 — siehe ~/Code/laravel/arento/planb-portal/SPEC.md).
// Auth: Bearer-Token, gehasht und gegen companies.portal_api_token_hash gematcht.
Route::get('v1/portal/profile', PortalProfileController::class)->name('api.portal.profile');
