<?php

use Illuminate\Support\Facades\Route;
use PHPinnacle\Cerber\Http\Controllers\Auth\AuthController;
use PHPinnacle\Cerber\Http\Controllers\Auth\PasswordController;
use PHPinnacle\Cerber\Http\Controllers\Auth\RegistrationController;
use PHPinnacle\Cerber\Http\Controllers\OAuth\OAuthController;

if (config('phpinnacle-cerber.api.enabled')) {
    Route::prefix('api')
        ->name('cerber.api.')
        ->middleware('api')
        ->group(function () {
            Route::prefix('auth')->group(function () {
                Route::post('register', [RegistrationController::class, 'register'])
                    ->name('register')
                    ->middleware('throttle:6,1');
                Route::post('login', [AuthController::class, 'login'])->name('login')->middleware('throttle:6,1');
                Route::get('verify-email', [RegistrationController::class, 'verify'])
                    ->name('verify-email')->middleware(['signed:relative', 'throttle:10,1']);
                Route::post('password/forgot', [PasswordController::class, 'forgot'])
                    ->name('password.forgot')
                    ->middleware('throttle:6,1');
                Route::post('password/reset', [PasswordController::class, 'reset'])
                    ->name('password.reset')
                    ->middleware('throttle:6,1');

                Route::middleware('auth:api')->group(function () {
                    Route::post('password/change', [PasswordController::class, 'change'])
                        ->name('password.change')
                        ->middleware('throttle:6,1');
                    Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
                    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
                });
            });

            Route::get('profile', [AuthController::class, 'profile'])->name('profile')->middleware('auth:api');
        });
}

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
