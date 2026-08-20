<?php

use App\Modules\NexaTaxi\Controllers\ContractPortalAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContractPortalAppController::class, 'index'])->name('index');
Route::get('/handleiding', [ContractPortalAppController::class, 'handleiding'])->name('handleiding');
Route::get('/manifest.webmanifest', [ContractPortalAppController::class, 'manifest'])->name('manifest');
