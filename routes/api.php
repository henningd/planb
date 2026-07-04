<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileDeviceController;
use App\Http\Controllers\Api\MobileHandbookPdfController;
use App\Http\Controllers\Api\MobileIncidentController;
use App\Http\Controllers\Api\MobileSyncController;
use App\Http\Controllers\Api\MonitoringWebhookController;
use App\Http\Controllers\Api\PortalProfileController;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureMobileAppKey;
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

// PlanB-Notfall-App (native iOS/Android). Optionaler App-Key-Gate über
// EnsureMobileAppKey; Login löst einen Kopplungs-Code ein und liefert einen
// mandantengebundenen Bearer-Token (Scope `mobile`).
Route::prefix('mobile')
    ->middleware(EnsureMobileAppKey::class)
    ->group(function () {
        Route::post('login', [MobileAuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('api.mobile.login');

        Route::middleware([AuthenticateApiToken::class.':mobile'])->group(function () {
            Route::post('logout', [MobileAuthController::class, 'logout'])->name('api.mobile.logout');
            Route::get('sync', MobileSyncController::class)->name('api.mobile.sync');
            Route::get('handbook/{version}/pdf', MobileHandbookPdfController::class)->name('api.mobile.handbook.pdf');
            Route::post('devices/register', [MobileDeviceController::class, 'register'])->name('api.mobile.devices.register');
            Route::post('devices/unregister', [MobileDeviceController::class, 'unregister'])->name('api.mobile.devices.unregister');
            Route::post('incidents', [MobileIncidentController::class, 'store'])->middleware('throttle:20,1')->name('api.mobile.incidents.store');
        });
    });
