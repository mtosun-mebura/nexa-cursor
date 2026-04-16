<?php

use Illuminate\Support\Facades\Route;
use App\Modules\NexaTaxi\Controllers\Admin\VehicleController;
use App\Modules\NexaTaxi\Controllers\Admin\RideRequestController;
use App\Modules\NexaTaxi\Controllers\Admin\TarievenController;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\ModuleDatabaseService;

/*
|--------------------------------------------------------------------------
| Nexa Taxi Module – Admin routes
|--------------------------------------------------------------------------
| Voertuigen en ritten/aanvragen. Prefix: admin/taxi, name: admin.taxi.*
| Data komt uit de module-database nexa_taxi.
*/

$taxiConn = fn () => app(ModuleDatabaseService::class)->getModuleConnectionName('taxi');
Route::bind('vehicle', fn ($value) => Vehicle::on($taxiConn())->findOrFail($value));
Route::bind('ride_request', fn ($value) => RideRequest::on($taxiConn())->findOrFail($value));

Route::get('tarieven', [TarievenController::class, 'edit'])->name('tarieven.edit');
Route::put('tarieven', [TarievenController::class, 'update'])->name('tarieven.update');
Route::post('ride_requests/{ride_request}/assign', [RideRequestController::class, 'assign'])->name('ride_requests.assign');
Route::resource('ride_requests', RideRequestController::class);
Route::post('vehicles/upload-image', [VehicleController::class, 'uploadImage'])->name('vehicles.upload-image');
Route::resource('vehicles', VehicleController::class);
