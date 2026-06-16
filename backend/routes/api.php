<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PropertiController;
use App\Http\Controllers\Api\KprController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/properti/subsidi', [PropertiController::class, 'subsidi']);
Route::get('/properti/rekomendasi', [PropertiController::class, 'rekomendasi']);
Route::post('/kpr/simulasi', [KprController::class, 'simulasi']);