<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::group(['middleware' => ['web']], static function () {
    Route::get('phpinnacle/switch-language/{code}', static function (string $code) {
        Session::put('locale', $code);

        return redirect()->back();
    })->name('phpinnacle-babylon.switch-language');
});
