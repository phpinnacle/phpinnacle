<?php

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelRegistry;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Route;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Resources\Roles\RoleResource;
use PHPinnacle\Cerber\Resources\Users\UserResource;
use PHPinnacle\Cerber\Services\SocialAccountContextRepository;
use Tests\TestCase;

uses(TestCase::class);

it('registers developer login only when configured outside production', function (
    string $environment,
    array $developers,
    bool $enabled,
) {
    $this->app['env'] = $environment;
    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->default()
        ->plugin(CerberPlugin::make()->withoutResources()->developers($developers));
    app(PanelRegistry::class)->register($panel);
    Filament::setCurrentPanel($panel);
    require base_path('vendor/filament/filament/routes/web.php');
    Route::getRoutes()->refreshNameLookups();
    $panel->boot();

    $route = Route::getRoutes()->getByName('filament.admin.cerber-developer-auth');
    expect($route !== null)->toBe($enabled);

    if ($enabled) {
        expect($route->methods())->toBe(['POST'])->and($route->uri())->toBe('admin/developer-auth');
    }

    $html = FilamentView::renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER)->toHtml();
    expect(str_contains($html, 'developer@example.test'))->toBe($enabled);
})->with([
    ['testing', ['developer@example.test'], true],
    ['production', ['developer@example.test'], false],
    ['testing', [], false],
]);

it('registers context routes and protects account linking with panel authentication', function () {
    $context = Mockery::mock(SocialAccountContext::class);
    $context->shouldReceive('name')->andReturn('admin');
    $context->shouldReceive('guard')->andReturn('admin');
    app(SocialAccountContextRepository::class)->add($context);

    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->default()
        ->authMiddleware([Authenticate::class])
        ->plugin(CerberPlugin::make()->withoutResources()->socialAccountContext('admin'));
    app(PanelRegistry::class)->register($panel);
    require base_path('vendor/filament/filament/routes/web.php');
    Route::getRoutes()->refreshNameLookups();

    expect($panel->getAuthGuard())->toBe('admin');

    foreach (['redirect', 'callback', 'test', 'link'] as $action) {
        $route = Route::getRoutes()->getByName('filament.admin.auth.' . $action);
        expect($route->uri())
            ->toBe('admin/auth/{provider}/' . $action)
            ->and(in_array(Authenticate::class, $route->gatherMiddleware(), true))
            ->toBe($action === 'link');
    }
});

it('keeps resource selection independent of social login', function () {
    $panel = Panel::make()->id('admin')->plugin(CerberPlugin::make()->withoutProviders());

    expect($panel->getResources())
        ->toBe([RoleResource::class, UserResource::class])
        ->and($panel->getRoutes())
        ->toBeEmpty()
        ->and($panel->getAuthenticatedRoutes())
        ->toBeEmpty()
        ->and($panel->getAuthGuard())
        ->toBe('web');
});
