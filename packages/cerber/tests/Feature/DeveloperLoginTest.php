<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Http\Controllers\LoginController;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    $this->artisan('migrate')->assertSuccessful();

    config()->set('auth.providers.users.model', \PHPinnacle\Cerber\Models\User::class);
    config()->set('session.driver', 'array');

    $this->panel = Panel::make()->id('admin')->path('admin')->default();
    app(PanelRegistry::class)->register($this->panel);
    Filament::setCurrentPanel($this->panel);

    $this->tenant = Tenant::default('default');
    $this->developer = User::register('Developer', ['admin']);
    $this->developer->save();
    $this->developerLogin = Login::register(
        $this->developer,
        \PHPinnacle\Cerber\Enums\LoginType::Email,
        'developer@example.test',
    );
    $this->developerLogin->markVerified();
    $this->plugin = CerberPlugin::make()->developers(['developer@example.test']);

    session()->start();
});

it('logs in a configured developer and regenerates the session and csrf token', function () {
    $sessionId = session()->getId();
    $token = session()->token();

    expect($this->plugin->auth('developer@example.test', $this->panel, null))
        ->toBeTrue()
        ->and($this->panel->auth()->id())
        ->toBe($this->developer->id)
        ->and($this->panel->auth()->user()->id)
        ->toBe($this->developer->id)
        ->and(session()->getId())
        ->not->toBe($sessionId)->and(session()->token())
        ->not->toBe($token);
});

it('logs out the previous user before switching to a developer', function () {
    $previous = User::register('Previous', ['admin']);
    $previous->save();
    $this->panel->auth()->login($previous);
    Event::fake([Logout::class]);

    expect($this->plugin->auth('developer@example.test', $this->panel, null))
        ->toBeTrue()
        ->and($this->panel->auth()->id())
        ->toBe($this->developer->id);

    Event::assertDispatched(Logout::class, fn (Logout $event) => $event->user->is($previous));
});

it('rejects developer login in production without changing the session', function () {
    $environment = $this->app['env'];
    $this->app['env'] = 'production';
    $sessionId = session()->getId();

    try {
        expect($this->plugin->auth('developer@example.test', $this->panel, null))
            ->toBeFalse()
            ->and($this->panel->auth()->guest())
            ->toBeTrue()
            ->and(session()->getId())
            ->toBe($sessionId);
    } finally {
        $this->app['env'] = $environment;
    }
});

it('rejects an unconfigured developer without logging out the current user', function () {
    $this->panel->auth()->login($this->developer);
    $this->plugin->developers([]);
    $sessionId = session()->getId();

    expect($this->plugin->auth('developer@example.test', $this->panel, null))
        ->toBeFalse()
        ->and($this->panel->auth()->id())
        ->toBe($this->developer->id)
        ->and(session()->getId())
        ->toBe($sessionId);
});

it('rejects configured developers who have no user record', function () {
    $this->plugin->developers(['missing@example.test' => 'Missing developer']);

    expect($this->plugin->auth('missing@example.test', $this->panel, null))
        ->toBeFalse()
        ->and($this->panel->auth()->guest())
        ->toBeTrue();
});

it('rejects developers who cannot access the panel', function () {
    $this->developer->status = UserStatus::Blocked;
    $this->developer->save();

    expect($this->plugin->auth('developer@example.test', $this->panel, null))
        ->toBeFalse()
        ->and($this->panel->auth()->guest())
        ->toBeTrue();
});

it('requires access to the requested tenant', function () {
    Cerberus::registerScoped();

    expect($this->plugin->auth('developer@example.test', $this->panel, $this->tenant))
        ->toBeFalse()
        ->and($this->panel->auth()->guest())
        ->toBeTrue();

    $this->developer->grant(Role::register($this->tenant, 'Developer'));

    expect($this->plugin->auth('developer@example.test', $this->panel, $this->tenant))
        ->toBeTrue()
        ->and($this->panel->auth()->id())
        ->toBe($this->developer->id);
});

it('handles developer login through the validated controller request', function () {
    $this->panel->homeUrl('/admin')->plugin($this->plugin->withoutResources());
    Route::post('/developer-auth', LoginController::class)->middleware('web');

    $this->post('/developer-auth', [
        'panel' => 'admin',
        'credentials' => 'developer@example.test',
    ])->assertRedirect('/admin');

    expect($this->panel->auth()->id())->toBe($this->developer->id);

    $this->post('/developer-auth', [
        'panel' => 'admin',
        'credentials' => 'unconfigured@example.test',
    ])->assertForbidden();

    expect($this->panel->auth()->id())->toBe($this->developer->id);
});
