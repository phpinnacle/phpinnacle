<?php

use Illuminate\Support\Facades\Route;
use PHPinnacle\Porta\Controllers;

Route::get('/webhook/{id}', Controllers\ShowController::class)
    ->name('webhook.show');

Route::post('/webhook/{id}', Controllers\CatchController::class)
    ->name('webhook.catch');
