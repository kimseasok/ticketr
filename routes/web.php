<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthcheckController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', HealthcheckController::class);
