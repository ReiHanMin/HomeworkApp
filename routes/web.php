<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OCRController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/process-image', [OCRController::class, 'processImage']);

Route::post('/process-text', [OCRController::class, 'processText']);
