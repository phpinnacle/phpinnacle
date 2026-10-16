<?php

use Illuminate\Support\Facades\Route;
use PHPinnacle\Eidos\Http\Controllers\SchemaIconController;

Route::get('phpinnacle/eidos/icons/{icon}.svg', SchemaIconController::class)
    ->where('icon', '[a-zA-Z0-9_-]+')
    ->name('phpinnacle-eidos.icons');
