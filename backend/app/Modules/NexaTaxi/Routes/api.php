<?php

use App\Modules\NexaTaxi\Controllers\Api\DriverAuthController;
use App\Modules\NexaTaxi\Controllers\Api\DriverAvailabilityController;
use App\Modules\NexaTaxi\Controllers\Api\DriverDispatchController;
use App\Modules\NexaTaxi\Controllers\Api\DriverDispatchStreamController;
use App\Modules\NexaTaxi\Controllers\Api\DriverRideInvoiceController;
use App\Modules\NexaTaxi\Controllers\Api\DriverRidePaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/driver')
    ->middleware(['taxi.driver'])
    ->group(function () {
        Route::post('logout', [DriverAuthController::class, 'logout']);
        Route::get('me', [DriverAuthController::class, 'me']);

        Route::put('availability', [DriverAvailabilityController::class, 'update'])
            ->middleware('throttle:60,1');

        Route::get('dispatch/inbox', [DriverDispatchController::class, 'inbox'])
            ->middleware('throttle:taxi-driver-poll');

        Route::get('dispatch/stream', [DriverDispatchStreamController::class, 'stream'])
            ->middleware('throttle:120,1');

        Route::post('dispatch/offers/{offer}/accept', [DriverDispatchController::class, 'accept'])
            ->middleware('throttle:30,1')
            ->whereNumber('offer');

        Route::post('dispatch/offers/{offer}/decline', [DriverDispatchController::class, 'decline'])
            ->middleware('throttle:30,1')
            ->whereNumber('offer');

        Route::post('dispatch/rides/{ride}/start', [DriverDispatchController::class, 'start'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/release', [DriverDispatchController::class, 'release'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/complete', [DriverDispatchController::class, 'complete'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::get('dispatch/rides/{ride}/payment', [DriverRidePaymentController::class, 'show'])
            ->middleware('throttle:60,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/payment', [DriverRidePaymentController::class, 'store'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/payment/cash', [DriverRidePaymentController::class, 'cash'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::get('dispatch/rides/{ride}/invoice', [DriverRideInvoiceController::class, 'show'])
            ->middleware('throttle:60,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/invoice/send', [DriverRideInvoiceController::class, 'send'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');
    });
