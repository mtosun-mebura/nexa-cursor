<?php

use App\Modules\NexaTaxi\Controllers\CustomerAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CustomerAppController::class, 'index'])->name('index');
Route::get('/manifest.webmanifest', [CustomerAppController::class, 'manifest'])->name('manifest');
