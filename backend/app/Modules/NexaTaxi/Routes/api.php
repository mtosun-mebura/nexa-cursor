<?php

use App\Modules\NexaTaxi\Controllers\Api\ContractPortalAuthController;
use App\Modules\NexaTaxi\Controllers\Api\ContractPortalController;
use App\Modules\NexaTaxi\Controllers\Api\DriverAuthController;
use App\Modules\NexaTaxi\Controllers\Api\DriverAvailabilityController;
use App\Modules\NexaTaxi\Controllers\Api\DriverDispatchController;
use App\Modules\NexaTaxi\Controllers\Api\DriverDispatchStreamController;
use App\Modules\NexaTaxi\Controllers\Api\DriverEarningsController;
use App\Modules\NexaTaxi\Controllers\Api\DriverPlanningController;
use App\Modules\NexaTaxi\Controllers\Api\DriverRideInvoiceController;
use App\Modules\NexaTaxi\Controllers\Api\DriverRidePaymentController;
use App\Modules\NexaTaxi\Controllers\Api\DriverRideStopController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/contract')
    ->middleware(['taxi.contract'])
    ->group(function () {
        Route::post('logout', [ContractPortalAuthController::class, 'logout']);
        Route::get('me', [ContractPortalAuthController::class, 'me']);
        Route::put('accent', [ContractPortalAuthController::class, 'updateAccent'])
            ->middleware('throttle:60,1');
        Route::get('passengers', [ContractPortalController::class, 'passengers'])
            ->middleware('throttle:60,1');
        Route::get('today', [ContractPortalController::class, 'today'])
            ->middleware('throttle:60,1');
        Route::get('week', [ContractPortalController::class, 'week'])
            ->middleware('throttle:60,1');
        Route::get('announcements', [ContractPortalController::class, 'announcements'])
            ->middleware('throttle:60,1');
        Route::get('absences', [ContractPortalController::class, 'absences'])
            ->middleware('throttle:60,1');
        Route::post('passengers/{passenger}/absences', [ContractPortalController::class, 'storeAbsence'])
            ->middleware('throttle:30,1')
            ->whereNumber('passenger');
        Route::delete('absences/{absence}', [ContractPortalController::class, 'destroyAbsence'])
            ->middleware('throttle:30,1')
            ->whereNumber('absence');
    });

Route::prefix('v1/driver')
    ->middleware(['taxi.driver'])
    ->group(function () {
        Route::post('logout', [DriverAuthController::class, 'logout']);
        Route::get('me', [DriverAuthController::class, 'me']);
        Route::put('accent', [DriverAuthController::class, 'updateAccent'])
            ->middleware('throttle:60,1');

        Route::get('earnings', [DriverEarningsController::class, 'show'])
            ->middleware('throttle:60,1');

        Route::get('planning', [DriverPlanningController::class, 'week'])
            ->middleware('throttle:60,1');

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

        Route::post('dispatch/offers/{offer}/archive', [DriverDispatchController::class, 'archiveOffer'])
            ->middleware('throttle:30,1')
            ->whereNumber('offer');

        Route::delete('dispatch/offers/{offer}/archive', [DriverDispatchController::class, 'deleteArchivedOffer'])
            ->middleware('throttle:30,1')
            ->whereNumber('offer');

        Route::post('dispatch/archived-offers/delete', [DriverDispatchController::class, 'deleteArchivedOffers'])
            ->middleware('throttle:20,1');

        Route::post('dispatch/rides/{ride}/propose-pickup', [DriverDispatchController::class, 'proposePickup'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');
        Route::post('dispatch/rides/{ride}/start', [DriverDispatchController::class, 'start'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/release', [DriverDispatchController::class, 'release'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/release-return', [DriverDispatchController::class, 'releaseReturn'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/start-return', [DriverDispatchController::class, 'startReturn'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/complete', [DriverDispatchController::class, 'complete'])
            ->middleware('throttle:30,1')
            ->whereNumber('ride');

        Route::get('dispatch/rides/{ride}/stops', [DriverRideStopController::class, 'index'])
            ->middleware('throttle:60,1')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/stops/{stop}/arrive', [DriverRideStopController::class, 'arrive'])
            ->middleware('throttle:60,1')
            ->whereNumber(['ride', 'stop']);

        Route::post('dispatch/rides/{ride}/stops/{stop}/pickup', [DriverRideStopController::class, 'pickup'])
            ->middleware('throttle:60,1')
            ->whereNumber(['ride', 'stop']);

        Route::post('dispatch/rides/{ride}/stops/{stop}/skip', [DriverRideStopController::class, 'skip'])
            ->middleware('throttle:60,1')
            ->whereNumber(['ride', 'stop']);

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
