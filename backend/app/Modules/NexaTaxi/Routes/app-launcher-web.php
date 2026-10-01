<?php

use App\Modules\NexaTaxi\Controllers\TaxiAppLauncherController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TaxiAppLauncherController::class, 'index'])->name('index');
