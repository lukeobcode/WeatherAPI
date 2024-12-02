<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/weather', [WeatherController::class, 'index']);

Route::get('/test-api-key', function () {
    dd(env('OPENWEATHER_API_KEY'));
});
