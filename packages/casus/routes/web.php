<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use PHPinnacle\Casus\Http\Controllers\ReportFrontendErrorController;

if (Config::boolean('phpinnacle-casus.frontend.enabled', false)) {
    Route::post('/casus/errors', ReportFrontendErrorController::class)
        ->middleware(['web', 'throttle:phpinnacle-casus.frontend'])
        ->name('phpinnacle-casus.frontend.report');
}
