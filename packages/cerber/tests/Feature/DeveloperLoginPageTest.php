<?php

use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPinnacle\Cerber\CerberPlugin;
use Tests\TestCase;

uses(TestCase::class);

function registerLoginPanel(array $developers): Panel
{
    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->default()
        ->middleware([SubstituteBindings::class])
        ->plugin(CerberPlugin::make()->withoutResources()->developers($developers));
    app(PanelRegistry::class)->register($panel);
    Filament::setCurrentPanel($panel);
    require base_path('vendor/filament/filament/routes/web.php');
    Route::getRoutes()->refreshNameLookups();
    $panel->boot();

    return $panel;
}

it('places passwordless buttons below the sign-in form when developers are configured', function () {
    registerLoginPanel(['developer@example.test' => 'Developer']);
    auth()->logout();

    $html = Livewire::test(Login::class)->assertOk()->html();
    $passwordless = __('phpinnacle-cerber::pages.login.as');
    $heading = __('filament-panels::auth/pages/login.heading');
    $button = 'Developer (developer@example.test)';

    expect($html)
        ->toContain($passwordless)
        ->toContain($button)
        ->toContain($heading)
        ->toContain('fi-simple-header-heading');

    expect(strpos($html, $heading))
        ->toBeLessThan(strpos($html, $passwordless))
        ->toBeLessThan(strpos($html, $button));

    expect(strpos($html, $passwordless))
        ->toBeLessThan(strpos($html, $button));

    $after = FilamentView::renderHook(
        PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
        scopes: Login::class,
    )->toHtml();
    expect($after)
        ->toContain('developer@example.test')
        ->toContain($passwordless)
        ->toContain('fi-color-primary');

    expect(FilamentView::renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE)->toHtml())
        ->not
        ->toContain('developer@example.test');
});

it('keeps the regular login heading when no developers are configured', function () {
    registerLoginPanel([]);
    auth()->logout();

    $html = Livewire::test(Login::class)->assertOk()->html();

    expect($html)
        ->toContain(__('filament-panels::auth/pages/login.heading'))
        ->not->toContain(__('phpinnacle-cerber::pages.login.as'))
        ->not->toContain('developer@example.test');
});
