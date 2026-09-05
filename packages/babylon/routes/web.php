<?php

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPinnacle\Babylon\BabylonPlugin;

Route::group(['middleware' => ['web']], static function () {
    Route::get('phpinnacle/switch-language/{code}', static function (Request $request, string $code) {
        $panelId = $request->query('panel');

        abort_unless(is_string($panelId), 404);

        $panel = Filament::getPanel($panelId);

        abort_unless($panel?->hasPlugin(BabylonPlugin::ID), 404);

        /** @var BabylonPlugin $plugin */
        $plugin = $panel->getPlugin(BabylonPlugin::ID);

        abort_unless(array_key_exists($code, $plugin->getLocales()), 404);

        $request->session()->put($plugin->getSessionKey(), $code);

        return redirect()->back();
    })->name('phpinnacle-babylon.switch-language');
});
