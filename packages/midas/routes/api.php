<?php

use Illuminate\Support\Facades\Route;
use PHPinnacle\Midas\Http\Controllers\NotifyController;

Route::post('/api/midas/transactions/{id}/notify', NotifyController::class)
    ->name('midas.transaction.notify');
