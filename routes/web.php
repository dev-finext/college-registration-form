<?php

use App\Http\Controllers\RegistrationController;
use App\Services\RegistrationPdf;
use Illuminate\Support\Facades\Route;

Route::get('/', [RegistrationController::class, 'show'])->name('registration.show');

Route::post('/register', [RegistrationController::class, 'store'])
    ->middleware('throttle:registration')
    ->name('registration.store');

// Fresh CSRF token for a tab that stayed open past the session lifetime.
Route::get('/token', [RegistrationController::class, 'token'])->name('registration.token');

if (app()->isLocal()) {
    // Local only: preview the PDF with sample data.
    Route::get('/dev/pdf', function (RegistrationPdf $pdf) {
        $data = require base_path('tests/Fixtures/registration.php');

        return response($pdf->render($data), 200, ['Content-Type' => 'application/pdf']);
    });
}

// Dedicated link per branch, e.g. /jerusalem - the branch is locked in the form.
Route::get('/{branch}', [RegistrationController::class, 'show'])
    ->whereIn('branch', array_keys(config('registration.branches')))
    ->name('registration.branch');
