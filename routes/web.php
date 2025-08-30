<?php

use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome-laravel');
});

Route::get('/', function () {
    return view('welcome');
});

// Route to handle the audio submission and translation logic
Route::post('/translate-voice', [TranslationController::class, 'translate']);
