<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PHPinnacle\Babylon\Http\Middleware\SetLocale;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    $locale = config('app.locale');

    App::setLocale($locale);
    Locale::setDefault($locale);
});

it('applies the locale stored in the session', function () {
    $request = Request::create('/dashboard');
    $request->setLaravelSession(app('session')->driver());
    $request->session()->put('locale', 'pl');

    $result = new SetLocale()->handle(
        $request,
        fn (Request $request) => $request->path(),
    );

    expect($result)
        ->toBe('dashboard')
        ->and(App::getLocale())
        ->toBe('pl')
        ->and(Locale::getDefault())
        ->toBe('pl');
});
