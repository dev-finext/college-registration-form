<?php

use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RegistrationController::class, 'show'])->name('registration.show');

Route::post('/register', [RegistrationController::class, 'store'])
    ->middleware('throttle:registration')
    ->name('registration.store');

// Fresh CSRF token for a tab that stayed open past the session lifetime.
Route::get('/token', [RegistrationController::class, 'token'])->name('registration.token');

// Dedicated link per branch, e.g. /jerusalem - the branch is locked in the form.
Route::get('/{branch}', [RegistrationController::class, 'show'])
    ->whereIn('branch', array_keys(config('registration.branches')))
    ->name('registration.branch');
