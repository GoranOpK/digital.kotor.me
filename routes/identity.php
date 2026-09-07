<?php

use App\Http\Controllers\ExistingSubjectIdentityCompletionController;
use Illuminate\Support\Facades\Route;

Route::get('/identitet/dopuna-subjekta', [ExistingSubjectIdentityCompletionController::class, 'create'])
    ->name('identity.completion.create');

Route::post('/identitet/dopuna-subjekta', [ExistingSubjectIdentityCompletionController::class, 'store'])
    ->name('identity.completion.store');
