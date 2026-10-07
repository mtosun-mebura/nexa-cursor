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

Route::prefix('v1/app')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('capabilities', [\App\Modules\NexaTaxi\Controllers\Api\AppBootstrapController::class, 'capabilities'])
            ->middleware('throttle:taxi-driver-read');
    });

Route::prefix('v1/customer')
    ->middleware(['taxi.customer'])
    ->group(function () {
        Route::post('logout', [\App\Modules\NexaTaxi\Controllers\Api\CustomerAuthController::class, 'logout']);
        Route::get('me', [\App\Modules\NexaTaxi\Controllers\Api\CustomerAuthController::class, 'me']);
        Route::put('profile', [\App\Modules\NexaTaxi\Controllers\Api\CustomerAuthController::class, 'updateProfile'])
            ->middleware('throttle:taxi-customer-action');
        Route::put('accent', [\App\Modules\NexaTaxi\Controllers\Api\CustomerAuthController::class, 'updateAccent'])
            ->middleware('throttle:taxi-customer-action');
        Route::get('rides', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'index'])
            ->middleware('throttle:taxi-customer-read');
        Route::get('rides/{ride}', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'show'])
            ->middleware('throttle:taxi-customer-read')
            ->whereNumber('ride');
        Route::post('rides/{ride}/cancel', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'cancel'])
            ->middleware('throttle:taxi-customer-action')
            ->whereNumber('ride');
        Route::post('rides/{ride}/wait', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'wait'])
            ->middleware('throttle:taxi-customer-action')
            ->whereNumber('ride');
        Route::post('rides/{ride}/pay', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'pay'])
            ->middleware('throttle:taxi-customer-action')
            ->whereNumber('ride');
        Route::get('rides/{ride}/invoice', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'invoice'])
            ->middleware('throttle:taxi-customer-read')
            ->whereNumber('ride');
        Route::post('book', [\App\Modules\NexaTaxi\Controllers\Api\CustomerRideController::class, 'book'])
            ->middleware('throttle:taxi-customer-action');
    });

Route::prefix('v1/contract')
    ->middleware(['taxi.contract'])
    ->group(function () {
        Route::post('logout', [ContractPortalAuthController::class, 'logout']);
        Route::get('me', [ContractPortalAuthController::class, 'me']);
        Route::put('accent', [ContractPortalAuthController::class, 'updateAccent'])
            ->middleware('throttle:taxi-driver-read');
        Route::get('passengers', [ContractPortalController::class, 'passengers'])
            ->middleware('throttle:taxi-driver-read');
        Route::get('today', [ContractPortalController::class, 'today'])
            ->middleware('throttle:taxi-driver-read');
        Route::get('week', [ContractPortalController::class, 'week'])
            ->middleware('throttle:taxi-driver-read');
        Route::get('announcements', [ContractPortalController::class, 'announcements'])
            ->middleware('throttle:taxi-driver-read');
        Route::get('absences', [ContractPortalController::class, 'absences'])
            ->middleware('throttle:taxi-driver-read');
        Route::post('passengers/{passenger}/absences', [ContractPortalController::class, 'storeAbsence'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('passenger');
        Route::delete('absences/{absence}', [ContractPortalController::class, 'destroyAbsence'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('absence');
        Route::post('stops/{rideStop}/start', [ContractPortalController::class, 'startRide'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('rideStop');
        Route::post('stops/{rideStop}/board', [ContractPortalController::class, 'boardPassenger'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('rideStop');
        Route::post('stops/{rideStop}/skip', [ContractPortalController::class, 'skipPassenger'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('rideStop');
        Route::post('stops/{rideStop}/complete', [ContractPortalController::class, 'completeRide'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('rideStop');
    });

Route::prefix('v1/driver')
    ->middleware(['taxi.driver'])
    ->group(function () {
        Route::post('logout', [DriverAuthController::class, 'logout']);
        Route::get('me', [DriverAuthController::class, 'me']);
        Route::put('accent', [DriverAuthController::class, 'updateAccent'])
            ->middleware('throttle:taxi-driver-action');
        Route::put('ride-alert-tone', [DriverAuthController::class, 'updateRideAlertTone'])
            ->middleware('throttle:taxi-driver-action');

        Route::get('earnings', [DriverEarningsController::class, 'show'])
            ->middleware('throttle:taxi-driver-read');

        Route::get('planning', [DriverPlanningController::class, 'week'])
            ->middleware('throttle:taxi-driver-read');

        Route::put('availability', [DriverAvailabilityController::class, 'update'])
            ->middleware('throttle:taxi-driver-action');

        Route::put('availability/location', [DriverAvailabilityController::class, 'updateLocation'])
            ->middleware('throttle:taxi-driver-location');

        Route::get('vehicles', [DriverAvailabilityController::class, 'vehicles'])
            ->middleware('throttle:taxi-driver-read');

        Route::get('dispatch/inbox', [DriverDispatchController::class, 'inbox'])
            ->middleware('throttle:taxi-driver-poll');

        Route::get('dispatch/stream', [DriverDispatchStreamController::class, 'stream'])
            ->middleware('throttle:taxi-driver-stream');

        Route::post('dispatch/offers/{offer}/accept', [DriverDispatchController::class, 'accept'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('offer');

        Route::post('dispatch/offers/{offer}/decline', [DriverDispatchController::class, 'decline'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('offer');

        Route::post('dispatch/offers/{offer}/archive', [DriverDispatchController::class, 'archiveOffer'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('offer');

        Route::delete('dispatch/offers/{offer}/archive', [DriverDispatchController::class, 'deleteArchivedOffer'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('offer');

        Route::post('dispatch/archived-offers/delete', [DriverDispatchController::class, 'deleteArchivedOffers'])
            ->middleware('throttle:taxi-driver-action');

        Route::post('dispatch/rides/{ride}/propose-pickup', [DriverDispatchController::class, 'proposePickup'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');
        Route::post('dispatch/rides/{ride}/start', [DriverDispatchController::class, 'start'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/release', [DriverDispatchController::class, 'release'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/cancel', [DriverDispatchController::class, 'cancel'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/hand-over-network', [DriverDispatchController::class, 'handOverToNetwork'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/release-return', [DriverDispatchController::class, 'releaseReturn'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/start-return', [DriverDispatchController::class, 'startReturn'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/complete', [DriverDispatchController::class, 'complete'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');

        Route::get('dispatch/rides/{ride}/stops', [DriverRideStopController::class, 'index'])
            ->middleware('throttle:taxi-driver-read')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/stops/{stop}/arrive', [DriverRideStopController::class, 'arrive'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber(['ride', 'stop']);

        Route::post('dispatch/rides/{ride}/stops/{stop}/pickup', [DriverRideStopController::class, 'pickup'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber(['ride', 'stop']);

        Route::post('dispatch/rides/{ride}/stops/{stop}/skip', [DriverRideStopController::class, 'skip'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber(['ride', 'stop']);

        Route::get('dispatch/rides/{ride}/payment', [DriverRidePaymentController::class, 'show'])
            ->middleware('throttle:taxi-driver-read')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/payment', [DriverRidePaymentController::class, 'store'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/payment/cash', [DriverRidePaymentController::class, 'cash'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');

        Route::get('dispatch/rides/{ride}/invoice', [DriverRideInvoiceController::class, 'show'])
            ->middleware('throttle:taxi-driver-read')
            ->whereNumber('ride');

        Route::post('dispatch/rides/{ride}/invoice/send', [DriverRideInvoiceController::class, 'send'])
            ->middleware('throttle:taxi-driver-action')
            ->whereNumber('ride');
    });
