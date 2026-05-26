<?php

use App\Modules\NexaTaxi\Controllers\Api\DriverAuthController;
use App\Modules\NexaTaxi\Controllers\Api\TaxiMollieWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/driver')->group(function () {
    Route::post('login', [DriverAuthController::class, 'login'])
        ->middleware('throttle:taxi-driver-login');
});

Route::post('webhooks/mollie', TaxiMollieWebhookController::class)
    ->name('webhooks.mollie');
