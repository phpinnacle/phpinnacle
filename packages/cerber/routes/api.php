<?php

use Illuminate\Support\Facades\Route;
use PHPinnacle\Cerber\Http\Controllers\OAuth\API\OAuthController;

Route::prefix('api/auth/oauth/{socialAccountContext}')
    ->name('cerber.oauth.')
    ->middleware(['api'])
    ->group(function () {
        Route::get('providers', [OAuthController::class, 'providers'])
            ->name('providers')
            ->middleware('throttle:30,1');

        Route::get('{provider}/redirect', [OAuthController::class, 'redirect'])
            ->name('redirect')
            ->middleware('throttle:10,1');

        Route::post('{provider}/callback', [OAuthController::class, 'callback'])
            ->name('callback')
            ->middleware('throttle:10,1');
        Route::delete('{provider}/link', [OAuthController::class, 'unlink'])
            ->name('unlink')
            ->middleware('throttle:10,1');
    });
