<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Babylon\BabylonPlugin;
use PHPinnacle\Babylon\Http\Middleware\SetLocale;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->plugin = BabylonPlugin::make()->locales('en', 'pl', 'ru', 'ar');
    $this->panel = Panel::make()
        ->id('admin')
        ->plugin($this->plugin);

    Filament::setCurrentPanel($this->panel);
});

afterEach(function () {
    $locale = config('app.locale');

    App::setLocale($locale);
    Locale::setDefault($locale);
    Filament::setCurrentPanel(null);
});

it('applies the locale stored in the session', function () {
    $request = Request::create('/dashboard');
    $request->setLaravelSession(app('session')->driver());
    $request->session()->put($this->plugin->getSessionKey(), 'pl');

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

it('applies a supported browser locale on the first visit', function () {
    $request = Request::create('/dashboard', server: [
        'HTTP_ACCEPT_LANGUAGE' => 'pl-PL,pl;q=0.9,en;q=0.8',
    ]);

    new SetLocale()->handle($request, fn () => null);

    expect(App::getLocale())
        ->toBe('pl')
        ->and(Locale::getDefault())
        ->toBe('pl');
});

it('keeps the application locale without a stored or browser locale', function () {
    config()->set('app.locale', 'en');
    App::setLocale('en');

    new SetLocale()->handle(Request::create('/dashboard'), fn () => null);

    expect(App::getLocale())->toBe('en');
});

it('keeps session locales separate for each panel', function () {
    $memberPlugin = BabylonPlugin::make()->locales('en', 'pl', 'ru');
    $memberPanel = Panel::make()
        ->id('member')
        ->plugin($memberPlugin);

    $request = Request::create('/dashboard');
    $request->setLaravelSession(app('session')->driver());
    $request->session()->put($this->plugin->getSessionKey(), 'pl');
    $request->session()->put($memberPlugin->getSessionKey(), 'ru');

    new SetLocale()->handle($request, fn () => null);

    expect(App::getLocale())->toBe('pl');

    Filament::setCurrentPanel($memberPanel);

    new SetLocale()->handle($request, fn () => null);

    expect(App::getLocale())->toBe('ru');
});

it('keeps preference groups separate for each panel', function () {
    expect($this->plugin->getPreferenceGroup())
        ->toBe('babylon-admin')
        ->and(BabylonPlugin::profile()->getStatePath(isAbsolute: false))
        ->toBe('babylon-admin');

    $memberPlugin = BabylonPlugin::make()->locales('en', 'pl');
    $memberPanel = Panel::make()
        ->id('member')
        ->plugin($memberPlugin);

    Filament::setCurrentPanel($memberPanel);

    expect($memberPlugin->getPreferenceGroup())
        ->toBe('babylon-member')
        ->and(BabylonPlugin::profile()->getStatePath(isAbsolute: false))
        ->toBe('babylon-member');
});

it('applies the locale stored in the panel preference', function () {
    Schema::create('preferences', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('user_id');
        $table->string('group');
        $table->string('key');
        $table->json('value')->nullable();
        $table->timestamps();
    });

    DB::table('preferences')->insert([
        'id' => '019caaa3-9a0c-7d13-bd86-e208d2644489',
        'user_id' => 'user-1',
        'group' => $this->plugin->getPreferenceGroup(),
        'key' => BabylonPlugin::PREFERENCE_KEY,
        'value' => json_encode('ru', JSON_THROW_ON_ERROR),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => new GenericUser(['id' => 'user-1']));

    new SetLocale()->handle($request, fn () => null);

    expect(App::getLocale())->toBe('ru');
});

it('keeps the legacy session preference as a fallback', function () {
    $request = Request::create('/dashboard');
    $request->setLaravelSession(app('session')->driver());
    $request->session()->put(BabylonPlugin::PREFERENCE_KEY, 'ru');

    new SetLocale()->handle($request, fn () => null);

    expect(App::getLocale())->toBe('ru');
});

it('drives the Filament document language and direction', function () {
    $request = Request::create('/dashboard');
    $request->setLaravelSession(app('session')->driver());
    $request->session()->put($this->plugin->getSessionKey(), 'ar');

    new SetLocale()->handle($request, fn () => null);

    expect(str_replace('_', '-', App::getLocale()))
        ->toBe('ar')
        ->and(__('filament-panels::layout.direction'))
        ->toBe('rtl');
});

it('provides one locale option map', function () {
    expect($this->plugin->getLocales())
        ->toBe([
            'en' => 'English',
            'pl' => 'Polski',
            'ru' => 'Русский',
            'ar' => 'العربية',
        ]);
});

it('stores only enabled locales from the switcher route', function () {
    app(PanelRegistry::class)->register($this->panel);

    $this
        ->from('/dashboard')
        ->get(route('phpinnacle-babylon.switch-language', [
            'code' => 'pl',
            'panel' => 'admin',
        ]))
        ->assertRedirect('/dashboard')
        ->assertSessionHas($this->plugin->getSessionKey(), 'pl');

    $this->get(route('phpinnacle-babylon.switch-language', [
        'code' => 'de',
        'panel' => 'admin',
    ]))->assertNotFound();
});
