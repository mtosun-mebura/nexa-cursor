<?php

use Illuminate\Support\Facades\Route;
use App\Modules\TaxiRoyaal\Controllers\Admin\VehicleController;
use App\Modules\TaxiRoyaal\Controllers\Admin\RideRequestController;
use App\Modules\TaxiRoyaal\Controllers\Admin\TarievenController;
use App\Modules\TaxiRoyaal\Models\Vehicle;
use App\Modules\TaxiRoyaal\Models\RideRequest;
use App\Services\ModuleDatabaseService;

/*
|--------------------------------------------------------------------------
| Taxi Royaal Module – Admin routes
|--------------------------------------------------------------------------
| Voertuigen en ritten/aanvragen. Prefix: admin/taxiroyaal, name: admin.taxiroyaal.*
| Data komt uit de module-database nexa_taxiroyaal.
*/

$taxiConn = fn () => app(ModuleDatabaseService::class)->getModuleConnectionName('taxiroyaal');
Route::bind('vehicle', fn ($value) => Vehicle::on($taxiConn())->findOrFail($value));
Route::bind('ride_request', fn ($value) => RideRequest::on($taxiConn())->findOrFail($value));

Route::get('tarieven', [TarievenController::class, 'edit'])->name('tarieven.edit');
Route::put('tarieven', [TarievenController::class, 'update'])->name('tarieven.update');
Route::post('ride_requests/{ride_request}/assign', [RideRequestController::class, 'assign'])->name('ride_requests.assign');
Route::resource('ride_requests', RideRequestController::class);
Route::post('vehicles/upload-image', [VehicleController::class, 'uploadImage'])->name('vehicles.upload-image');
Route::resource('vehicles', VehicleController::class);
