<?php

use App\Modules\NexaTaxi\Controllers\DriverAppController;
use App\Modules\NexaTaxi\Controllers\TaxiDriverPaymentReturnController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DriverAppController::class, 'index'])->name('index');
Route::get('/handleiding', [DriverAppController::class, 'handleiding'])->name('handleiding');
Route::get('/manifest.webmanifest', [DriverAppController::class, 'manifest'])->name('manifest');
Route::get('/betaling/terug', TaxiDriverPaymentReturnController::class)->name('payment.return');
