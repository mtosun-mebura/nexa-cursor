<?php

use App\Modules\NexaTaxi\Controllers\Api\ContractPortalAuthController;
use App\Modules\NexaTaxi\Controllers\Api\DriverAuthController;
use App\Modules\NexaTaxi\Controllers\Api\TaxiMollieWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/app')->group(function () {
    Route::post('login', [\App\Modules\NexaTaxi\Controllers\Api\AppBootstrapController::class, 'login'])
        ->middleware('throttle:taxi-app-login-code');
    Route::post('login-code/request', [\App\Modules\NexaTaxi\Controllers\Api\AppBootstrapController::class, 'requestLoginCode'])
        ->middleware('throttle:taxi-app-login-code');
    Route::post('login-code/verify', [\App\Modules\NexaTaxi\Controllers\Api\AppBootstrapController::class, 'verifyLoginCode'])
        ->middleware('throttle:taxi-app-login-code');
});

Route::prefix('v1/driver')->group(function () {
    Route::post('login', [DriverAuthController::class, 'login'])
        ->middleware('throttle:taxi-driver-login');
    Route::post('login-code/request', [DriverAuthController::class, 'requestLoginCode'])
        ->middleware('throttle:taxi-app-login-code');
    Route::post('login-code/verify', [DriverAuthController::class, 'verifyLoginCode'])
        ->middleware('throttle:taxi-app-login-code');
});

Route::prefix('v1/contract')->group(function () {
    Route::post('login', [ContractPortalAuthController::class, 'login'])
        ->middleware('throttle:taxi-contract-login');
    Route::post('login-code/request', [ContractPortalAuthController::class, 'requestLoginCode'])
        ->middleware('throttle:taxi-app-login-code');
    Route::post('login-code/verify', [ContractPortalAuthController::class, 'verifyLoginCode'])
        ->middleware('throttle:taxi-app-login-code');
});

Route::prefix('v1/customer')->group(function () {
    Route::post('login', [\App\Modules\NexaTaxi\Controllers\Api\CustomerAuthController::class, 'login'])
        ->middleware('throttle:taxi-driver-login');
    Route::post('register', [\App\Modules\NexaTaxi\Controllers\Api\CustomerAuthController::class, 'register'])
        ->middleware('throttle:taxi-driver-login');
    Route::post('quote', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'quote'])
        ->middleware('throttle:60,1');
    Route::post('book/guest', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'book'])
        ->middleware('throttle:20,1');
    Route::get('live', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'live'])
        ->middleware('throttle:taxi-driver-poll');
    Route::post('live/cancel', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'cancelByToken'])
        ->middleware('throttle:20,1');
    Route::post('live/wait', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'waitByToken'])
        ->middleware('throttle:20,1');
    Route::post('live/pay', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'payByToken'])
        ->middleware('throttle:20,1');
    Route::get('live/invoice', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'invoiceByToken'])
        ->middleware('throttle:30,1');
});

Route::post('webhooks/mollie', TaxiMollieWebhookController::class)
    ->name('webhooks.mollie');
