<?php

use Illuminate\Support\Facades\Route;
use PHPinnacle\Razor\Http\Controllers\DocumentController;

Route::get('/documents/{id}', DocumentController::class)
    ->name('documents.show');
